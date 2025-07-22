<?php
/**
 * Database Update Script for Market-X Security Enhancement
 */

require_once 'includes/db.php';

echo "Starting database updates for Market-X...\n";

// First, let's check current database structure
try {
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Current tables: " . implode(', ', $tables) . "\n";
} catch (Exception $e) {
    echo "Error checking tables: " . $e->getMessage() . "\n";
}

// Update users table
echo "Updating users table...\n";
try {
    $updates = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_vendor TINYINT(1) DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS vendor_approved TINYINT(1) DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS vendor_payment_id VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS account_locked TINYINT(1) DEFAULT 0",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_token VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_expires TIMESTAMP NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(64) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_expires TIMESTAMP NULL"
    ];
    
    foreach ($updates as $update) {
        try {
            $conn->exec($update);
            echo "✓ " . substr($update, 0, 50) . "...\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "⚠ " . $e->getMessage() . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error updating users table: " . $e->getMessage() . "\n";
}

// Create user_profiles table
echo "Creating user_profiles table...\n";
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_profiles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            profile_image LONGTEXT DEFAULT NULL,
            description TEXT DEFAULT NULL,
            phone VARCHAR(255) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            birth_date DATE DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        )
    ");
    echo "✓ user_profiles table created\n";
} catch (Exception $e) {
    echo "⚠ user_profiles: " . $e->getMessage() . "\n";
}

// Create vendors table
echo "Creating vendors table...\n";
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS vendors (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            business_name VARCHAR(255) NOT NULL,
            business_description TEXT,
            payment_status ENUM('pending', 'paid', 'verified') DEFAULT 'pending',
            payment_amount DECIMAL(10,2) DEFAULT 100.00,
            payment_txid VARCHAR(100) DEFAULT NULL,
            payment_address VARCHAR(255) DEFAULT NULL,
            payment_date TIMESTAMP NULL,
            verification_date TIMESTAMP NULL,
            commission_rate DECIMAL(5,2) DEFAULT 5.00,
            total_sales DECIMAL(15,2) DEFAULT 0.00,
            total_products INT DEFAULT 0,
            rating_average DECIMAL(3,2) DEFAULT 0.00,
            rating_count INT DEFAULT 0,
            status ENUM('active', 'suspended', 'pending') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status)
        )
    ");
    echo "✓ vendors table created\n";
} catch (Exception $e) {
    echo "⚠ vendors: " . $e->getMessage() . "\n";
}

// Update products table
echo "Updating products table...\n";
try {
    $product_updates = [
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS vendor_id INT DEFAULT NULL",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS stock_quantity INT DEFAULT 0",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive', 'pending_approval') DEFAULT 'active'",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS views_count INT DEFAULT 0",
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS sales_count INT DEFAULT 0"
    ];
    
    foreach ($product_updates as $update) {
        try {
            $conn->exec($update);
            echo "✓ " . substr($update, 0, 50) . "...\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "⚠ " . $e->getMessage() . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error updating products table: " . $e->getMessage() . "\n";
}

// Create reviews table
echo "Creating reviews table...\n";
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT PRIMARY KEY AUTO_INCREMENT,
            product_id INT NOT NULL,
            user_id INT NOT NULL,
            vendor_id INT NOT NULL,
            order_id INT DEFAULT NULL,
            rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            review_title VARCHAR(255) NOT NULL,
            review_content TEXT NOT NULL,
            vendor_response TEXT DEFAULT NULL,
            vendor_response_date TIMESTAMP NULL,
            is_verified_purchase TINYINT(1) DEFAULT 0,
            helpful_votes INT DEFAULT 0,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_product_id (product_id),
            INDEX idx_user_id (user_id),
            INDEX idx_rating (rating)
        )
    ");
    echo "✓ reviews table created\n";
} catch (Exception $e) {
    echo "⚠ reviews: " . $e->getMessage() . "\n";
}

// Create messages table
echo "Creating messages table...\n";
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            sender_id INT NOT NULL,
            recipient_id INT NOT NULL,
            product_id INT DEFAULT NULL,
            order_id INT DEFAULT NULL,
            message_content TEXT NOT NULL,
            message_type ENUM('text', 'image', 'file') DEFAULT 'text',
            is_read TINYINT(1) DEFAULT 0,
            is_encrypted TINYINT(1) DEFAULT 1,
            encryption_key_hash VARCHAR(64) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
            INDEX idx_sender_recipient (sender_id, recipient_id),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✓ messages table created\n";
} catch (Exception $e) {
    echo "⚠ messages: " . $e->getMessage() . "\n";
}

