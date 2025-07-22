<?php
/**
 * Market-X Database Setup and Upgrade Script
 * Applies all security and admin improvements
 */

require_once 'includes/db.php';

function runDatabaseUpgrade() {
    global $conn;
    
    echo "🚀 Market-X Database Upgrade Starting...\n\n";
    
    try {
        // Read and execute the upgrade SQL
        $sql = file_get_contents('database_improvements.sql');
        
        // Split into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $conn->exec($statement);
                echo "✅ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Duplicate') !== false) {
                    echo "⚠️  Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
                } else {
                    echo "❌ Error: " . $e->getMessage() . "\n";
                    echo "   Statement: " . substr($statement, 0, 100) . "...\n";
                }
            }
        }
        
        echo "\n🎉 Database upgrade completed successfully!\n";
        
        // Verify critical tables
        echo "\n🔍 Verifying database structure...\n";
        
        $critical_tables = [
            'admin_users', 'security_audit_log', 'ddos_protection', 
            'messages', 'user_profiles', 'vendors', 'reviews', 'notifications'
        ];
        
        foreach ($critical_tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' exists\n";
            } else {
                echo "❌ Table '$table' missing\n";
            }
        }
        
        // Check if default admin exists
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
        $stmt->execute();
        $admin_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($admin_count > 0) {
            echo "✅ Default admin user exists\n";
        } else {
            echo "⚠️  Creating default admin user...\n";
            
            // Create default admin with secure password
            $admin_password = password_hash('Admin123!', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO admin_users (username, password, email, role) 
                VALUES ('admin', ?, 'admin@marketx.local', 'super_admin')
            ");
            
            if ($stmt->execute([$admin_password])) {
                echo "✅ Default admin created: username=admin, password=Admin123!\n";
                echo "⚠️  IMPORTANT: Change this password after first login!\n";
            } else {
                echo "❌ Failed to create default admin\n";
            }
        }
        
        echo "\n📊 Database Statistics:\n";
        
        // Show table statistics
        $tables = ['users', 'products', 'orders', 'admin_users'];
        foreach ($tables as $table) {
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "   $table: $count records\n";
            } catch (Exception $e) {
                echo "   $table: Error reading count\n";
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        echo "❌ Database upgrade failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Check if script is run from command line or browser
if (php_sapi_name() === 'cli') {
    // Command line
    echo "Market-X Database Upgrade Tool\n";
    echo "==============================\n\n";
    
    $success = runDatabaseUpgrade();
    exit($success ? 0 : 1);
    
} else {
    // Browser
    header('Content-Type: text/plain');
    echo "Market-X Database Upgrade Tool\n";
    echo "==============================\n\n";
    
    // Simple security check
    if (!isset($_GET['upgrade']) || $_GET['upgrade'] !== 'confirm') {
        echo "⚠️  To run the database upgrade, add '?upgrade=confirm' to the URL\n";
        echo "   Example: http://yoursite.com/database_upgrade.php?upgrade=confirm\n";
        exit();
    }
    
    runDatabaseUpgrade();
}

?>