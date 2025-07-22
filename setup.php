<?php
/**
 * Database Setup Script for Market-X
 * Run this once to initialize the database and create an admin user
 */

session_start();
require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Market-X Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #f5c6cb; }
        .info { background: #cce7ff; color: #004085; padding: 15px; border-radius: 4px; margin: 10px 0; border: 1px solid #99d3ff; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🚀 Market-X Database Setup</h1>";

try {
    // Test database connection
    $test = $conn->query("SELECT 1");
    echo "<div class='success'>✓ Database connection successful!</div>";
    
    // Check if admin user exists
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            // Create admin user
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO admin_users (username, password, email, role, is_active, created_at) 
                VALUES ('admin', ?, 'admin@marketx.local', 'super_admin', 1, NOW())
            ");
            
            if ($stmt->execute([$admin_password])) {
                echo "<div class='success'>✓ Admin user created successfully!</div>";
                echo "<div class='info'>
                    <strong>Admin Login Details:</strong><br>
                    Username: <strong>admin</strong><br>
                    Password: <strong>admin123</strong><br>
                    <small>Please change this password after first login!</small>
                </div>";
            } else {
                echo "<div class='error'>✗ Failed to create admin user</div>";
            }
        } else {
            echo "<div class='info'>ℹ Admin user already exists</div>";
        }
    } catch (Exception $e) {
        // Table might not exist, try to create it
        echo "<div class='info'>Creating admin_users table...</div>";
        
        $sql = "CREATE TABLE `admin_users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `password` varchar(255) NOT NULL,
            `email` varchar(100) NOT NULL,
            `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
            `is_active` tinyint(1) DEFAULT 1,
            `failed_login_attempts` int(11) DEFAULT 0,
            `last_login` timestamp NULL DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $conn->exec($sql);
        echo "<div class='success'>✓ admin_users table created!</div>";
        
        // Now create admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO admin_users (username, password, email, role, is_active, created_at) 
            VALUES ('admin', ?, 'admin@marketx.local', 'super_admin', 1, NOW())
        ");
        
        if ($stmt->execute([$admin_password])) {
            echo "<div class='success'>✓ Admin user created successfully!</div>";
            echo "<div class='info'>
                <strong>Admin Login Details:</strong><br>
                Username: <strong>admin</strong><br>
                Password: <strong>admin123</strong><br>
                <small>Please change this password after first login!</small>
            </div>";
        }
    }
    
    // Check essential tables
    $essential_tables = ['users', 'products', 'orders', 'messages', 'vendors'];
    $missing_tables = [];
    
    foreach ($essential_tables as $table) {
        try {
            $conn->query("SELECT 1 FROM $table LIMIT 1");
            echo "<div class='success'>✓ Table '$table' exists</div>";
        } catch (Exception $e) {
            $missing_tables[] = $table;
            echo "<div class='error'>✗ Table '$table' is missing</div>";
        }
    }
    
    if (!empty($missing_tables)) {
        echo "<div class='error'>
            <strong>Missing tables detected!</strong><br>
            Please import the 'ecommerce_complete.sql' file into your database.<br>
            Missing tables: " . implode(', ', $missing_tables) . "
        </div>";
    } else {
        echo "<div class='success'>✓ All essential tables are present!</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='error'>
        Please check your database configuration in includes/db.php:<br>
        - Make sure MySQL server is running<br>
        - Verify database name, username, and password<br>
        - Import the ecommerce_complete.sql file
    </div>";
}

echo "
        <hr>
        <h3>🔧 Quick Actions</h3>
        <a href='index.php' class='btn'>Go to Marketplace</a>
        <a href='admin/login.php' class='btn'>Admin Login</a>
        <a href='login.php' class='btn'>User Login</a>
        
        <hr>
        <p><small>This setup script helps initialize your Market-X marketplace. Run it once after setting up your database.</small></p>
    </div>
</body>
</html>";
?>