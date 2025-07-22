<?php
/**
 * Database Setup Script for Market-X
 * This script will update the database with all required tables and features
 */

require_once 'includes/db.php';

echo "<h1>Market-X Database Setup</h1>";
echo "<style>body { font-family: Arial, sans-serif; background: #1a1a1a; color: #e0e0e0; margin: 20px; }";
echo "h1, h2 { color: #ff6b35; } .success { color: #28a745; } .error { color: #dc3545; } .info { color: #17a2b8; }</style>";

$errors = [];
$success = [];

// Function to execute SQL with error handling
function executeSQL($conn, $sql, $description) {
    global $errors, $success;
    
    try {
        $conn->exec($sql);
        $success[] = "✓ $description";
        return true;
    } catch (PDOException $e) {
        $errors[] = "✗ $description - Error: " . $e->getMessage();
        return false;
    }
}

echo "<h2>Actualizando Base de Datos...</h2>";

// 1. Update users table to add profile fields and security
echo "<p class='info'>Actualizando tabla 'users'...</p>";
$sqls = [
    "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN is_vendor TINYINT(1) DEFAULT 0", 
    "ALTER TABLE users ADD COLUMN vendor_approved TINYINT(1) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN vendor_payment_id VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL",
    "ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0",
    "ALTER TABLE users ADD COLUMN account_locked TINYINT(1) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(32) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN password_reset_expires TIMESTAMP NULL",
    "ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN remember_expires TIMESTAMP NULL"
];

foreach ($sqls as $sql) {
    executeSQL($conn, $sql, "Añadiendo campos de seguridad a users");
}

// 2. Create user profiles table
echo "<p class='info'>Creando tabla 'user_profiles'...</p>";
$sql = "CREATE TABLE IF NOT EXISTS user_profiles (
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
)";
executeSQL($conn, $sql, "Creando tabla user_profiles");

// 3. Create vendors table
echo "<p class='info'>Creando tabla 'vendors'...</p>";
$sql = "CREATE TABLE IF NOT EXISTS vendors (
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
)";
executeSQL($conn, $sql, "Creando tabla vendors");

// 4. Update products table
echo "<p class='info'>Actualizando tabla 'products'...</p>";
$sqls = [
    "ALTER TABLE products ADD COLUMN vendor_id INT DEFAULT NULL",
    "ALTER TABLE products ADD COLUMN stock_quantity INT DEFAULT 0",
    "ALTER TABLE products ADD COLUMN status ENUM('active', 'inactive', 'pending_approval') DEFAULT 'pending_approval'",
    "ALTER TABLE products ADD COLUMN views_count INT DEFAULT 0", 
    "ALTER TABLE products ADD COLUMN sales_count INT DEFAULT 0",
    "ALTER TABLE products ADD COLUMN category VARCHAR(100) DEFAULT 'general'",
    "ALTER TABLE products ADD INDEX idx_vendor_id (vendor_id)",
    "ALTER TABLE products ADD INDEX idx_status (status)"
];

foreach ($sqls as $sql) {
    executeSQL($conn, $sql, "Actualizando tabla products");
}

// 5. Create reviews table
echo "<p class='info'>Creando tabla 'reviews'...</p>";
$sql = "CREATE TABLE IF NOT EXISTS reviews (
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
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_rating (rating)
)";
executeSQL($conn, $sql, "Creando tabla reviews");

// 6. Create messages table
echo "<p class='info'>Creando tabla 'messages'...</p>";
$sql = "CREATE TABLE IF NOT EXISTS messages (
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
)";
executeSQL($conn, $sql, "Creando tabla messages");

// 7. Update orders table
echo "<p class='info'>Actualizando tabla 'orders'...</p>";
$sqls = [
    "ALTER TABLE orders ADD COLUMN vendor_id INT DEFAULT NULL",
    "ALTER TABLE orders ADD COLUMN commission_amount DECIMAL(10,2) DEFAULT 0.00",
    "ALTER TABLE orders ADD COLUMN vendor_earnings DECIMAL(10,2) DEFAULT 0.00",
    "ALTER TABLE orders ADD COLUMN shipping_status ENUM('pending', 'processing', 'shipped', 'delivered') DEFAULT 'pending'",
    "ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE orders ADD COLUMN delivered_at TIMESTAMP NULL",
    "ALTER TABLE orders ADD INDEX idx_vendor_id (vendor_id)"
];

