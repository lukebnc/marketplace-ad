<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    echo "<div style='background: #000; color: #fff; padding: 20px;'>";
    echo "<h1>🔍 DEBUG LOGIN PROCESS</h1>";
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        echo "<p style='color: red;'>❌ TOKEN DE SEGURIDAD INVÁLIDO</p>";
        echo "<p>Token recibido: $csrf_token</p>";
        echo "<p>Token en sesión: " . ($_SESSION['csrf_token'] ?? 'No existe') . "</p>";
    } else {
        echo "<p style='color: green;'>✅ CSRF Token válido</p>";
    }
    
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'] ?? '';
    
    echo "<p><strong>Usuario:</strong> $username</p>";
    echo "<p><strong>Contraseña:</strong> " . str_repeat('*', strlen($password)) . "</p>";

    try {
        // Authenticate admin
        $admin = authenticateAdmin($username, $password);
        
        if ($admin) {
            echo "<p style='color: green;'>✅ AUTENTICACIÓN EXITOSA</p>";
            echo "<p>Redirigiendo a index.php...</p>";
            
            $_SESSION['success'] = "Bienvenido al panel de administración, " . htmlspecialchars($admin['username']) . "!";
            
            // Manual redirect with JavaScript
            echo "<script>";
            echo "setTimeout(function() { window.location.href = 'index.php'; }, 2000);";
            echo "</script>";
            
        } else {
            echo "<p style='color: red;'>❌ CREDENCIALES INCORRECTAS</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
    exit;
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Admin Login</title>
    <style>
        body { background: #000; color: #fff; font-family: Arial; padding: 20px; }
        form { max-width: 400px; margin: 20px 0; }
        input { width: 100%; padding: 10px; margin: 10px 0; }
        button { background: #ff6b35; color: white; padding: 15px; border: none; width: 100%; cursor: pointer; }
    </style>
</head>
<body>
    <h1>🧪 Test Login Admin Direct</h1>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <p><label>Usuario:</label></p>
        <input type="text" name="username" value="admin" required>
        
        <p><label>Contraseña:</label></p>
        <input type="password" name="password" value="Admin123!" required>
        
        <button type="submit" name="test_login">🚀 TEST LOGIN</button>
    </form>
    
    <p><a href="admin/login.php" style="color: #ff6b35;">← Volver al login normal</a></p>
</body>
</html>