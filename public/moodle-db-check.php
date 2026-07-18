<?php
/**
 * Temporary diagnostic script to troubleshoot Moodle database connectivity issues.
 * 
 * SECURITY: Protected with a key. Delete this file after use.
 */
if (($_GET['key'] ?? '') !== 'sits_security_1994') {
    die('Unauthorized access.');
}

header('Content-Type: text/plain; charset=utf-8');

// Find home directory
$homeDir = '/home/sitseduorg';
if (!is_dir($homeDir)) {
    $current = realpath(__DIR__);
    if (preg_match('/^(\/home\/[^\/]+)/', $current, $matches)) {
        $homeDir = $matches[1];
    } else {
        $homeDir = dirname($current, 2);
    }
}

echo "Diagnostic run on: " . date('Y-m-d H:i:s') . "\n";
echo "Home directory: $homeDir\n";
echo "========================================\n\n";

$candidates = [
    'NEW Moodle (moodle)' => [
        "$homeDir/moodle/config.php",
        "$homeDir/moodle/public/config.php",
    ],
    'OLD Moodle (moodle-old)' => [
        "$homeDir/moodle-old/config.php",
        "$homeDir/moodle-old/public/config.php",
    ]
];

function parse_moodle_config($path) {
    if (!file_exists($path)) {
        return null;
    }
    $src = file_get_contents($path);
    $g = function ($key, $default = '') use ($src) {
        // Match both single and double quotes, and handle spaces
        if (preg_match('/\$CFG->' . preg_quote($key, '/') . '\s*=\s*[\'"]([^\'"]*)[\'"]/', $src, $m)) {
            return $m[1];
        }
        return $default;
    };
    return [
        'path'     => $path,
        'dbtype'   => $g('dbtype', 'mariadb'),
        'dbhost'   => $g('dbhost', 'localhost'),
        'dbname'   => $g('dbname'),
        'dbuser'   => $g('dbuser'),
        'dbpass'   => $g('dbpass'),
        'prefix'   => $g('prefix', 'mdl_'),
        'dataroot' => $g('dataroot'),
        'wwwroot'  => $g('wwwroot'),
    ];
}

foreach ($candidates as $name => $paths) {
    echo "Checking configuration for $name:\n";
    $config = null;
    foreach ($paths as $p) {
        echo " - Checking file: $p ... " . (file_exists($p) ? "FOUND" : "NOT FOUND") . "\n";
        if (file_exists($p)) {
            $config = parse_moodle_config($p);
            break;
        }
    }

    if (!$config) {
        echo " => No configuration file found for $name.\n";
        echo "----------------------------------------\n\n";
        continue;
    }

    echo "Parsed settings:\n";
    echo "  - dbtype:   {$config['dbtype']}\n";
    echo "  - dbhost:   {$config['dbhost']}\n";
    echo "  - dbname:   {$config['dbname']}\n";
    echo "  - dbuser:   {$config['dbuser']}\n";
    echo "  - dbpass:   " . (empty($config['dbpass']) ? "[EMPTY]" : (substr($config['dbpass'], 0, 2) . str_repeat('*', strlen($config['dbpass']) - 4) . substr($config['dbpass'], -2))) . " (length: " . strlen($config['dbpass']) . ")\n";
    echo "  - prefix:   {$config['prefix']}\n";
    echo "  - wwwroot:  {$config['wwwroot']}\n";
    echo "  - dataroot: {$config['dataroot']}\n";

    // Check directory permissions for dataroot
    if (!empty($config['dataroot'])) {
        echo "  - dataroot status: ";
        if (is_dir($config['dataroot'])) {
            echo "Exists, is writable? " . (is_writable($config['dataroot']) ? "YES" : "NO") . ", perms: " . substr(sprintf('%o', fileperms($config['dataroot'])), -4) . "\n";
        } else {
            echo "DOES NOT EXIST\n";
        }
    }

    // Attempt DB Connection
    echo "\nConnecting to Database:\n";
    try {
        $dsn = "mysql:host={$config['dbhost']};dbname={$config['dbname']};charset=utf8mb4";
        $start = microtime(true);
        $pdo = new PDO($dsn, $config['dbuser'], $config['dbpass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5, // Timeout after 5 seconds
        ]);
        $duration = round(microtime(true) - $start, 4);
        echo "  => SUCCESS (took {$duration}s)\n";

        // Query basic facts
        $p = $config['prefix'];
        $tables_stmt = $pdo->query("SHOW TABLES");
        $tables = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "  => Total tables in DB: " . count($tables) . "\n";

        if (count($tables) > 0) {
            // Check if mdl_config table exists
            $config_table = $p . "config";
            if (in_array($config_table, $tables)) {
                $v_stmt = $pdo->prepare("SELECT value FROM $config_table WHERE name = ?");
                $v_stmt->execute(['version']);
                $version = $v_stmt->fetchColumn();

                $r_stmt = $pdo->prepare("SELECT value FROM $config_table WHERE name = ?");
                $r_stmt->execute(['release']);
                $release = $r_stmt->fetchColumn();

                echo "  => Moodle Database Version: $version\n";
                echo "  => Moodle Database Release: $release\n";
            } else {
                echo "  => WARNING: Config table '$config_table' not found!\n";
            }

            // Check if mdl_user table exists and count users
            $user_table = $p . "user";
            if (in_array($user_table, $tables)) {
                $u_stmt = $pdo->query("SELECT COUNT(*) FROM $user_table WHERE deleted = 0");
                $users = $u_stmt->fetchColumn();
                echo "  => Active users: $users\n";
            }
        } else {
            echo "  => WARNING: Database is completely empty!\n";
        }

    } catch (PDOException $e) {
        echo "  => CONNECTION FAILED!\n";
        echo "  => Error Code: " . $e->getCode() . "\n";
        echo "  => Error Message: " . $e->getMessage() . "\n";
    }

    echo "----------------------------------------\n\n";
}
