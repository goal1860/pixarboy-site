<?php
/**
 * Database Configuration Example
 * 
 * Copy this file to database.php and update with your Hostinger database credentials
 * DO NOT commit database.php to version control
 */

// Hostinger Database Configuration
// Get these from Hostinger Control Panel > Databases
define('DB_HOST', 'localhost');        // Usually 'localhost' on Hostinger
define('DB_NAME', 'your_database_name'); // Your database name (e.g., u123456789_cms)
define('DB_USER', 'your_database_user'); // Your database username (e.g., u123456789_user)
define('DB_PASS', 'your_database_password'); // Your database password
define('DB_CHARSET', 'utf8mb4');

// Create database connection
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // For production, log the error instead of displaying it
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    return $pdo;
}

