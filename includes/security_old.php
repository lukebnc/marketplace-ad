<?php
/**
 * Enhanced Security Functions for Market-X
 * Includes encryption, input validation, authentication, DDoS protection, and audit logging
 */

class SecurityManager {
    
    // DDoS Protection Configuration
    private static $ddos_max_requests = 100; // Max requests per time window
    private static $ddos_time_window = 3600; // Time window in seconds (1 hour)
    private static $ddos_block_duration = 7200; // Block duration in seconds (2 hours)
    private static $encryption_key = null;
    private static $jwt_secret = null;
    
    /**
     * Initialize encryption keys from database
     */
    public static function init() {
        global $conn;
        
        // Get or create encryption key
        $stmt = $conn->prepare("SELECT value FROM settings WHERE key_name = 'encryption_key'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || empty($result['value'])) {
            // Generate new encryption key
            $key = base64_encode(random_bytes(32));
            $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE key_name = 'encryption_key'");
            $stmt->execute([$key]);
            self::$encryption_key = $key;
        } else {
            self::$encryption_key = $result['value'];
        }
        
        // Get or create JWT secret
        $stmt = $conn->prepare("SELECT value FROM settings WHERE key_name = 'jwt_secret'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || empty($result['value'])) {
            // Generate new JWT secret
            $secret = base64_encode(random_bytes(64));
            $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE key_name = 'jwt_secret'");
            $stmt->execute([$secret]);
            self::$jwt_secret = $secret;
        } else {
            self::$jwt_secret = $result['value'];
        }
    }
    
    /**
     * Encrypt sensitive data using AES-256-GCM
     */
    public static function encrypt($data) {
        if (self::$encryption_key === null) {
            self::init();
        }
        
        $key = base64_decode(self::$encryption_key);
        $iv = random_bytes(12); // 96-bit IV for GCM
        $tag = '';
        
        $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        if ($encrypted === false) {
            throw new Exception('Encryption failed');
        }
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public static function decrypt($encrypted_data) {
        if (self::$encryption_key === null) {
            self::init();
        }
        
        $key = base64_decode(self::$encryption_key);
        $data = base64_decode($encrypted_data);
        
        if (strlen($data) < 28) { // 12 bytes IV + 16 bytes tag
            return false;
        }
        
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);
        
        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        return $decrypted !== false ? $decrypted : null;
    }
    
    /**
     * Hash password with strong algorithm
     */
    public static function hashPassword($password) {
        // Use ARGON2ID for maximum security
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }
    
    /**
     * Verify password hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Sanitize and validate input
     */
    public static function sanitizeInput($input, $type = 'string') {
        if ($input === null) return null;
        
        // Remove null bytes and trim
        $input = str_replace(chr(0), '', $input);
        $input = trim($input);
        
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
                
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
                
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'filename':
                // Remove dangerous characters for filenames
                return preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
                
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9]/', '', $input);
                
