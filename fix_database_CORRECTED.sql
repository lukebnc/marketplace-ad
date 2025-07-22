-- Script ACTUALIZADO para arreglar la base de datos Market-X
-- Ejecutar este script en phpMyAdmin (REEMPLAZA el anterior)

-- Agregar columna admin_sent_link si no existe
ALTER TABLE orders ADD COLUMN IF NOT EXISTS admin_sent_link TEXT DEFAULT NULL;

-- Crear tabla admin_users si no existe
CREATE TABLE IF NOT EXISTS admin_users (
    id int(11) NOT NULL AUTO_INCREMENT,
    username varchar(50) NOT NULL,
    password varchar(255) NOT NULL,
    email varchar(100) NOT NULL,
    role enum('admin','super_admin') DEFAULT 'admin',
    is_active tinyint(1) DEFAULT 1,
    last_login timestamp NULL DEFAULT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY username (username),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Borrar usuarios admin existentes (para evitar duplicados)
DELETE FROM admin_users WHERE username = 'admin';
DELETE FROM users WHERE username = 'admin';

-- Insertar usuario admin con HASH CORRECTO (contraseña: Admin123!)
INSERT INTO admin_users (username, password, email, role, is_active) VALUES 
('admin', '$2y$10$DYtZYI/0UXBpS6BIqEpnXe106UjYcDUbUDQWHc13Yuqq8eNdkqA82', 'admin@marketx.local', 'super_admin', 1);

-- También agregar admin a tabla users por compatibilidad
INSERT INTO users (username, password, email, is_vendor, vendor_approved) VALUES 
('admin', '$2y$10$DYtZYI/0UXBpS6BIqEpnXe106UjYcDUbUDQWHc13Yuqq8eNdkqA82', 'admin@marketx.local', 1, 1);

-- Verificar que tenemos datos en la tabla settings
INSERT IGNORE INTO settings (key_name, value) VALUES 
('site_name', 'Market-X'),
('vendor_fee', '100.00'),
('commission_rate', '5.00'),
('xmr_address', '4xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');