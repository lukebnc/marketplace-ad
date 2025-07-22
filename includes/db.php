<?php
/**
 * Database Configuration for Market-X
 * Make sure these settings match your phpMyAdmin configuration
 */

// Database connection settings
$host = 'localhost';           // Usually 'localhost' for local servers
$dbname = 'ecommerce_db';      // Make sure this database exists in phpMyAdmin
$username = 'root';            // Your MySQL username
$password = '';                // Your MySQL password (empty for XAMPP default)

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Test the connection with a simple query
    $test = $conn->query("SELECT 1");
    
} catch (PDOException $e) {
    // More detailed error message for debugging
    $error_msg = "Database connection failed!\n";
    $error_msg .= "Error: " . $e->getMessage() . "\n";
    $error_msg .= "Make sure:\n";
    $error_msg .= "1. MySQL server is running\n";
    $error_msg .= "2. Database 'ecommerce_db' exists\n"; 
    $error_msg .= "3. Username/password are correct\n";
    $error_msg .= "4. You've imported the ecommerce_complete.sql file\n";
    
    // Log the error
    error_log($error_msg);
    
    // Show user-friendly error
    die("<h1>Database Connection Error</h1>
         <p>Cannot connect to the database. Please check:</p>
         <ul>
         <li>MySQL server is running</li>
         <li>Database 'ecommerce_db' exists</li>
         <li>Import the provided ecommerce_complete.sql file</li>
         <li>Username and password are correct</li>
         </ul>
         <p>Technical error: " . htmlspecialchars($e->getMessage()) . "</p>");
}
?>