            case 'html':
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
            default:
                return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Validate input based on type and constraints
     */
    public static function validateInput($input, $rules) {
        $errors = [];
        
        // Required field check
        if (isset($rules['required']) && $rules['required'] && empty($input)) {
            $errors[] = 'This field is required';
            return $errors;
        }
        
        if (!empty($input)) {
            // Length validation
            if (isset($rules['min_length']) && strlen($input) < $rules['min_length']) {
                $errors[] = "Minimum length is {$rules['min_length']} characters";
            }
            
            if (isset($rules['max_length']) && strlen($input) > $rules['max_length']) {
                $errors[] = "Maximum length is {$rules['max_length']} characters";
            }
            
            // Type-specific validation
            if (isset($rules['type'])) {
                switch ($rules['type']) {
                    case 'email':
                        if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = 'Invalid email format';
                        }
                        break;
                        
                    case 'password':
                        if (!self::isStrongPassword($input)) {
                            $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, number and special character';
                        }
                        break;
                        
                    case 'username':
                        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $input)) {
                            $errors[] = 'Username must be 3-30 characters, alphanumeric and underscore only';
                        }
                        break;
                        
                    case 'numeric':
                        if (!is_numeric($input)) {
                            $errors[] = 'Must be a valid number';
                        }
                        break;
                        
                    case 'url':
                        if (!filter_var($input, FILTER_VALIDATE_URL)) {
                            $errors[] = 'Invalid URL format';
                        }
                        break;
                }
            }
            
            // Custom regex pattern
            if (isset($rules['pattern']) && !preg_match($rules['pattern'], $input)) {
                $errors[] = $rules['pattern_message'] ?? 'Invalid format';
            }
        }
        
        return $errors;
    }
    
    /**
     * Check if password meets strength requirements
     */
    public static function isStrongPassword($password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special char
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($action, $user_id = null, $success = true, $additional_data = null) {
        global $conn;
        
        $ip = self::getUserIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $conn->prepare("
            INSERT INTO security_audit_log (user_id, action, ip_address, user_agent, additional_data, success) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $additional_json = $additional_data ? json_encode($additional_data) : null;
        $stmt->execute([$user_id, $action, $ip, $user_agent, $additional_json, $success ? 1 : 0]);
    }
    
    /**
     * Get user's real IP address
     */
    public static function getUserIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    /**
     * Check for brute force attacks
     */
    public static function checkBruteForce($username, $max_attempts = 5, $lockout_time = 900) { // 15 minutes
        global $conn;
        
        // Get user login attempts in the last lockout period
        $stmt = $conn->prepare("
            SELECT COUNT(*) as attempts 
            FROM security_audit_log 
            WHERE action = 'login_failed' 
            AND ip_address = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $ip = self::getUserIP();
        $stmt->execute([$ip, $lockout_time]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['attempts'] >= $max_attempts;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Token expires after 1 hour
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Validate and sanitize uploaded file
     */
    public static function validateUploadedFile($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 10485760) {
        $errors = [];
        
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $errors[] = 'No file uploaded';
            return $errors;
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            $errors[] = 'File too large. Maximum size is ' . ($max_size / 1024 / 1024) . 'MB';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types);
        }
        
        // Additional security checks for images
        if (strpos($mime_type, 'image/') === 0) {
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info === false) {
                $errors[] = 'Invalid image file';
            } else {
                // Check image dimensions (max 4K resolution)
                if ($image_info[0] > 4096 || $image_info[1] > 4096) {
                    $errors[] = 'Image too large. Maximum dimensions: 4096x4096';
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Convert uploaded file to secure base64 format
     */
    public static function fileToSecureBase64($file, $max_width = 800, $max_height = 800) {
        if (!file_exists($file['tmp_name'])) {
            throw new Exception('File not found');
        }
        
        $image_info = getimagesize($file['tmp_name']);
        if (!$image_info) {
            throw new Exception('Invalid image file');
        }
        
        // Create image resource based on type
        switch ($image_info['mime']) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $source = imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($file['tmp_name']);
                break;
            default:
                throw new Exception('Unsupported image type');
        }
        
        if (!$source) {
            throw new Exception('Failed to create image resource');
        }
        
        $width = imagesx($source);
        $height = imagesy($source);
        
        // Calculate new dimensions while maintaining aspect ratio
        $new_width = $width;
        $new_height = $height;
        
        if ($width > $max_width || $height > $max_height) {
            $ratio = min($max_width / $width, $max_height / $height);
            $new_width = intval($width * $ratio);
            $new_height = intval($height * $ratio);
        }
        
        // Create new image with calculated dimensions
        $target = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG and GIF
        if ($image_info['mime'] === 'image/png' || $image_info['mime'] === 'image/gif') {
            imagealphablending($target, false);
            imagesavealpha($target, true);
        }
        
        // Resize image
        imagecopyresampled($target, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Convert to base64
        ob_start();
        switch ($image_info['mime']) {
            case 'image/jpeg':
                imagejpeg($target, null, 90);
                break;
            case 'image/png':
                imagepng($target);
                break;
            case 'image/gif':
                imagegif($target);
                break;
        }
        $image_data = ob_get_contents();
        ob_end_clean();
        
        // Clean up memory
        imagedestroy($source);
        imagedestroy($target);
        
        // Return encrypted base64 data
        $base64 = base64_encode($image_data);
        return 'data:' . $image_info['mime'] . ';base64,' . $base64;
    }
}

/**
 * Authentication and Session Management
 */
class AuthManager {
    
    /**
     * Authenticate user with enhanced security
     */
    public static function authenticate($username, $password) {
        global $conn;
        
        // Check for brute force
        if (SecurityManager::checkBruteForce($username)) {
            SecurityManager::logSecurityEvent('login_blocked_brute_force', null, false, ['username' => $username]);
            throw new Exception('Too many failed attempts. Please try again later.');
        }
        
        // Get user data
        $stmt = $conn->prepare("
            SELECT id, username, password, email, is_vendor, vendor_approved, 
                   account_locked, login_attempts, last_login 
            FROM users 
            WHERE username = ? OR email = ?
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            SecurityManager::logSecurityEvent('login_failed', null, false, ['username' => $username, 'reason' => 'user_not_found']);
            throw new Exception('Invalid credentials');
        }
        
        // Check if account is locked
        if ($user['account_locked']) {
            SecurityManager::logSecurityEvent('login_failed', $user['id'], false, ['reason' => 'account_locked']);
            throw new Exception('Account is locked. Please contact support.');
        }
        
        // Verify password
        if (!SecurityManager::verifyPassword($password, $user['password'])) {
            // Increment login attempts
            $stmt = $conn->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            SecurityManager::logSecurityEvent('login_failed', $user['id'], false, ['reason' => 'invalid_password']);
            throw new Exception('Invalid credentials');
        }
        
        // Successful login - reset attempts and update last login
        $stmt = $conn->prepare("
            UPDATE users 
            SET login_attempts = 0, last_login = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        // Create secure session
        self::createSecureSession($user);
        
        SecurityManager::logSecurityEvent('login_success', $user['id'], true);
        
        return $user;
    }
    
    /**
     * Create secure session with regenerated ID
     */
    private static function createSecureSession($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_vendor'] = $user['is_vendor'];
        $_SESSION['vendor_approved'] = $user['vendor_approved'];
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = SecurityManager::getUserIP();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Set secure session cookie parameters
        $session_name = session_name();
        $session_id = session_id();
        
        setcookie($session_name, $session_id, [
            'expires' => time() + 86400, // 24 hours
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']), // Only over HTTPS in production
            'httponly' => true, // Prevent XSS
            'samesite' => 'Strict' // CSRF protection
        ]);
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout (24 hours)
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > 86400) {
            self::logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Check if user is vendor
     */
    public static function isVendor() {
        return self::isAuthenticated() && 
               isset($_SESSION['is_vendor']) && 
               $_SESSION['is_vendor'] && 
               isset($_SESSION['vendor_approved']) && 
               $_SESSION['vendor_approved'];
    }
    
    /**
     * Require authentication (redirect if not authenticated)
     */
    public static function requireAuth($redirect_url = 'index.php') {
        if (!self::isAuthenticated()) {
            $_SESSION['error'] = 'Please login to access this page.';
            header("Location: $redirect_url");
            exit();
        }
    }
    
    /**
     * Require vendor status
     */
    public static function requireVendor($redirect_url = 'vendor_upgrade.php') {
        self::requireAuth();
        
        if (!self::isVendor()) {
            $_SESSION['error'] = 'Vendor access required.';
            header("Location: $redirect_url");
            exit();
        }
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            SecurityManager::logSecurityEvent('user_logout', $_SESSION['user_id'], true);
            self::clearRememberMe($_SESSION['user_id']);
        }
        
        // Clear user session variables
        $user_keys = ['user_id', 'username', 'email', 'is_vendor', 'vendor_approved', 
                     'last_activity', 'ip_address', 'user_agent'];
        
        foreach ($user_keys as $key) {
            unset($_SESSION[$key]);
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
    }
    
    /**
     * Remember me functionality
     */
    public static function setRememberMe($user_id, $token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        global $conn;
        
        // Generate secure token hash
        $hashed_token = SecurityManager::hashPassword($token);
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Store in database
        $stmt = $conn->prepare("
            UPDATE users 
            SET remember_token = ?, remember_expires = ? 
            WHERE id = ?
        ");
        $stmt->execute([$hashed_token, $expires, $user_id]);
        
        // Set cookie (30 days)
        setcookie('remember_token', $token, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    /**
     * Clear remember me
     */
    public static function clearRememberMe($user_id = null) {
        if ($user_id) {
            global $conn;
            $stmt = $conn->prepare("UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?");
            $stmt->execute([$user_id]);
        }
        
        // Clear cookie
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    /**
     * Get current user ID from session
     */
    public static function getCurrentUserId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user data
     */
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        global $conn;
        
        $stmt = $conn->prepare("
            SELECT u.*, up.profile_image, up.description, up.phone 
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['profile_image']) {
            $user['profile_image'] = SecurityManager::decrypt($user['profile_image']);
        }
        if ($user && $user['description']) {
            $user['description'] = SecurityManager::decrypt($user['description']);
        }
        if ($user && $user['phone']) {
            $user['phone'] = SecurityManager::decrypt($user['phone']);
        }
        
        return $user;
    }
    
    /**
     * DDoS Protection - Check if IP should be blocked
     */
    public static function checkDDoSProtection($ip_address = null) {
        if (!$ip_address) {
            $ip_address = self::getUserIP();
        }
        
        global $conn;
        
        try {
            // Get current settings
            $stmt = $conn->prepare("SELECT value FROM settings WHERE key_name = ?");
            
            $stmt->execute(['ddos_max_requests']);
            $max_requests = $stmt->fetch(PDO::FETCH_ASSOC)['value'] ?? self::$ddos_max_requests;
            
            $stmt->execute(['ddos_time_window']);
            $time_window = $stmt->fetch(PDO::FETCH_ASSOC)['value'] ?? self::$ddos_time_window;
            
            // Check if IP is currently blocked
            $stmt = $conn->prepare("
                SELECT blocked_until FROM ddos_protection 
                WHERE ip_address = ? AND blocked_until > NOW()
            ");
            $stmt->execute([$ip_address]);
            $blocked = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($blocked) {
                self::logSecurityEvent('ddos_blocked_request', null, false, [
                    'ip' => $ip_address,
                    'blocked_until' => $blocked['blocked_until']
                ]);
                return false; // IP is blocked
            }
            
            // Check request count in time window
            $stmt = $conn->prepare("
                SELECT request_count, first_request, last_request 
                FROM ddos_protection 
                WHERE ip_address = ?
            ");
            $stmt->execute([$ip_address]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $now = time();
            
            if (!$record) {
                // First request from this IP
                $stmt = $conn->prepare("
                    INSERT INTO ddos_protection (ip_address, request_count, first_request, last_request) 
                    VALUES (?, 1, FROM_UNIXTIME(?), FROM_UNIXTIME(?))
                ");
                $stmt->execute([$ip_address, $now, $now]);
                return true;
            }
            
            $first_request = strtotime($record['first_request']);
            $time_elapsed = $now - $first_request;
            
            if ($time_elapsed > $time_window) {
                // Reset counter - time window has passed
                $stmt = $conn->prepare("
                    UPDATE ddos_protection 
                    SET request_count = 1, first_request = FROM_UNIXTIME(?), last_request = FROM_UNIXTIME(?)
                    WHERE ip_address = ?
                ");
                $stmt->execute([$now, $now, $ip_address]);
                return true;
            }
            
            // Update request count
            $new_count = $record['request_count'] + 1;
            $stmt = $conn->prepare("
                UPDATE ddos_protection 
                SET request_count = ?, last_request = FROM_UNIXTIME(?)
                WHERE ip_address = ?
            ");
            $stmt->execute([$new_count, $now, $ip_address]);
            
            // Check if limit exceeded
            if ($new_count > $max_requests) {
                // Block the IP
                $block_until = $now + self::$ddos_block_duration;
                $stmt = $conn->prepare("
                    UPDATE ddos_protection 
                    SET blocked_until = FROM_UNIXTIME(?)
                    WHERE ip_address = ?
                ");
                $stmt->execute([$block_until, $ip_address]);
                
                self::logSecurityEvent('ddos_ip_blocked', null, true, [
                    'ip' => $ip_address,
                    'request_count' => $new_count,
                    'time_window' => $time_window,
                    'blocked_until' => date('Y-m-d H:i:s', $block_until)
                ]);
                
                return false; // Block request
            }
            
            return true; // Allow request
            
        } catch (Exception $e) {
            // Log error but don't block on database errors
            error_log("DDoS Protection Error: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Advanced rate limiting per user action
     */
    public static function checkRateLimit($action, $user_id = null, $max_attempts = 10, $time_window = 300) {
        if (!$user_id) {
            $user_id = self::getCurrentUserId() ?? 0;
        }
        
        $cache_key = "rate_limit_{$action}_{$user_id}_" . self::getUserIP();
        
        // Use simple file-based caching for rate limiting
        $cache_file = sys_get_temp_dir() . '/marketx_rate_' . md5($cache_key) . '.tmp';
        
        $now = time();
        $attempts = [];
        
        if (file_exists($cache_file)) {
            $data = file_get_contents($cache_file);
            $attempts = json_decode($data, true) ?: [];
        }
        
        // Clean old attempts
        $attempts = array_filter($attempts, function($timestamp) use ($now, $time_window) {
            return ($now - $timestamp) < $time_window;
        });
        
        // Check if limit exceeded
        if (count($attempts) >= $max_attempts) {
            self::logSecurityEvent('rate_limit_exceeded', $user_id, false, [
                'action' => $action,
                'attempts' => count($attempts),
                'max_attempts' => $max_attempts,
                'time_window' => $time_window
            ]);
            return false;
        }
        
        // Add current attempt
        $attempts[] = $now;
        file_put_contents($cache_file, json_encode($attempts));
        
        return true;
    }
    
    /**
     * Block suspicious IP addresses
     */
    public static function blockSuspiciousIP($ip_address, $duration = 3600, $reason = 'Suspicious activity') {
        global $conn;
        
        try {
            $block_until = time() + $duration;
            
            $stmt = $conn->prepare("
                INSERT INTO ddos_protection (ip_address, request_count, blocked_until) 
                VALUES (?, 0, FROM_UNIXTIME(?))
                ON DUPLICATE KEY UPDATE blocked_until = FROM_UNIXTIME(?)
            ");
            $stmt->execute([$ip_address, $block_until, $block_until]);
            
            self::logSecurityEvent('ip_manually_blocked', null, true, [
                'ip' => $ip_address,
                'reason' => $reason,
                'duration' => $duration,
                'blocked_until' => date('Y-m-d H:i:s', $block_until)
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error blocking IP: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Advanced brute force protection
     */
    public static function checkBruteForceProtection($username, $action = 'login') {
        if (!self::checkRateLimit("{$action}_{$username}", null, 5, 900)) { // 5 attempts per 15 minutes
            return false;
        }
        
        // Additional IP-based protection
        if (!self::checkRateLimit("{$action}_ip", null, 10, 600)) { // 10 attempts per 10 minutes per IP
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate secure session ID
     */
    public static function generateSecureSessionId() {
        if (session_status() == PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        
        $secure_id = bin2hex(random_bytes(32));
        return $secure_id;
    }
    
    /**
     * Advanced CSRF token with expiration
     */
    public static function generateAdvancedCSRFToken($expiry = 3600) {
        $token = self::generateToken(32);
        $expires = time() + $expiry;
        $payload = base64_encode(json_encode(['token' => $token, 'expires' => $expires]));
        $_SESSION['csrf_tokens'][$token] = $expires;
        
        // Clean expired tokens
        if (isset($_SESSION['csrf_tokens'])) {
            foreach ($_SESSION['csrf_tokens'] as $old_token => $expire_time) {
                if (time() > $expire_time) {
                    unset($_SESSION['csrf_tokens'][$old_token]);
                }
            }
        }
        
        return $payload;
    }
    
    /**
     * Verify advanced CSRF token
     */
    public static function verifyAdvancedCSRFToken($payload) {
        try {
            $data = json_decode(base64_decode($payload), true);
            if (!$data || !isset($data['token']) || !isset($data['expires'])) {
                return false;
            }
            
            $token = $data['token'];
            $expires = $data['expires'];
            
            if (time() > $expires) {
                return false; // Token expired
            }
            
            if (!isset($_SESSION['csrf_tokens'][$token])) {
                return false; // Token not found in session
            }
            
            if ($_SESSION['csrf_tokens'][$token] != $expires) {
                return false; // Token mismatch
            }
            
            // Remove used token
            unset($_SESSION['csrf_tokens'][$token]);
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Initialize security manager
SecurityManager::init();

?>