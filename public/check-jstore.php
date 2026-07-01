<?php
/**
 * Temporary diagnostic script to inspect Alexandria Book Library tables.
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
    
    $tables = ['josn9_ablend', 'josn9_abbook', 'josn9_ablibrary'];
    
    foreach ($tables as $table) {
        echo "DESCRIBE $table:\n";
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                echo "  - {$column['Field']} ({$column['Type']}) " . 
                     ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
                     ($column['Key'] ? " KEY:{$column['Key']}" : "") . "\n";
            }
            
            // Fetch first 3 records to see sample data
            echo "\nSAMPLE DATA FROM $table (max 3):\n";
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                echo "  (No records found)\n";
            } else {
                foreach ($rows as $row) {
                    echo "  " . json_encode($row) . "\n";
                }
            }
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n";
        }
        echo "\n----------------------------------------\n\n";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
