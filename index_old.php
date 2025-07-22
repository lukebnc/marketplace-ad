<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Handle User Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    // Fetch user from the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['success'] = "Login successful!";
    } else {
        // Invalid credentials
        $_SESSION['error'] = "Invalid username or password.";
    }
    redirect('index.php');
}

// Handle User Logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('index.php');
}

// Fetch All Products
$stmt = $conn->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Market-X</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <!-- Booting Screen -->
    <div class="booting-screen">
        <h1>Booting Market-X...</h1>
        <div class="loading-bar">
            <span></span>
        </div>
        <p>Please wait while the system initializes...</p>
    </div>
    <!-- Main Content -->
    <div class="container">
        <!-- Main Window -->
        <div class="xp-window">
            <!-- Title Bar -->
            <div class="xp-titlebar">
                <div class="title-content">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1"/>
                        <circle cx="19" cy="21" r="1"/>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>
                    </svg>
                    <span class="title-text">Market-X</span>
                </div>
                <button class="close-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>
            <!-- Content -->
            <div class="content">
                <!-- Navigation -->
                <div class="navigation">
                    <a href="index.php" class="xp-button">Home</a>
                    <!-- Removed "Products" button -->
                    <a href="cart.php" class="xp-button">Cart (0)</a>
                </div>
                <!-- Notifications -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="notification success">
                        <span class="icon">&#10004;</span>
                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="notification error">
                        <span class="icon">&#10008;</span>
                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <!-- User Dashboard -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-dashboard">
                        <h3>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h3>
                        <!-- Removed "Manage Account" link -->
                        <p><strong>Orders:</strong> <a href="orders.php">View Order History</a></p>
                        <p><strong>Cart:</strong> <a href="cart.php">View Cart</a></p>
                        <a href="?logout" class="xp-button">Logout</a>
                    </div>
                <?php else: ?>
                    <!-- Login Form -->
                    <div class="login-form">
                        <h3>Login</h3>
                        <form method="POST" action="">
                            <label for="username">Username:</label>
                            <input type="text" name="username" id="username" required>
                            <label for="password">Password:</label>
                            <input type="password" name="password" id="password" required>
                            <button type="submit" name="login" class="xp-button">Login</button>
                        </form>
                        <p>Don't have an account? <a href="register.php">Register here</a>.</p>
                    </div>
                <?php endif; ?>
                <!-- Featured Products -->
                <div class="xp-window products-window">
                    <div class="xp-titlebar">
                        <div class="title-content">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                <line x1="8" y1="21" x2="16" y2="21"/>
                                <line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                            <span>Featured Products</span>
                        </div>
                    </div>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="xp-product">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="specs"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p class="price">$<?php echo htmlspecialchars($product['price']); ?></p>
                                <a href="product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="xp-button">View Product</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Footer -->
                <div class="footer">
                    <p>© 2025 Market-X - KEY TO O</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>