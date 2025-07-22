<?php
/**
 * Test Admin Authentication
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<h1>🔍 Test de Autenticación Admin</h1>";

$username = 'admin';
$password = 'Admin123!';

echo "<p><strong>Probando credenciales:</strong></p>";
echo "<p>Usuario: $username</p>";
echo "<p>Contraseña: $password</p>";

echo "<h2>1. Verificar Usuario en Base de Datos:</h2>";

try {
    // Verificar en tabla admin_users
    $stmt = $conn->prepare("SELECT id, username, password, email, role, is_active FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin_user) {
        echo "<p>✅ Usuario encontrado en admin_users:</p>";
        echo "<pre>" . print_r($admin_user, true) . "</pre>";
        
        // Verificar contraseña
        if (password_verify($password, $admin_user['password'])) {
            echo "<p style='color: green;'>✅ Contraseña CORRECTA en admin_users</p>";
        } else {
            echo "<p style='color: red;'>❌ Contraseña INCORRECTA en admin_users</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Usuario NO encontrado en admin_users</p>";
    }
    
    // Verificar en tabla users
    $stmt = $conn->prepare("SELECT id, username, password, email FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $regular_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($regular_user) {
        echo "<p>✅ Usuario encontrado en users:</p>";
        echo "<pre>" . print_r($regular_user, true) . "</pre>";
        
        // Verificar contraseña
        if (password_verify($password, $regular_user['password'])) {
            echo "<p style='color: green;'>✅ Contraseña CORRECTA en users</p>";
        } else {
            echo "<p style='color: red;'>❌ Contraseña INCORRECTA en users</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Usuario NO encontrado en users</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Probar función authenticateAdmin():</h2>";

try {
    $result = authenticateAdmin($username, $password);
    
    if ($result) {
        echo "<p style='color: green;'>✅ authenticateAdmin() devolvió TRUE</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ authenticateAdmin() devolvió FALSE</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error en authenticateAdmin(): " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificar Sesión:</h2>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session variables:</p>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>4. Test isAdminLoggedIn():</h2>";
if (isAdminLoggedIn()) {
    echo "<p style='color: green;'>✅ isAdminLoggedIn() = TRUE</p>";
} else {
    echo "<p style='color: red;'>❌ isAdminLoggedIn() = FALSE</p>";
}
?>