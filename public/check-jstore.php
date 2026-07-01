<?php
/**
 * Temporary diagnostic script to list all tables in the Joomla database.
 * 
 * SECURITY: Protected with a key. Delete this file after use.
 */
if (($_GET['key'] ?? '') !== 'sits_security_1994') {
    die('Unauthorized access.');
}

header('Content-Type: text/plain; charset=utf-8');

$host = '127.0.0.1';
$db = 'sitseduorg_joomla';
$user = 'sitseduorg_joomla';
$pass = 'p4sG)D44.S';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected successfully to $db.\n";
    echo "========================================\n\n";
    
    echo "Tables in database:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $foundJstore = false;
    foreach ($tables as $table) {
        echo " - $table\n";
        if (stripos($table, 'store') !== false || stripos($table, 'sub') !== false || stripos($table, 'plan') !== false) {
            $foundJstore = true;
        }
    }
    
    if (!$foundJstore) {
        echo "\nWarning: No table names containing 'store', 'sub', or 'plan' were found.\n";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
