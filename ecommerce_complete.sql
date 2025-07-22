-- ================================================
-- MARKET-X COMPLETE DATABASE STRUCTURE
-- Base de datos completa para solucionar login/registro
-- Incluye todas las tablas y configuraciones necesarias
-- ================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar tablas existentes si existen
DROP TABLE IF EXISTS `ddos_protection`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `security_audit_log`;
DROP TABLE IF EXISTS `user_favorites`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `vendors`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `admin_users`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;

-- ================================================
-- TABLA: users (Usuarios del sistema)
-- ================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `is_vendor` tinyint(1) DEFAULT 0,
  `vendor_approved` tinyint(1) DEFAULT 0,
  `vendor_payment_id` varchar(100) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `account_locked` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(32) DEFAULT NULL,
  `password_reset_token` varchar(64) DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `remember_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: admin_users (Administradores del sistema)
-- ================================================
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: settings (Configuración del sistema)
-- ================================================
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: products (Productos del marketplace)
-- ================================================
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vendor_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT 'general',
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','pending_approval') DEFAULT 'pending_approval',
  `views_count` int(11) DEFAULT 0,
  `sales_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: vendors (Sistema de vendedores)
-- ================================================
CREATE TABLE `vendors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_description` text DEFAULT NULL,
  `payment_status` enum('pending','paid','verified') DEFAULT 'pending',
  `payment_amount` decimal(10,2) DEFAULT 100.00,
  `payment_txid` varchar(100) DEFAULT NULL,
  `payment_address` varchar(255) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `verification_date` timestamp NULL DEFAULT NULL,
  `commission_rate` decimal(5,2) DEFAULT 5.00,
  `total_sales` decimal(15,2) DEFAULT 0.00,
  `total_products` int(11) DEFAULT 0,
  `rating_average` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(11) DEFAULT 0,
  `status` enum('active','suspended','pending') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: orders (Sistema de pedidos)
-- ================================================
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `commission_amount` decimal(10,2) DEFAULT 0.00,
  `vendor_earnings` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','processing','completed','cancelled','refunded') DEFAULT 'pending',
  `shipping_status` enum('pending','processing','shipped','delivered') DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'xmr',
  `payment_address` varchar(255) DEFAULT NULL,
  `payment_txid` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: user_profiles (Perfiles de usuario)
-- ================================================
CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `profile_image` longtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: messages (Sistema de mensajería)
-- ================================================
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `message_content` text NOT NULL,
  `message_type` enum('text','image','file') DEFAULT 'text',
  `is_read` tinyint(1) DEFAULT 0,
  `is_encrypted` tinyint(1) DEFAULT 1,
  `encryption_key_hash` varchar(64) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sender_recipient` (`sender_id`,`recipient_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: reviews (Sistema de reseñas)
