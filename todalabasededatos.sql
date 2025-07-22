-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-07-2025 a las 10:45:24
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `ecommerce_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `role`, `is_active`, `last_login`, `failed_login_attempts`, `created_at`, `updated_at`) VALUES
(4, 'admin', '$2y$10$DYtZYI/0UXBpS6BIqEpnXe106UjYcDUbUDQWHc13Yuqq8eNdkqA82', 'admin@marketx.local', 'super_admin', 1, NULL, 0, '2025-07-21 19:23:02', '2025-07-21 19:23:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ddos_protection`
--

CREATE TABLE `ddos_protection` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `request_count` int(11) DEFAULT 1,
  `blocked_until` timestamp NULL DEFAULT NULL,
  `first_request` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_request` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `message_content` text NOT NULL,
  `message_type` enum('text','image','file') DEFAULT 'text',
  `is_read` tinyint(1) DEFAULT 0,
  `is_encrypted` tinyint(1) DEFAULT 1,
  `encryption_key_hash` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` text NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admin_sent_link` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `vendor_id`, `name`, `description`, `price`, `stock_quantity`, `category`, `image`, `status`, `views_count`, `sales_count`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Premium Digital Security Package', 'Complete digital security solution including VPN, encrypted storage, and secure communications toolkit.', 299.99, 100, 'digital_security', NULL, 'active', 0, 0, '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(2, NULL, 'Anonymous Web Hosting', 'Secure and anonymous web hosting service with Tor integration and cryptocurrency payments.', 49.99, 50, 'hosting', NULL, 'active', 0, 0, '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(3, NULL, 'Cryptocurrency Privacy Tools', 'Advanced tools for cryptocurrency privacy including mixers, privacy coins guides, and secure wallets.', 149.99, 25, 'cryptocurrency', NULL, 'active', 0, 0, '2025-07-21 17:48:24', '2025-07-21 17:48:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_title` varchar(255) NOT NULL,
  `review_content` text NOT NULL,
  `vendor_response` text DEFAULT NULL,
  `vendor_response_date` timestamp NULL DEFAULT NULL,
  `is_verified_purchase` tinyint(1) DEFAULT 0,
  `helpful_votes` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `security_audit_log`
--

CREATE TABLE `security_audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`)),
  `success` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text NOT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`, `is_encrypted`, `description`, `created_at`, `updated_at`) VALUES
(1, 'vendor_fee', '100.00', 0, 'Fee to become a vendor in USD', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(2, 'commission_rate', '5.0', 0, 'Default commission rate percentage', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(3, 'max_file_size', '10485760', 0, 'Maximum file upload size in bytes (10MB)', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(4, 'encryption_key', 'U2VjdXJlS2V5Rm9yTWFya2V0WEVuY3J5cHRpb24yMDI1', 1, 'Master encryption key for sensitive data', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(5, 'jwt_secret', 'U3VwZXJTZWN1cmVKV1RTZWNyZXRGb3JNYXJrZXRYMjAyNQ==', 1, 'JWT secret for secure tokens', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(6, 'email_verification_required', '0', 0, 'Require email verification', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(7, 'two_factor_enabled', '0', 0, 'Enable two-factor authentication', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(8, 'marketplace_title', 'Market-X', 0, 'Marketplace title', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(9, 'marketplace_description', 'Secure Multi-Vendor Marketplace', 0, 'Marketplace description', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(10, 'maintenance_mode', '0', 0, 'Maintenance mode enabled', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(11, 'store_name', 'Market-X', 0, 'Store display name', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(12, 'xmr_address', '4AdUndXHHZ6cfufTMvppY6JwXNouMBzSkbLYfpAV5Usx3skxNgYeYTRj5UzqtReoS44qo9mtmXCqY45DJ852K5Jv2684Rge', 0, 'Monero payment address', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(13, 'max_login_attempts', '5', 0, 'Maximum login attempts before lockout', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(14, 'ddos_max_requests', '100', 0, 'Maximum requests per time window', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(15, 'ddos_time_window', '3600', 0, 'DDoS protection time window in seconds', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(16, 'encryption_enabled', '1', 0, 'Enable encryption features', '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(17, 'site_name', 'Market-X', 0, NULL, '2025-07-21 19:04:09', '2025-07-21 19:04:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `email_verified`, `is_vendor`, `vendor_approved`, `vendor_payment_id`, `last_login`, `login_attempts`, `account_locked`, `two_factor_secret`, `password_reset_token`, `password_reset_expires`, `remember_token`, `remember_expires`, `created_at`, `updated_at`) VALUES
(2, 'testuser', '$argon2id$v=19$m=65536,t=4,p=3$YklHQm5aeUt4VVJjckFVZw$8xMvNa6fLm9kWp3rYzQ+F7qLJdC4g9P2tQ8Mn0Lp5Vk', 'test@example.com', 1, 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, '2025-07-21 17:48:24', '2025-07-21 17:48:24'),
(3, 'poli', '$2y$10$so9C0oyXdvJ7CYLJ9JkFveFXtEkgQsdqOXzQoFR5WJK42018iRKAm', 'tes@test.com', 0, 1, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, '2025-07-21 17:48:55', '2025-07-21 18:28:36'),
(4, 'juan', '$2y$10$5LsnvNDEJ/n9Du8q0Gz0ieoxx0hTJqrra9DoYvZLUnpylgpsNWIt6', 'dawd@dwda.cpm', 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, '2025-07-21 18:42:24', '2025-07-21 18:42:24'),
(6, 'prueba', '$2y$10$1l8GHSK2IJ3b8tWsvwe2fe72QQNggfE9jnALdiJfOahVrmsv7AEpC', 'hola@gmail.com', 0, 0, 0, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, '2025-07-21 19:07:13', '2025-07-21 19:07:13'),
(8, 'admin', '$2y$10$DYtZYI/0UXBpS6BIqEpnXe106UjYcDUbUDQWHc13Yuqq8eNdkqA82', 'admin@marketx.local', 0, 1, 1, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, '2025-07-21 19:23:02', '2025-07-21 19:23:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_favorites`
--

