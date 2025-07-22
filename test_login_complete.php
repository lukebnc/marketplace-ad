<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<h1>🧪 Test Login Admin Completo</h1>";

// Simular el proceso completo de login
echo "<h2>1. Generar CSRF Token:</h2>";
$csrf_token = generateCSRFToken();
echo "<p>CSRF Token: $csrf_token</p>";

echo "<h2>2. Simular POST Request:</h2>";
$_POST['username'] = 'admin';
$_POST['password'] = 'Admin123!';
$_POST['login'] = '1';
$_POST['csrf_token'] = $csrf_token;

echo "<p>Datos POST simulados:</p>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<h2>3. Verificar CSRF Token:</h2>";
if (verifyCSRFToken($_POST['csrf_token'])) {
    echo "<p style='color: green;'>✅ CSRF Token VÁLIDO</p>";
} else {
    echo "<p style='color: red;'>❌ CSRF Token INVÁLIDO</p>";
}

echo "<h2>4. Ejecutar authenticateAdmin():</h2>";
$username = sanitizeInput($_POST['username']);
$password = $_POST['password'] ?? '';

try {
    $admin = authenticateAdmin($username, $password);
    
    if ($admin) {
        echo "<p style='color: green;'>✅ Autenticación EXITOSA</p>";
        echo "<pre>" . print_r($admin, true) . "</pre>";
        
        echo "<h2>5. Variables de Sesión Después del Login:</h2>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
        
        echo "<h2>6. Test isAdminLoggedIn():</h2>";
        if (isAdminLoggedIn()) {
            echo "<p style='color: green;'>✅ isAdminLoggedIn() = TRUE</p>";
            echo "<p style='color: green;'>🎉 ¡LOGIN COMPLETAMENTE EXITOSO!</p>";
            echo "<p><strong>El usuario debería ser redirigido a index.php</strong></p>";
        } else {
            echo "<p style='color: red;'>❌ isAdminLoggedIn() = FALSE</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Autenticación FALLIDA</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error en authenticateAdmin(): " . $e->getMessage() . "</p>";
}
?>