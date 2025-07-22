-- QUICK TEST DATABASE FOR MARKET-X
-- Simple structure to test login/registration quickly
-- Use this if the complete database is too complex

SET NAMES utf8mb4;
DROP DATABASE IF EXISTS ecommerce_db;
CREATE DATABASE ecommerce_db;
USE ecommerce_db;

-- Basic users table for authentication
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `is_vendor` tinyint(1) DEFAULT 0,
  `vendor_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Basic products table
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Basic settings table for configuration
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert test users
INSERT INTO `users` (`username`, `password`, `email`, `is_vendor`, `vendor_approved`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@marketx.local', 1, 1),
('testuser', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'test@example.com', 0, 0);

-- Insert test products
INSERT INTO `products` (`name`, `description`, `price`) VALUES
('Test Product 1', 'This is a test product description', 99.99),
('Test Product 2', 'Another test product', 149.50),
('Test Product 3', 'Third test product', 75.00);

-- Insert basic settings
INSERT INTO `settings` (`key_name`, `value`) VALUES
('store_name', 'Market-X'),
('version', '1.0');

-- Test the data
SELECT 'Users created:' as info, COUNT(*) as count FROM users;
SELECT 'Products created:' as info, COUNT(*) as count FROM products;
SELECT 'Settings created:' as info, COUNT(*) as count FROM settings;

/*
LOGIN CREDENTIALS:
- Username: admin
- Password: secret

- Username: testuser  
- Password: secret

Both passwords are "secret" for testing
*/