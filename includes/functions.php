<?php
/**
 * FUNCTIONS.PHP - VERSIÓN ULTRA SIMPLE
 * Sin clases complejas, solo funciones básicas PHP
 */

// Simple redirect function
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Simple input sanitization
function sanitizeInput($data) {
    if ($data === null) return null;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Simple password hashing
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Simple password verification
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Simple user authentication
function authenticateUser($username, $password) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT id, username, password, email, is_vendor, vendor_approved FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_vendor'] = $user['is_vendor'];
            $_SESSION['vendor_approved'] = $user['vendor_approved'];
            $_SESSION['login_time'] = time();
            
            return $user;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Auth error: " . $e->getMessage());
        return false;
    }
}

// Create a new user
function createUser($username, $password, $email) {
    global $conn;
    
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            return false; // User already exists
        }
        
        // Create user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, created_at) VALUES (?, ?, ?, NOW())");
        
        return $stmt->execute([$username, $hashed_password, $email]);
    } catch (Exception $e) {
        error_log("Create user error: " . $e->getMessage());
        return false;
    }
}

// Logout user
function logoutUser() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

// Simple CSRF token generation
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Simple CSRF token verification
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate password strength
function validatePassword($password) {
    return strlen($password) >= 6; // Simple validation for now
}

// Get user by ID
function getUserById($user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return false;
    }
}

// Simple setting functions
function getSetting($key) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT value FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['value'] : null;
    } catch (Exception $e) {
        return null;
    }
}

function setSetting($key, $value) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        return $stmt->execute([$key, $value, $value]);
    } catch (Exception $e) {
        return false;
    }
}

// Simple admin check
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Simple admin authentication
function authenticateAdmin($username, $password) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            
            return $admin;
        }
        
        return false;
    } catch (Exception $e) {
        // If admin_users table doesn't exist, check regular users for admin
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND username = 'admin'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_login_time'] = time();
                
                return $user;
            }
        } catch (Exception $e2) {
            error_log("Admin auth error: " . $e2->getMessage());
        }
        
        return false;
    }
}

// Simple admin logout
function logoutAdmin() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_login_time']);
}

// Additional helper functions for compatibility

// Encrypt/decrypt functions (simple base64 for now)
function encryptData($data) {
    return base64_encode($data);
}

function decryptData($data) {
    return base64_decode($data);
}

// File upload validation
function validateUploadedFile($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
    $errors = [];
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = "File size must be less than " . ($max_size / 1024 / 1024) . "MB";
    }
    
    // Check file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowed_types);
    }
    
    return $errors;
}

// Generate secure filename
function generateSecureFilename($original_name, $user_id = null) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $hash = hash('sha256', uniqid() . microtime(true) . ($user_id ?: rand()));
    return substr($hash, 0, 16) . '_' . time() . '.' . $extension;
}

// Simple rate limiting (basic implementation)
function checkRateLimit($action, $user_id, $max_attempts, $time_window) {
    // Simple implementation - always return true for now
    // In production, you'd use database or cache to track attempts
    return true;
}

// Log security events (simple file logging)
function logSecurityEvent($event, $user_id, $success, $details = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'user_id' => $user_id,
        'success' => $success,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    error_log("SECURITY EVENT: " . json_encode($log_entry));
}

// Get user IP
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

// Require authentication (redirect if not authenticated)
function requireAuth($redirect_url = 'login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        redirect($redirect_url);
    }
}

// Get current authenticated user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return getUserById($_SESSION['user_id']);
}

?>