CREATE TABLE `user_favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_profiles`
--

CREATE TABLE `user_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_image` longtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `user_profiles`
--

INSERT INTO `user_profiles` (`id`, `user_id`, `profile_image`, `description`, `phone`, `address`, `birth_date`, `created_at`, `updated_at`) VALUES
(2, 2, NULL, 'Test user account for marketplace testing', NULL, NULL, NULL, '2025-07-21 17:48:24', '2025-07-21 17:48:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `vendors`
--

INSERT INTO `vendors` (`id`, `user_id`, `business_name`, `business_description`, `payment_status`, `payment_amount`, `payment_txid`, `payment_address`, `payment_date`, `verification_date`, `commission_rate`, `total_sales`, `total_products`, `rating_average`, `rating_count`, `status`, `created_at`) VALUES
(2, 3, 'bGlvbm55cw==', 'b2pkaWpmb25qZGlqIG9uZm9pamRvaWhnZW9pIG5kb2lud29pc25nb3VmbmJlb2l1c25iIG9pc2pmZG5zb2loZm9pc2ggb2l1ZmVvIG9pdWZod29pZmhlb2lmc2hmb2llZnNm', 'pending', 100.00, NULL, '4AdUndXHHZ6cfufTMvppY6JwXNouMBzSkbLYfpAV5Usx3skxNgYeYTRj5UzqtReoS44qo9mtmXCqY45DJ852K5Jv2684Rge', NULL, NULL, 5.00, 0.00, 0, 0.00, 0, 'pending', '2025-07-21 18:28:36');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `ddos_protection`
--
ALTER TABLE `ddos_protection`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`),
  ADD KEY `idx_blocked_until` (`blocked_until`);

--
-- Indices de la tabla `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender_recipient` (`sender_id`,`recipient_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_messages_recipient` (`recipient_id`),
  ADD KEY `fk_messages_product` (`product_id`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`);

--
-- Indices de la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indices de la tabla `security_audit_log`
--
ALTER TABLE `security_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Indices de la tabla `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_name` (`key_name`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- Indices de la tabla `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `fk_favorites_product` (`product_id`);

--
-- Indices de la tabla `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indices de la tabla `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ddos_protection`
--
ALTER TABLE `ddos_protection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `security_audit_log`
--
ALTER TABLE `security_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_messages_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orders_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `security_audit_log`
--
ALTER TABLE `security_audit_log`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `fk_favorites_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_user_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `vendors`
--
ALTER TABLE `vendors`
  ADD CONSTRAINT `fk_vendors_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
