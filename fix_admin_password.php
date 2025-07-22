<?php
/**
 * Fix Admin Password - Genera el hash correcto y actualiza la base de datos
 */

require_once 'includes/db.php';

echo "<h1>🔧 Arreglando Contraseña de Admin - Market-X</h1>";

try {
    // Generar el hash correcto para Admin123!
    $correct_password = 'Admin123!';
    $correct_hash = password_hash($correct_password, PASSWORD_DEFAULT);
    
    echo "<p><strong>Contraseña:</strong> " . htmlspecialchars($correct_password) . "</p>";
    echo "<p><strong>Hash generado:</strong> " . htmlspecialchars($correct_hash) . "</p>";
    
    // Actualizar en tabla admin_users
    echo "<p>✅ Actualizando contraseña en tabla admin_users...</p>";
    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$correct_hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Contraseña actualizada en admin_users</p>";
    } else {
        // Si no existe, crear el usuario
        echo "<p>➕ Creando usuario admin en admin_users...</p>";
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password, email, role, is_active) VALUES (?, ?, ?, 'super_admin', 1)");
        $stmt->execute(['admin', $correct_hash, 'admin@marketx.local']);
        echo "<p>✅ Usuario admin creado en admin_users</p>";
    }
    
    // Actualizar en tabla users también
    echo "<p>✅ Actualizando contraseña en tabla users...</p>";
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$correct_hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Contraseña actualizada en users</p>";
    } else {
        // Si no existe, crear el usuario
        echo "<p>➕ Creando usuario admin en users...</p>";
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, is_vendor, vendor_approved) VALUES (?, ?, ?, 1, 1)");
        $stmt->execute(['admin', $correct_hash, 'admin@marketx.local']);
        echo "<p>✅ Usuario admin creado en users</p>";
    }
    
    // Verificar la contraseña
    echo "<h2>🔍 Verificación de Contraseña:</h2>";
    $stmt = $conn->prepare("SELECT password FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($correct_password, $admin['password'])) {
        echo "<p style='color: green;'>✅ ¡CONTRASEÑA VERIFICADA CORRECTAMENTE!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: Contraseña no verificada correctamente</p>";
    }
    
    echo "<h2>🎉 ¡LISTO!</h2>";
    echo "<p><strong>Ahora puedes hacer login con:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Usuario:</strong> <code>admin</code></li>";
    echo "<li><strong>Contraseña:</strong> <code>Admin123!</code></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>