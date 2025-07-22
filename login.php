<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Show errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Por favor completa todos los campos.";
    } else {
        // Attempt authentication
        $user = authenticateUser($username, $password);
        
        if ($user) {
            $_SESSION['success'] = "¡Bienvenido de vuelta, " . htmlspecialchars($user['username']) . "!";
            
            // Redirect to intended page
            $redirect_url = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect_url);
        } else {
            $_SESSION['error'] = "Usuario o contraseña incorrectos.";
        }
    }
}

// If already logged in, redirect to index
if (isLoggedIn()) {
    redirect('index.php');
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Market-X</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        /* Market-X Consistent Dark Orange Theme */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 25%, #2d1810 50%, #1a1a1a 75%, #0a0a0a 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e5e5e5;
        }
        
        .login-container {
            background: linear-gradient(145deg, #1f1f1f, #2a2a2a);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 107, 53, 0.1);
            width: 100%;
            max-width: 420px;
            position: relative;
            border: 1px solid #3a3a3a;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .logo h1 {
            color: #e5e5e5;
            margin: 0;
            font-size: 2.8em;
            font-weight: 700;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(255, 107, 53, 0.3);
        }
        
        .logo p {
            color: #b8b8b8;
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #e5e5e5;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #3a3a3a;
            border-radius: 10px;
            font-size: 16px;
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            color: #e5e5e5;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 15px rgba(255, 107, 53, 0.2);
            background: linear-gradient(145deg, #1f1f1f, #151515);
        }
        
        .form-group input::placeholder {
            color: #666;
        }
        
        .login-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 25px;
        }
        
        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .links {
            text-align: center;
        }
        
        .links a {
            color: #ff6b35;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .links a:hover {
            color: #ff8c42;
            text-decoration: underline;
        }
        
        .links p {
            color: #b8b8b8;
            margin: 10px 0;
            font-size: 14px;
        }
        
        .notification {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification.success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.15), rgba(76, 175, 80, 0.05));
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .notification.error {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.15), rgba(244, 67, 54, 0.05));
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        .notification .icon {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Market-X</h1>
            <p>Marketplace Multi-Vendedor Seguro</p>
        </div>
        
        <!-- Notifications -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success">
                <span class="icon">✓</span>
                <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="notification error">
                <span class="icon">✗</span>
                <span><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" 
                       name="username" 
                       id="username" 
                       placeholder="Ingresa tu nombre de usuario"
                       required 
                       autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       placeholder="Ingresa tu contraseña"
                       required 
                       autocomplete="current-password">
            </div>
            
            <button type="submit" name="login" class="login-button">
                🔐 Iniciar Sesión
            </button>
        </form>
        
        <div class="links">
            <p>¿No tienes cuenta? <a href="register.php">Crear cuenta aquí</a></p>
            <p><a href="admin/login.php">Panel de Administración</a></p>
        </div>
    </div>
</body>
</html>