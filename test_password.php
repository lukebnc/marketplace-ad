<?php
require_once 'includes/db.php';

// Get the stored password hash for testuser
$stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
$stmt->execute(['testuser']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Stored hash: " . $user['password'] . "\n";
    
    // Test different passwords
    $passwords_to_test = ['Test123!', 'password', 'testuser', 'admin123'];
    
    foreach ($passwords_to_test as $password) {
        $result = password_verify($password, $user['password']);
        echo "Password '$password': " . ($result ? "MATCH" : "NO MATCH") . "\n";
    }
} else {
    echo "User not found\n";
}
?>