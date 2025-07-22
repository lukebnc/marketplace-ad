-- Database updates for secure multi-vendor marketplace
-- Adding encryption and security features

-- 1. Update users table to add profile fields and security (with IF NOT EXISTS checks)
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'email_verified' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0;', 'SELECT "Column email_verified already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'is_vendor' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN is_vendor TINYINT(1) DEFAULT 0;', 'SELECT "Column is_vendor already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'vendor_approved' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN vendor_approved TINYINT(1) DEFAULT 0;', 'SELECT "Column vendor_approved already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'vendor_payment_id' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN vendor_payment_id VARCHAR(100) DEFAULT NULL;', 'SELECT "Column vendor_payment_id already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'last_login' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;', 'SELECT "Column last_login already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'login_attempts' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN login_attempts INT DEFAULT 0;', 'SELECT "Column login_attempts already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'account_locked' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN account_locked TINYINT(1) DEFAULT 0;', 'SELECT "Column account_locked already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'two_factor_secret' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(32) DEFAULT NULL;', 'SELECT "Column two_factor_secret already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'password_reset_token' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL;', 'SELECT "Column password_reset_token already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'users' AND column_name = 'password_reset_expires' AND table_schema = DATABASE()) = 0, 'ALTER TABLE users ADD COLUMN password_reset_expires TIMESTAMP NULL;', 'SELECT "Column password_reset_expires already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Create user profiles table with encrypted data (if it doesn't exist)
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
);

-- 3. Create vendors table with payment tracking (if it doesn't exist)
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
);

-- 4. Update products table to associate with vendors (with IF NOT EXISTS checks)
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'products' AND column_name = 'vendor_id' AND table_schema = DATABASE()) = 0, 'ALTER TABLE products ADD COLUMN vendor_id INT DEFAULT NULL;', 'SELECT "Column vendor_id already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'products' AND column_name = 'stock_quantity' AND table_schema = DATABASE()) = 0, 'ALTER TABLE products ADD COLUMN stock_quantity INT DEFAULT 0;', 'SELECT "Column stock_quantity already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'products' AND column_name = 'status' AND table_schema = DATABASE()) = 0, 'ALTER TABLE products ADD COLUMN status ENUM("active", "inactive", "pending_approval") DEFAULT "pending_approval";', 'SELECT "Column status already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'products' AND column_name = 'views_count' AND table_schema = DATABASE()) = 0, 'ALTER TABLE products ADD COLUMN views_count INT DEFAULT 0;', 'SELECT "Column views_count already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'products' AND column_name = 'sales_count' AND table_schema = DATABASE()) = 0, 'ALTER TABLE products ADD COLUMN sales_count INT DEFAULT 0;', 'SELECT "Column sales_count already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add indexes if they don't exist
SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_name = 'products' AND index_name = 'idx_vendor_id' AND table_schema = DATABASE()) = 0, 'ALTER TABLE products ADD INDEX idx_vendor_id (vendor_id);', 'SELECT "Index idx_vendor_id already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_name = 'products' AND index_name = 'idx_status' AND table_schema = DATABASE()) = 0, 'ALTER TABLE products ADD INDEX idx_status (status);', 'SELECT "Index idx_status already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. Create reviews table with encrypted content (if it doesn't exist)
CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    vendor_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    rating TINYINT NOT NULL,
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
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_user_id (user_id),
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_rating (rating),
    UNIQUE KEY unique_user_product_review (user_id, product_id, order_id)
);

-- 6. Create messages table with end-to-end encryption (if it doesn't exist)
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
);

-- 7. Create message threads for organized conversations (if it doesn't exist)
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
    UNIQUE KEY unique_thread (participant1_id, participant2_id),
    INDEX idx_participants (participant1_id, participant2_id),
    INDEX idx_last_message_date (last_message_date)
);