-- ================================================
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review_title` varchar(255) NOT NULL,
  `review_content` text NOT NULL,
  `vendor_response` text DEFAULT NULL,
  `vendor_response_date` timestamp NULL DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `helpful_votes` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: user_favorites (Lista de favoritos)
-- ================================================
CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_product` (`user_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: security_audit_log (Logs de seguridad)
-- ================================================
CREATE TABLE `security_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `additional_data` json DEFAULT NULL,
  `success` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: notifications (Sistema de notificaciones)
-- ================================================
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` text NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- TABLA: ddos_protection (Protección DDoS)
-- ================================================
CREATE TABLE `ddos_protection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `request_count` int(11) DEFAULT 1,
  `blocked_until` timestamp NULL DEFAULT NULL,
  `first_request` timestamp DEFAULT CURRENT_TIMESTAMP,
  `last_request` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`),
  KEY `idx_blocked_until` (`blocked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- FOREIGN KEY CONSTRAINTS
-- ================================================
ALTER TABLE `products` ADD CONSTRAINT `fk_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;
ALTER TABLE `vendors` ADD CONSTRAINT `fk_vendors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;
ALTER TABLE `orders` ADD CONSTRAINT `fk_orders_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
ALTER TABLE `user_profiles` ADD CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `messages` ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `messages` ADD CONSTRAINT `fk_messages_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `messages` ADD CONSTRAINT `fk_messages_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;
ALTER TABLE `reviews` ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
ALTER TABLE `reviews` ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `reviews` ADD CONSTRAINT `fk_reviews_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;
ALTER TABLE `user_favorites` ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `user_favorites` ADD CONSTRAINT `fk_favorites_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
ALTER TABLE `security_audit_log` ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `notifications` ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- ================================================
-- DATOS INICIALES: CONFIGURACIÓN DEL SISTEMA
-- ================================================
INSERT INTO `settings` (`key_name`, `value`, `is_encrypted`, `description`) VALUES
('vendor_fee', '100.00', 0, 'Fee to become a vendor in USD'),
('commission_rate', '5.0', 0, 'Default commission rate percentage'),
('max_file_size', '10485760', 0, 'Maximum file upload size in bytes (10MB)'),
('encryption_key', 'U2VjdXJlS2V5Rm9yTWFya2V0WEVuY3J5cHRpb24yMDI1', 1, 'Master encryption key for sensitive data'),
('jwt_secret', 'U3VwZXJTZWN1cmVKV1RTZWNyZXRGb3JNYXJrZXRYMjAyNQ==', 1, 'JWT secret for secure tokens'),
('email_verification_required', '0', 0, 'Require email verification'),
('two_factor_enabled', '0', 0, 'Enable two-factor authentication'),
('marketplace_title', 'Market-X', 0, 'Marketplace title'),
('marketplace_description', 'Secure Multi-Vendor Marketplace', 0, 'Marketplace description'),
('maintenance_mode', '0', 0, 'Maintenance mode enabled'),
('store_name', 'Market-X', 0, 'Store display name'),
('xmr_address', '4AdUndXHHZ6cfufTMvppY6JwXNouMBzSkbLYfpAV5Usx3skxNgYeYTRj5UzqtReoS44qo9mtmXCqY45DJ852K5Jv2684Rge', 0, 'Monero payment address'),
('max_login_attempts', '5', 0, 'Maximum login attempts before lockout'),
('ddos_max_requests', '100', 0, 'Maximum requests per time window'),
('ddos_time_window', '3600', 0, 'DDoS protection time window in seconds'),
('encryption_enabled', '1', 0, 'Enable encryption features');

-- ================================================
-- DATOS INICIALES: USUARIO ADMINISTRADOR
-- ================================================
INSERT INTO `admin_users` (`username`, `password`, `email`, `role`, `is_active`) VALUES
('admin', '$argon2id$v=19$m=65536,t=4,p=3$WHhsNmh6UWRyUGV2bGlQUw$+rPj2K6/Gf9gvWnr+fZyV7qwYLCZGf9j8k3MlN0pQ4c', 'admin@marketx.local', 'super_admin', 1);

-- ================================================
-- DATOS INICIALES: USUARIO DE PRUEBA
-- ================================================
INSERT INTO `users` (`username`, `password`, `email`, `is_vendor`, `vendor_approved`, `email_verified`) VALUES
('admin', '$argon2id$v=19$m=65536,t=4,p=3$WHhsNmh6UWRyUGV2bGlQUw$+rPj2K6/Gf9gvWnr+fZyV7qwYLCZGf9j8k3MlN0pQ4c', 'admin@marketx.local', 1, 1, 1),
('testuser', '$argon2id$v=19$m=65536,t=4,p=3$YklHQm5aeUt4VVJjckFVZw$8xMvNa6fLm9kWp3rYzQ+F7qLJdC4g9P2tQ8Mn0Lp5Vk', 'test@example.com', 0, 0, 1);

-- ================================================
-- DATOS INICIALES: PERFIL ADMIN VENDOR
-- ================================================
INSERT INTO `vendors` (`user_id`, `business_name`, `business_description`, `payment_status`, `verification_date`, `status`) VALUES
(1, 'Market-X Admin Store', 'Official Market-X administrator store for featured products', 'verified', NOW(), 'active');

-- ================================================
-- DATOS INICIALES: PERFILES DE USUARIO
-- ================================================
INSERT INTO `user_profiles` (`user_id`, `description`) VALUES
(1, 'System Administrator and Store Manager'),
(2, 'Test user account for marketplace testing');

-- ================================================
-- DATOS INICIALES: PRODUCTOS DE EJEMPLO
-- ================================================
INSERT INTO `products` (`vendor_id`, `name`, `description`, `price`, `stock_quantity`, `category`, `status`) VALUES
(1, 'Premium Digital Security Package', 'Complete digital security solution including VPN, encrypted storage, and secure communications toolkit.', 299.99, 100, 'digital_security', 'active'),
(1, 'Anonymous Web Hosting', 'Secure and anonymous web hosting service with Tor integration and cryptocurrency payments.', 49.99, 50, 'hosting', 'active'),
(1, 'Cryptocurrency Privacy Tools', 'Advanced tools for cryptocurrency privacy including mixers, privacy coins guides, and secure wallets.', 149.99, 25, 'cryptocurrency', 'active');

-- ================================================
-- FINALIZAR SETUP
-- ================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ================================================
-- INFORMACIÓN DE USUARIOS CREADOS
-- ================================================
/*
USUARIOS CREADOS:

1. ADMINISTRADOR:
   - Usuario: admin
   - Contraseña: Admin123!
   - Email: admin@marketx.local  
   - Rol: Super Admin + Vendedor Aprobado

2. USUARIO DE PRUEBA:
   - Usuario: testuser
   - Contraseña: Test123!
   - Email: test@example.com
   - Rol: Usuario normal

CONFIGURACIONES:
- Encriptación: Habilitada
- Comisión por defecto: 5%
- Protección DDoS: Activada
- Dirección Monero: Configurada (ejemplo)
- Límites de seguridad: Configurados

PRODUCTOS EJEMPLO:
- 3 productos digitales de ejemplo
- Categorías: digital_security, hosting, cryptocurrency
- Precios entre $49.99 - $299.99

¡Base de datos lista para usar!
*/