// Create message_threads table
echo "Creating message_threads table...\n";
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS message_threads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            participant1_id INT NOT NULL,
            participant2_id INT NOT NULL,
            last_message_id INT DEFAULT NULL,
            last_message_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (participant1_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (participant2_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_participants (participant1_id, participant2_id),
            INDEX idx_last_message_date (last_message_date)
        )
    ");
    echo "✓ message_threads table created\n";
} catch (Exception $e) {
    echo "⚠ message_threads: " . $e->getMessage() . "\n";
}

// Update orders table
echo "Updating orders table...\n";
try {
    $order_updates = [
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS vendor_id INT DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS commission_amount DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS vendor_earnings DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS shipping_status ENUM('pending', 'processing', 'shipped', 'delivered') DEFAULT 'pending'",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL"
    ];
    
    foreach ($order_updates as $update) {
        try {
            $conn->exec($update);
            echo "✓ " . substr($update, 0, 50) . "...\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "⚠ " . $e->getMessage() . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error updating orders table: " . $e->getMessage() . "\n";
}

// Create user_favorites table
echo "Creating user_favorites table...\n";
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_favorites (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id)
        )
    ");
    echo "✓ user_favorites table created\n";
} catch (Exception $e) {
    echo "⚠ user_favorites: " . $e->getMessage() . "\n";
}

// Create security_audit_log table
echo "Creating security_audit_log table...\n";
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS security_audit_log (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT DEFAULT NULL,
            action VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT DEFAULT NULL,
            additional_data TEXT DEFAULT NULL,
            success TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            INDEX idx_action (action)
        )
    ");
    echo "✓ security_audit_log table created\n";
} catch (Exception $e) {
    echo "⚠ security_audit_log: " . $e->getMessage() . "\n";
}

// Ensure settings table exists and has required columns
echo "Updating settings table...\n";
try {
    // First check if settings table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() == 0) {
        // Create settings table
        $conn->exec("
            CREATE TABLE settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                key_name VARCHAR(100) NOT NULL UNIQUE,
                value TEXT NOT NULL,
                is_encrypted TINYINT(1) DEFAULT 0,
                description TEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_key_name (key_name)
            )
        ");
        echo "✓ settings table created\n";
    } else {
        // Add missing columns if needed
        try {
            $conn->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS is_encrypted TINYINT(1) DEFAULT 0");
            $conn->exec("ALTER TABLE settings ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL");
            echo "✓ settings table updated\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "⚠ settings update: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Insert default settings
    $default_settings = [
        ['store_name', 'Market-X', 0, 'Store display name'],
        ['vendor_fee', '100.00', 0, 'Fee to become a vendor in USD'],
        ['commission_rate', '5.0', 0, 'Default commission rate percentage'],
        ['max_file_size', '10485760', 0, 'Maximum file upload size in bytes (10MB)'],
        ['encryption_key', '', 1, 'Master encryption key for sensitive data'],
        ['jwt_secret', '', 1, 'JWT secret for secure tokens'],
        ['email_verification_required', '1', 0, 'Require email verification'],
        ['two_factor_enabled', '0', 0, 'Enable two-factor authentication'],
        ['xmr_address', '', 1, 'Monero payment address']
    ];
    
    foreach ($default_settings as $setting) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO settings (key_name, value, is_encrypted, description) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE description = VALUES(description)
            ");
            $stmt->execute($setting);
        } catch (Exception $e) {
            echo "⚠ Setting {$setting[0]}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✓ Default settings inserted\n";
    
} catch (Exception $e) {
    echo "Error with settings table: " . $e->getMessage() . "\n";
}

// Final database verification
echo "\n=== Database Update Summary ===\n";
try {
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Total tables: " . count($tables) . "\n";
    echo "Tables: " . implode(', ', $tables) . "\n";
    
    // Check users table structure
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Users table columns: " . count($columns) . "\n";
    
} catch (Exception $e) {
    echo "Error in final verification: " . $e->getMessage() . "\n";
}

echo "\n✅ Database update completed successfully!\n";
echo "Market-X is now ready for secure multi-vendor functionality.\n";

?>