foreach ($sqls as $sql) {
    executeSQL($conn, $sql, "Actualizando tabla orders");
}

// 8. Update settings table
echo "<p class='info'>Actualizando tabla 'settings'...</p>";
$sqls = [
    "ALTER TABLE settings ADD COLUMN is_encrypted TINYINT(1) DEFAULT 0",
    "ALTER TABLE settings ADD COLUMN description TEXT DEFAULT NULL",
    "ALTER TABLE settings ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE settings ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

foreach ($sqls as $sql) {
    executeSQL($conn, $sql, "Actualizando tabla settings");
}

// 9. Create security audit log table
echo "<p class='info'>Creando tabla 'security_audit_log'...</p>";
$sql = "CREATE TABLE IF NOT EXISTS security_audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    additional_data JSON DEFAULT NULL,
    success TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_action (action)
)";
executeSQL($conn, $sql, "Creando tabla security_audit_log");

// 10. Create user favorites table
echo "<p class='info'>Creando tabla 'user_favorites'...</p>";
$sql = "CREATE TABLE IF NOT EXISTS user_favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user_id (user_id)
)";
executeSQL($conn, $sql, "Creando tabla user_favorites");

// 11. Insert default settings
echo "<p class='info'>Insertando configuraciones por defecto...</p>";
$default_settings = [
    ['vendor_fee', '100.00', 0, 'Fee to become a vendor in USD'],
    ['commission_rate', '5.0', 0, 'Default commission rate percentage'],
    ['max_file_size', '10485760', 0, 'Maximum file upload size in bytes (10MB)'],
    ['encryption_key', '', 1, 'Master encryption key for sensitive data'],
    ['jwt_secret', '', 1, 'JWT secret for secure tokens'],
    ['email_verification_required', '1', 0, 'Require email verification'],
    ['two_factor_enabled', '0', 0, 'Enable two-factor authentication'],
    ['marketplace_title', 'Market-X', 0, 'Marketplace title'],
    ['marketplace_description', 'Secure Multi-Vendor Marketplace', 0, 'Marketplace description'],
    ['maintenance_mode', '0', 0, 'Maintenance mode enabled']
];

foreach ($default_settings as $setting) {
    $stmt = $conn->prepare("INSERT INTO settings (key_name, value, is_encrypted, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
    if ($stmt->execute($setting)) {
        $success[] = "✓ Configuración '{$setting[0]}' añadida/actualizada";
    }
}

// 12. Create admin user if not exists
echo "<p class='info'>Creando usuario administrador...</p>";
$admin_password = password_hash('admin123', PASSWORD_ARGON2ID);
$stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, email, is_vendor, vendor_approved) VALUES (?, ?, ?, ?, ?)");
if ($stmt->execute(['admin', $admin_password, 'admin@market-x.local', 1, 1])) {
    $success[] = "✓ Usuario administrador creado (admin/admin123)";
}

// Show results
echo "<h2>Resultados:</h2>";

if (!empty($success)) {
    echo "<h3 class='success'>Operaciones exitosas:</h3><ul>";
    foreach ($success as $msg) {
        echo "<li class='success'>$msg</li>";
    }
    echo "</ul>";
}

if (!empty($errors)) {
    echo "<h3 class='error'>Errores encontrados:</h3><ul>";
    foreach ($errors as $msg) {
        echo "<li class='error'>$msg</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='success'><strong>¡Base de datos actualizada exitosamente!</strong></p>";
    echo "<p class='info'>Puedes eliminar este archivo por seguridad: <code>setup_database.php</code></p>";
}

echo "<p><a href='index.php' style='color: #ff6b35;'>← Volver al marketplace</a></p>";
?>