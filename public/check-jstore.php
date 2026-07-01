<?php
/**
 * Temporary diagnostic script to locate Joomla configuration files
 * and extract database credentials/JSTORE settings on the production server.
 * 
 * SECURITY: Protected with a key. Delete this file after use.
 */
if (($_GET['key'] ?? '') !== 'sits_security_1994') {
    die('Unauthorized access.');
}

header('Content-Type: text/plain; charset=utf-8');

$homeDir = '/home/sitseduorg';
if (!is_dir($homeDir)) {
    $current = realpath(__DIR__);
    if (preg_match('/^(\/home\/[^\/]+)/', $current, $matches)) {
        $homeDir = $matches[1];
    } else {
        $homeDir = dirname($current, 2);
    }
}

echo "Searching home directory: $homeDir\n";
echo "========================================\n\n";

function searchJoomlaConfig($dir, &$results, $depth = 0) {
    if ($depth > 4) return;
    $files = @scandir($dir);
    if (!$files) return;
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            // Skip common large/irrelevant directories
            if (in_array($file, ['node_modules', 'vendor', 'storage', 'cache', 'tmp', 'logs', '.git', 'moodledata'])) {
                continue;
            }
            searchJoomlaConfig($path, $results, $depth + 1);
        } elseif ($file === 'configuration.php') {
            $results[] = $path;
        }
    }
}

$found = [];
searchJoomlaConfig($homeDir, $found);

if (empty($found)) {
    echo "No Joomla configuration.php files found.\n\n";
    echo "Directory listing of $homeDir:\n";
    $list = @scandir($homeDir);
    if ($list) {
        foreach ($list as $item) {
            if ($item !== '.' && $item !== '..') {
                $isDir = is_dir($homeDir . '/' . $item) ? '[DIR]' : '[FILE]';
                echo " $isDir $item\n";
            }
        }
    }
} else {
    echo "Found " . count($found) . " Joomla configuration.php file(s):\n";
    foreach ($found as $path) {
        echo "\n----------------------------------------\n";
        echo "PATH: $path\n";
        echo "----------------------------------------\n";
        
        $content = @file_get_contents($path);
        if ($content) {
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                // Print database settings, live_site, secret token, and mail config
                if (preg_match('/public\s+\$(host|user|password|db|dbprefix|sitename|live_site|secret|mailfrom|fromname)\b/', $line) ||
                    stripos($line, 'jstore') !== false) {
                    echo "  " . trim($line) . "\n";
                }
            }
        } else {
            echo "  Error: Could not read file contents.\n";
        }
    }
}
