-- ===============================================
-- Market-X Database Improvements
-- Adding security and admin features
-- ===============================================

-- Add admin users table for secure admin login
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add default super admin user (password: Admin123!)
INSERT INTO `admin_users` (`username`, `password`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@marketx.local', 'super_admin');

-- Add security audit log table
CREATE TABLE IF NOT EXISTS `security_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NULL,
  `admin_id` int(11) NULL,
  `action` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `success` tinyint(1) NOT NULL,
  `details` json NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `admin_id` (`admin_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add DDoS protection table
CREATE TABLE IF NOT EXISTS `ddos_protection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `request_count` int(11) DEFAULT 1,
  `blocked_until` timestamp NULL DEFAULT NULL,
  `first_request` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_request` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `blocked_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add messages table for messaging system
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message_content` text NOT NULL,
  `is_encrypted` tinyint(1) DEFAULT 1,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_id` (`recipient_id`),
  KEY `is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add user profiles table
CREATE TABLE IF NOT EXISTS `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `profile_image` text NULL,
  `description` text NULL,
  `phone` varchar(20) NULL,
  `address` text NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add vendors table
CREATE TABLE IF NOT EXISTS `vendors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `business_description` text,
  `rating_average` decimal(2,1) DEFAULT 0.0,
  `rating_count` int(11) DEFAULT 0,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `commission_rate` decimal(5,2) DEFAULT 5.00,
  `status` enum('pending','approved','suspended') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add reviews table
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) NULL,
  `rating` int(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `review_title` text NOT NULL,
  `review_content` text NOT NULL,
  `vendor_response` text NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `is_encrypted` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `vendor_id` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` text NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Improve existing tables
ALTER TABLE `users` 
  ADD COLUMN IF NOT EXISTS `is_vendor` tinyint(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `vendor_approved` tinyint(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `account_locked` tinyint(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `failed_login_attempts` int(11) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `last_login` timestamp NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `remember_token` varchar(255) NULL,
  ADD COLUMN IF NOT EXISTS `remember_expires` timestamp NULL DEFAULT NULL;

ALTER TABLE `products` 
  ADD COLUMN IF NOT EXISTS `vendor_id` int(11) NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `status` enum('active','inactive','pending_approval') DEFAULT 'pending_approval';

ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `commission_amount` decimal(10,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `tracking_number` varchar(100) NULL,
  ADD COLUMN IF NOT EXISTS `notes` text NULL;

-- Update settings table to support encryption
ALTER TABLE `settings` 
  ADD COLUMN IF NOT EXISTS `is_encrypted` tinyint(1) DEFAULT 0;

-- Add foreign key constraints for data integrity
ALTER TABLE `user_profiles` 
  ADD CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `vendors` 
  ADD CONSTRAINT `fk_vendors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_users_username` ON `users` (`username`);
CREATE INDEX IF NOT EXISTS `idx_users_email` ON `users` (`email`);
CREATE INDEX IF NOT EXISTS `idx_products_status` ON `products` (`status`);
CREATE INDEX IF NOT EXISTS `idx_orders_status` ON `orders` (`status`);
CREATE INDEX IF NOT EXISTS `idx_orders_user_id` ON `orders` (`user_id`);

-- Insert some initial settings
INSERT IGNORE INTO `settings` (`key_name`, `value`, `is_encrypted`) VALUES
('site_name', 'Market-X', 0),
('site_description', 'Secure Multi-Vendor Marketplace', 0),
('commission_rate', '5.00', 0),
('max_login_attempts', '5', 0),
('ddos_max_requests', '100', 0),
('ddos_time_window', '3600', 0),
('encryption_enabled', '1', 0);

COMMIT;