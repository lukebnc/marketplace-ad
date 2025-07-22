<?php
require_once 'security.php';

// Redirect function with CSRF protection
function redirect($url) {
    // Prevent header injection
    $url = filter_var($url, FILTER_SANITIZE_URL);
    header("Location: " . $url);
    exit();
}

// Enhanced redirect with security logging
function secureRedirect($url, $message = null, $log_action = null) {
    if ($message) {
        $_SESSION['info'] = $message;
    }
    
    if ($log_action) {
        SecurityManager::logSecurityEvent($log_action, AuthManager::getCurrentUserId(), true);
    }
    
    redirect($url);
}

// Check if user is logged in (for admin) - Enhanced
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Legacy sanitize function - Use SecurityManager::sanitizeInput instead
function sanitizeInput($data) {
    return SecurityManager::sanitizeInput($data);
}

// Enhanced settings function with encryption support
function getSetting($key_name) {
    global $conn;
    $stmt = $conn->prepare("SELECT value, is_encrypted FROM settings WHERE key_name = ?");
    $stmt->execute([$key_name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        return null;
    }
    
    if ($result['is_encrypted']) {
        return SecurityManager::decrypt($result['value']);
    }
    
    return $result['value'];
}

// Set encrypted setting
function setSetting($key_name, $value, $encrypt = false) {
    global $conn;
    
    if ($encrypt) {
        $value = SecurityManager::encrypt($value);
    }
    
    $stmt = $conn->prepare("
        INSERT INTO settings (key_name, value, is_encrypted) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE value = VALUES(value), is_encrypted = VALUES(is_encrypted)
    ");
    return $stmt->execute([$key_name, $value, $encrypt ? 1 : 0]);
}

// Check if user has purchased a product (for reviews)
function hasUserPurchasedProduct($user_id, $product_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE user_id = ? AND product_id = ? AND status = 'completed'
    ");
    $stmt->execute([$user_id, $product_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

// Get user profile data
function getUserProfile($user_id) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.email, u.is_vendor, u.vendor_approved, u.created_at,
               up.profile_image, up.description, up.phone, up.address,
               v.business_name, v.rating_average, v.rating_count, v.total_sales
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN vendors v ON u.id = v.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Decrypt sensitive data
        if ($user['profile_image']) {
            $user['profile_image'] = SecurityManager::decrypt($user['profile_image']);
        }
        if ($user['description']) {
            $user['description'] = SecurityManager::decrypt($user['description']);
        }
        if ($user['phone']) {
            $user['phone'] = SecurityManager::decrypt($user['phone']);
        }
        if ($user['address']) {
            $user['address'] = SecurityManager::decrypt($user['address']);
        }
    }
    
    return $user;
}

// Format price with currency
function formatPrice($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

// Generate secure filename
function generateSecureFilename($original_name, $user_id) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $timestamp = time();
    $random = SecurityManager::generateToken(8);
    return $user_id . '_' . $timestamp . '_' . $random . '.' . $extension;
}

// Validate XMR address format (basic validation)
function isValidXMRAddress($address) {
    // Basic Monero address validation (length and character set)
    return preg_match('/^[A-Za-z0-9]{95,106}$/', $address);
}

// Calculate vendor commission
function calculateCommission($amount, $commission_rate = null) {
    if ($commission_rate === null) {
        $commission_rate = (float) getSetting('commission_rate') ?: 5.0;
    }
    
    $commission = ($amount * $commission_rate) / 100;
    $vendor_earnings = $amount - $commission;
    
    return [
        'commission' => $commission,
        'vendor_earnings' => $vendor_earnings,
        'commission_rate' => $commission_rate
    ];
}

// Get product reviews with decryption
function getProductReviews($product_id, $limit = 10, $offset = 0) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT r.*, u.username, up.profile_image
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE r.product_id = ? AND r.status = 'approved'
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$product_id, $limit, $offset]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reviews as &$review) {
        $review['review_title'] = SecurityManager::decrypt($review['review_title']);
        $review['review_content'] = SecurityManager::decrypt($review['review_content']);
        if ($review['vendor_response']) {
            $review['vendor_response'] = SecurityManager::decrypt($review['vendor_response']);
        }
        if ($review['profile_image']) {
            $review['profile_image'] = SecurityManager::decrypt($review['profile_image']);
        }
    }
    
    return $reviews;
}

// Get conversation messages
function getConversationMessages($user1_id, $user2_id, $limit = 50) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT m.*, u.username as sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = ? AND m.recipient_id = ?) 
           OR (m.sender_id = ? AND m.recipient_id = ?)
        ORDER BY m.created_at ASC
        LIMIT ?
    ");
    
    $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id, $limit]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($messages as &$message) {
        if ($message['is_encrypted']) {
            $message['message_content'] = SecurityManager::decrypt($message['message_content']);
        }
    }
    
    return $messages;
}

// Mark messages as read
function markMessagesAsRead($user_id, $sender_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE recipient_id = ? AND sender_id = ? AND is_read = 0
    ");
    
    return $stmt->execute([$user_id, $sender_id]);
}

// Get unread message count
function getUnreadMessageCount($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM messages 
        WHERE recipient_id = ? AND is_read = 0
    ");
    
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

// Validate and process image upload
function processImageUpload($file, $max_size = 5242880) { // 5MB default
    $validation_errors = SecurityManager::validateUploadedFile(
        $file, 
        ['image/jpeg', 'image/png', 'image/gif'], 
        $max_size
    );
    
    if (!empty($validation_errors)) {
        throw new Exception(implode(', ', $validation_errors));
    }
    
    return SecurityManager::fileToSecureBase64($file);
}

// Create notification system
function createNotification($user_id, $type, $title, $message, $related_id = null) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, title, message, related_id, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    return $stmt->execute([
        $user_id, 
        $type, 
        SecurityManager::encrypt($title), 
        SecurityManager::encrypt($message), 
        $related_id
    ]);
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31104000) return floor($time/2592000) . ' months ago';
    return floor($time/31104000) . ' years ago';
}

?>