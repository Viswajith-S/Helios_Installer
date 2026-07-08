<?php
/**
 * Dual Database Connection File
 * 
 * $db_crm connects to the CRM2 Inventory database (Read Only recommended)
 * $db_installs connects to the new Installs database (Read/Write)
 */

$db_host = '127.0.0.1';
$db_port = '3307';
$db_user = 'root'; // Replace with your database username
$db_pass = ''; // Replace with your database password
$charset = 'utf8mb4';

// PDO Options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];

try {
    // 1. Connect to CRM Database (inventory - lowercase)
    $dsn_crm = "mysql:host=$db_host;port=$db_port;dbname=inventory;charset=$charset";
    $db_crm = new PDO($dsn_crm, $db_user, $db_pass, $options);
    
    // 2. Connect to New Installs Database (installs - lowercase)
    $dsn_installs = "mysql:host=$db_host;port=$db_port;dbname=installs;charset=$charset";
    $db_installs = new PDO($dsn_installs, $db_user, $db_pass, $options);
    
} catch (\PDOException $e) {
    // For local dev, gracefully fallback instead of dying
    $db_crm = null;
    $db_installs = null;
    $db_error = "Database connection failed: " . $e->getMessage();
}
?>
