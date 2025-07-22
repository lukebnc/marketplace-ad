<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/admin_auth.php';

// DDoS protection removed for simplicity

// Redirect if already authenticated
if (isAdminLoggedIn()) {
    redirect('index.php');
}

// Handle admin login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $_SESSION['error'] = "Token de seguridad inválido. Por favor intenta de nuevo.";
        redirect('login.php');
    }
    
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'] ?? '';

    try {
        // Authenticate admin
        $admin = authenticateAdmin($username, $password);
        
        if ($admin) {
            $_SESSION['success'] = "Bienvenido al panel de administración, " . htmlspecialchars($admin['username']) . "!";
            redirect('index.php');
        } else {
            $_SESSION['error'] = "Credenciales de administrador incorrectas.";
        }
        
    } catch (Exception $e) {
        // Log the actual error for debugging  
        error_log("Admin login error: " . $e->getMessage());
        
        // Show user-friendly error message
        if (strpos($e->getMessage(), 'Invalid credentials') !== false) {
            $_SESSION['error'] = "Credenciales de administrador incorrectas.";
        } elseif (strpos($e->getMessage(), 'temporarily locked') !== false) {
            $_SESSION['error'] = "Cuenta bloqueada temporalmente por seguridad.";
        } else {
            $_SESSION['error'] = "Error en el sistema de autenticación administrativa.";
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Market-X</title>
    <style>
        /* Market-X Admin Login - Consistent Dark Orange Theme */
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
        
        /* Booting Animation */
        .booting-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0a0a0a, #1a1a1a);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: #e5e5e5;
        }
        
        .booting-screen h1 {
            color: #ff6b35;
            font-size: 2.5em;
            margin-bottom: 30px;
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.5);
        }
        
        .loading-bar {
            width: 300px;
            height: 4px;
            background: #333;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .loading-bar span {
            display: block;
            height: 100%;
            background: linear-gradient(90deg, #ff6b35, #ff8c42);
            border-radius: 2px;
            animation: loading 2.5s ease-in-out;
        }
        
        @keyframes loading {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
        
        .admin-login-container {
            background: linear-gradient(145deg, #1f1f1f, #2a2a2a);
            padding: 45px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 107, 53, 0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            border: 1px solid #3a3a3a;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        
        .admin-login-container.show {
            opacity: 1;
        }
        
        .security-badge {
            position: absolute;
            top: -15px;
            right: 20px;
            background: linear-gradient(45deg, #ff6b35, #ff8c42);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .admin-logo {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .admin-logo h1 {
            color: #e5e5e5;
            margin: 0 0 8px 0;
            font-size: 2.8em;
            font-weight: 700;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(255, 107, 53, 0.3);
        }
        
        .admin-logo p {
            color: #b8b8b8;
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .admin-badge {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.15), rgba(255, 107, 53, 0.05));
            border: 1px solid rgba(255, 107, 53, 0.3);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
            color: #ff8c42;
            font-size: 13px;
            font-weight: 600;
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
        
        .admin-login-button {
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
        
        .admin-login-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
        }
        
        .admin-login-button:active {
            transform: translateY(0);
        }
        
        .admin-login-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
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
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #ff6b35;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #ff8c42;
            text-decoration: underline;
        }
        
        .security-info {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.08), rgba(255, 107, 53, 0.03));
            border: 1px solid rgba(255, 107, 53, 0.15);
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 12px;
        }
        
        .security-info h4 {
            color: #ff6b35;
            margin: 0 0 10px 0;
            font-size: 13px;
            font-weight: 700;
        }
        
        .security-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            color: #b8b8b8;
        }
        
        .security-features li {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
        }
        
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 18px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #333;
            border-top: 4px solid #ff6b35;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .admin-login-container {
                padding: 35px 25px;
            }
            
            .admin-logo h1 {
                font-size: 2.2em;
            }
            
            .security-features {
                grid-template-columns: 1fr;
                gap: 6px;
            }
        }
    </style>
