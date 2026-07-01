<?php
// Moodle config file path on server
$moodle_config_path = $_SERVER['HOME'] . '/moodle/config.php';

if (!file_exists($moodle_config_path)) {
    // Try alternative common locations
    $alt_paths = [
        $_SERVER['HOME'] . '/public_html/moodle/config.php',
        $_SERVER['HOME'] . '/public_html/config.php',
    ];
    foreach ($alt_paths as $alt) {
        if (file_exists($alt)) {
            $moodle_config_path = $alt;
            break;
        }
    }
}

if (!file_exists($moodle_config_path)) {
    echo "ERROR: Moodle config.php not found. Please verify Moodle installation path.\n";
    exit(1);
}

// Load Moodle config.php
// We define a dummy $CFG object to capture Moodle's configuration
unset($CFG);
global $CFG;
$CFG = new stdClass();

// Include the Moodle config file (it will populate $CFG)
// Moodle config.php might try to load setup.php, so we handle that by mocking setup.php or letting it run.
// To avoid execution errors from Moodle core functions, we can also parse the file as text.
$config_content = file_get_contents($moodle_config_path);

$dbhost = 'localhost';
$dbuser = '';
$dbpass = '';
$dbname = '';

if (preg_match('/\$CFG->dbhost\s*=\s*[\'"]([^\'"]+)[\'"]/', $config_content, $matches)) {
    $dbhost = $matches[1];
}
if (preg_match('/\$CFG->dbuser\s*=\s*[\'"]([^\'"]+)[\'"]/', $config_content, $matches)) {
    $dbuser = $matches[1];
}
if (preg_match('/\$CFG->dbpass\s*=\s*[\'"]([^\'"]+)[\'"]/', $config_content, $matches)) {
    $dbpass = $matches[1];
}
if (preg_match('/\$CFG->dbname\s*=\s*[\'"]([^\'"]+)[\'"]/', $config_content, $matches)) {
    $dbname = $matches[1];
}

if (empty($dbuser) || empty($dbname)) {
    echo "ERROR: Failed to parse database credentials from $moodle_config_path\n";
    exit(1);
}

echo "Detected Database Configuration from config.php:\n";
echo "  Host: $dbhost\n";
echo "  Database: $dbname\n";
echo "  User: $dbuser\n";
echo "  Password: [HIDDEN (" . strlen($dbpass) . " chars)]\n\n";

// SQL file path on server
$sql_file = $_SERVER['HOME'] . '/sitseduorg_moodle.sql';

if (!file_exists($sql_file)) {
    echo "ERROR: SQL file not found at $sql_file\n";
    exit(1);
}

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass, [
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
    
    $cmd = sprintf(
        "mysql -u %s -p%s -h %s %s < %s",
        escapeshellarg($dbuser),
        escapeshellarg($dbpass),
        escapeshellarg($dbhost),
        escapeshellarg($dbname),
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
