<?php
/**
 * Fix Database - Agrega columnas faltantes y corrige problemas
 */

require_once 'includes/db.php';

echo "<h1>Arreglando Base de Datos - Market-X</h1>";

try {
    // Verificar si la columna admin_sent_link existe
    $result = $conn->query("DESCRIBE orders");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('admin_sent_link', $columns)) {
        echo "<p>✅ Agregando columna admin_sent_link a la tabla orders...</p>";
        $conn->exec("ALTER TABLE orders ADD COLUMN admin_sent_link TEXT DEFAULT NULL");
        echo "<p>✅ Columna admin_sent_link agregada exitosamente.</p>";
    } else {
        echo "<p>ℹ️ La columna admin_sent_link ya existe.</p>";
    }
    
    // Verificar si la tabla admin_users existe
    try {
        $conn->query("SELECT 1 FROM admin_users LIMIT 1");
        echo "<p>ℹ️ La tabla admin_users ya existe.</p>";
    } catch (PDOException $e) {
        echo "<p>✅ Creando tabla admin_users...</p>";
        $conn->exec("
            CREATE TABLE admin_users (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        echo "<p>✅ Tabla admin_users creada exitosamente.</p>";
        
        // Crear usuario admin por defecto
        $admin_password = password_hash('Admin123!', PASSWORD_DEFAULT);
        $conn->prepare("INSERT INTO admin_users (username, password, email, role, is_active) VALUES (?, ?, ?, 'super_admin', 1)")
             ->execute(['admin', $admin_password, 'admin@marketx.local']);
        echo "<p>✅ Usuario admin creado: <strong>admin / Admin123!</strong></p>";
    }
    
    // Verificar que existe el usuario admin regular también
    $admin_check = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
    $admin_check->execute();
    
    if (!$admin_check->fetch()) {
        echo "<p>✅ Creando usuario admin en tabla users...</p>";
        $admin_password = password_hash('Admin123!', PASSWORD_DEFAULT);
        $conn->prepare("INSERT INTO users (username, password, email, is_vendor, vendor_approved) VALUES (?, ?, ?, 1, 1)")
             ->execute(['admin', $admin_password, 'admin@marketx.local']);
        echo "<p>✅ Usuario admin creado en users también.</p>";
    }
    
    echo "<h2>✅ ¡Base de datos arreglada exitosamente!</h2>";
    echo "<p><strong>Credenciales de Admin:</strong></p>";
    echo "<ul>";
    echo "<li>Usuario: <code>admin</code></li>";
    echo "<li>Contraseña: <code>Admin123!</code></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>