<?php
// Database credentials
$host = '127.0.0.1';
$db   = 'sitseduorg_moodle';
$user = 'sitseduorg_moodle';
$pass = 'SITS@1994'; // We saw this password used in the main website db commands

// SQL file path on server
$sql_file = $_SERVER['HOME'] . '/sitseduorg_moodle.sql';

if (!file_exists($sql_file)) {
    echo "ERROR: SQL file not found at $sql_file\n";
    exit(1);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "Connected successfully to database.\n";
    
    // 1. Drop all tables
    echo "Dropping all existing tables...\n";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
        echo "Dropped table: $table\n";
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "All tables dropped.\n";
    
    // 2. Import SQL file
    echo "Importing SQL file ($sql_file)...\n";
    
    // We can run the mysql CLI directly from PHP to import large SQL files efficiently
    $cmd = sprintf(
        "mysql -u %s -p%s -h %s %s < %s",
        escapeshellarg($user),
        escapeshellarg($pass),
        escapeshellarg($host),
        escapeshellarg($db),
        escapeshellarg($sql_file)
    );
    
    system($cmd, $retval);
    
    if ($retval === 0) {
        echo "Database imported successfully!\n";
    } else {
        echo "ERROR: Import command failed with exit code $retval\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