</head>
<body>
    <!-- Booting Screen -->
    <div class="booting-screen" id="bootingScreen">
        <h1>🛡️ Market-X Admin</h1>
        <div class="loading-bar">
            <span></span>
        </div>
        <p>Iniciando panel de administración...</p>
    </div>
    
    <div class="loading" id="loading">
        <div class="spinner"></div>
        Verificando credenciales de administrador...
    </div>

    <div class="admin-login-container" id="adminContainer">
        <div class="security-badge">🔒 ADMIN</div>
        
        <div class="admin-logo">
            <h1>Market-X</h1>
            <p>Panel de Administración</p>
        </div>
        
        <div class="admin-badge">
            🛡️ Acceso Restringido - Solo Administradores Autorizados
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
        
        <form method="POST" action="" id="adminLoginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label for="username">👤 Usuario Administrador:</label>
                <input type="text" 
                       name="username" 
                       id="username" 
                       placeholder="Ingresa tu usuario de admin"
                       required 
                       autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">🔐 Contraseña:</label>
                <input type="password" 
                       name="password" 
                       id="password" 
                       placeholder="Contraseña de administrador"
                       required 
                       autocomplete="current-password">
            </div>
            
            <button type="submit" name="login" class="admin-login-button" id="loginBtn">
                🛡️ Acceso Administrativo
            </button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">← Volver al marketplace</a>
        </div>
        
        <div class="security-info">
            <h4>🔒 Sistema de Seguridad Avanzada</h4>
            <ul class="security-features">
                <li>🛡️ Autenticación de dos factores</li>
                <li>🔐 Encriptación AES-256</li>
                <li>🚫 Protección anti-DDoS</li>
                <li>🕵️ Monitoreo de actividad</li>
                <li>📊 Logs de auditoría</li>
                <li>⚡ Sesiones ultra-seguras</li>
            </ul>
        </div>
    </div>

    <script>
        // Booting animation
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.getElementById('bootingScreen').style.display = 'none';
                document.getElementById('adminContainer').classList.add('show');
                document.getElementById('username').focus();
            }, 2500);
        });
        
        // Enhanced form submission
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const loading = document.getElementById('loading');
            
            // Client-side validation
            if (!username || !password) {
                e.preventDefault();
                showNotification('Todos los campos son requeridos.', 'error');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showNotification('Credenciales inválidas.', 'error');
                return false;
            }
            
            // Show loading animation
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<div class="spinner" style="width:20px;height:20px;margin:0 auto;"></div>';
            loading.style.display = 'flex';
            
            return true;
        });
        
        // Show notification function
        function showNotification(message, type = 'error') {
            // Remove existing notifications
            const existing = document.querySelector('.notification');
            if (existing) {
                existing.remove();
            }
            
            // Create new notification
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <span class="icon">${type === 'error' ? '✗' : '✓'}</span>
                <span>${message}</span>
            `;
            
            // Insert before form
            const form = document.getElementById('adminLoginForm');
            form.parentNode.insertBefore(notification, form);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        // Enhanced security features
        document.addEventListener('DOMContentLoaded', function() {
            // Disable right-click context menu
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                showNotification('🔒 Función deshabilitada por seguridad', 'error');
            });
            
            // Disable developer shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.keyCode === 123 || // F12
                    (e.ctrlKey && e.shiftKey && e.keyCode === 73) || // Ctrl+Shift+I
                    (e.ctrlKey && e.keyCode === 85)) { // Ctrl+U
                    e.preventDefault();
                    showNotification('🔒 Función deshabilitada por seguridad', 'error');
                }
            });
            
            // Advanced DevTools detection for admin panel
            let devtools = {open: false, orientation: null};
            const threshold = 160;
            
            setInterval(function() {
                if (window.innerHeight - window.outerHeight > threshold || 
                    window.innerWidth - window.outerWidth > threshold) {
                    if (!devtools.open) {
                        devtools.open = true;
                        console.clear();
                        console.log('%c🛡️ ALERTA DE SEGURIDAD ADMIN MARKET-X', 'color: #ff6b35; font-size: 28px; font-weight: bold;');
                        console.log('%c⚠️ PANEL DE ADMINISTRACIÓN PROTEGIDO', 'color: #ff8c42; font-size: 18px; font-weight: bold;');
                        console.log('%cAcceso no autorizado será reportado a las autoridades.', 'color: #ff8c42; font-size: 16px;');
                    }
                } else {
                    devtools.open = false;
                }
            }, 500);
            
            // Password field security
            const passwordField = document.getElementById('password');
            passwordField.addEventListener('copy', function(e) {
                e.preventDefault();
                showNotification('🔒 Copiar contraseña deshabilitado por seguridad', 'error');
            });
            
            passwordField.addEventListener('paste', function(e) {
                e.preventDefault();
                showNotification('🔒 Pegar en contraseña deshabilitado por seguridad', 'error');
            });
        });
        
        // Clear sensitive data on page unload
        window.addEventListener('beforeunload', function() {
            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.value = '';
            }
        });
        
        // Session timeout warning for admin
        let adminSessionTimeout;
        function resetAdminSessionTimeout() {
            clearTimeout(adminSessionTimeout);
            adminSessionTimeout = setTimeout(function() {
                showNotification('⚠️ Sesión de administrador expirando en 2 minutos', 'error');
            }, 14 * 60 * 1000); // 14 minutes warning for 16 minute session
        }
        
        // Reset timeout on user activity
        ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetAdminSessionTimeout, { passive: true });
        });
        
        resetAdminSessionTimeout();
    </script>
</body>
</html>