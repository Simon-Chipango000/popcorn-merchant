<?php
// config.php - Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'popcorn_paradise');
define('DB_USER', 'root');
define('DB_PASS', '');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Create database connection
function getDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Create tables if they don't exist
function initializeDatabase() {
    $pdo = getDB();
    
    // Orders table
    $sql = "CREATE TABLE IF NOT EXISTS orders (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(20),
        flavor VARCHAR(50) NOT NULL,
        spice_level VARCHAR(20) NOT NULL,
        quantity INT(11) NOT NULL,
        delivery_method VARCHAR(50) NOT NULL,
        delivery_address TEXT,
        special_instructions TEXT,
        total_price DECIMAL(10,2) NOT NULL,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'confirmed', 'shipped', 'delivered') DEFAULT 'pending'
    )";
    
    $pdo->exec($sql);
}

// Initialize the database when this file is included
initializeDatabase();
?>