-- 8. Update orders table for vendor management (with IF NOT EXISTS checks)
SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'orders' AND column_name = 'vendor_id' AND table_schema = DATABASE()) = 0, 'ALTER TABLE orders ADD COLUMN vendor_id INT DEFAULT NULL;', 'SELECT "Column vendor_id already exists in orders" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'orders' AND column_name = 'commission_amount' AND table_schema = DATABASE()) = 0, 'ALTER TABLE orders ADD COLUMN commission_amount DECIMAL(10,2) DEFAULT 0.00;', 'SELECT "Column commission_amount already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'orders' AND column_name = 'vendor_earnings' AND table_schema = DATABASE()) = 0, 'ALTER TABLE orders ADD COLUMN vendor_earnings DECIMAL(10,2) DEFAULT 0.00;', 'SELECT "Column vendor_earnings already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'orders' AND column_name = 'shipping_status' AND table_schema = DATABASE()) = 0, 'ALTER TABLE orders ADD COLUMN shipping_status ENUM("pending", "processing", "shipped", "delivered") DEFAULT "pending";', 'SELECT "Column shipping_status already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'orders' AND column_name = 'tracking_number' AND table_schema = DATABASE()) = 0, 'ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(100) DEFAULT NULL;', 'SELECT "Column tracking_number already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_name = 'orders' AND column_name = 'delivered_at' AND table_schema = DATABASE()) = 0, 'ALTER TABLE orders ADD COLUMN delivered_at TIMESTAMP NULL;', 'SELECT "Column delivered_at already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add index if it doesn't exist
SET @sql = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_name = 'orders' AND index_name = 'idx_vendor_id' AND table_schema = DATABASE()) = 0, 'ALTER TABLE orders ADD INDEX idx_vendor_id (vendor_id);', 'SELECT "Index idx_vendor_id already exists in orders" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 9. Create favorites/wishlist table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS user_favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_user_id (user_id)
);

-- 10. Create security audit log table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS security_audit_log (
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
);

-- 11. Create settings table for secure configuration
CREATE TABLE IF NOT EXISTS settings_new (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NOT NULL,
    is_encrypted TINYINT(1) DEFAULT 0,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key_name (key_name)
);

-- Migrate existing settings data
INSERT INTO settings_new (key_name, value, is_encrypted, description)
SELECT 
    key_name, 
    value, 
    0 as is_encrypted,
    CASE 
        WHEN key_name = 'store_name' THEN 'Store display name'
        WHEN key_name = 'xmr_address' THEN 'Monero payment address'
        ELSE 'Migrated setting'
    END as description
FROM settings;

-- Drop old settings table and rename new one
DROP TABLE settings;
RENAME TABLE settings_new TO settings;

-- Insert additional default settings
INSERT INTO settings (key_name, value, is_encrypted, description) VALUES
('vendor_fee', '100.00', 0, 'Fee to become a vendor in USD'),
('commission_rate', '5.0', 0, 'Default commission rate percentage'),
('max_file_size', '10485760', 0, 'Maximum file upload size in bytes (10MB)'),
('encryption_key', '', 1, 'Master encryption key for sensitive data'),
('jwt_secret', '', 1, 'JWT secret for secure tokens'),
('email_verification_required', '1', 0, 'Require email verification'),
('two_factor_enabled', '0', 0, 'Enable two-factor authentication')
ON DUPLICATE KEY UPDATE value = VALUES(value);

-- Update xmr_address to be encrypted
UPDATE settings SET is_encrypted = 1 WHERE key_name = 'xmr_address';

-- 12. Create password history table for security (if it doesn't exist)
CREATE TABLE IF NOT EXISTS password_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- 13. Add foreign key constraints with checks
SET @sql = IF((SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE table_name = 'products' AND constraint_name = 'fk_products_vendor' AND table_schema = DATABASE()) = 0, 'ALTER TABLE products ADD CONSTRAINT fk_products_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL;', 'SELECT "Foreign key fk_products_vendor already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF((SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE table_name = 'orders' AND constraint_name = 'fk_orders_vendor' AND table_schema = DATABASE()) = 0, 'ALTER TABLE orders ADD CONSTRAINT fk_orders_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL;', 'SELECT "Foreign key fk_orders_vendor already exists" as message;');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 14. Add trigger to prevent duplicate conversations (alternative to LEAST/GREATEST)
DROP TRIGGER IF EXISTS prevent_duplicate_threads;

DELIMITER //
CREATE TRIGGER prevent_duplicate_threads 
BEFORE INSERT ON message_threads 
FOR EACH ROW 
BEGIN
    DECLARE thread_exists INT DEFAULT 0;
    
    -- Check if thread already exists (in either direction)
    SELECT COUNT(*) INTO thread_exists
    FROM message_threads 
    WHERE (participant1_id = NEW.participant1_id AND participant2_id = NEW.participant2_id)
       OR (participant1_id = NEW.participant2_id AND participant2_id = NEW.participant1_id);
    
    -- If thread exists, prevent insertion
    IF thread_exists > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Thread between these participants already exists';
    END IF;
END//
DELIMITER ;