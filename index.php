<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Debugging - Show errors for now
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simple authentication check - redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    // Store intended page for redirect after login
    $_SESSION['redirect_after_login'] = 'index.php';
    header('Location: login.php');
    exit();
}

// Handle User Logout
if (isset($_GET['logout'])) {
    session_destroy();
    $_SESSION['success'] = "Logged out successfully!";
    header('Location: login.php');
    exit();
}

// Get cart count (simple version)
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Fetch All Products with error handling
$products = [];
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username as vendor_name, v.business_name, v.rating_average, v.rating_count,
               COALESCE(v.business_name, u.username) as display_vendor
        FROM products p
        LEFT JOIN vendors v ON p.vendor_id = v.id
        LEFT JOIN users u ON v.user_id = u.id
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to basic products query
    try {
        $stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 20");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        // If even basic query fails, show error
        $_SESSION['error'] = "Error loading products: " . $e2->getMessage();
        $products = [];
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Market-X - Secure Multi-Vendor Marketplace</title>
    <link rel="stylesheet" href="assets/styles.css">
    <meta name="description" content="Market-X - Secure anonymous marketplace with encrypted communications and Monero payments">
    <meta name="keywords" content="marketplace, secure, anonymous, monero, encrypted">
</head>
<body>
    <!-- Booting Screen -->
    <div class="booting-screen">
        <h1>Initializing Market-X...</h1>
        <div class="loading-bar">
            <span></span>
        </div>
        <p>Establishing secure connections...</p>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Main Window -->
        <div class="xp-window">
            <!-- Title Bar -->
            <div class="xp-titlebar">
                <div class="title-content">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="m1 1 4 4 12.5 12.5A2 2 0 0 0 19 18h2a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-5L9 0H2a2 2 0 0 0-2 2v4"/>
                    </svg>
                    <span class="title-text">Market-X • Secure Marketplace</span>
                </div>
                <button class="close-button" onclick="alert('🔒 Secure Session Active')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"/>
                        <path d="m6 6 12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Navigation -->
            <div class="navigation">
                <a href="index.php" class="xp-button">🏠 Home</a>
                <a href="cart.php" class="xp-button">🛒 Cart (<?php echo $cart_count; ?>)</a>
                <a href="orders.php" class="xp-button">📦 Orders</a>
                <?php if (isset($_SESSION['is_vendor']) && $_SESSION['is_vendor']): ?>
                    <a href="vendor_dashboard.php" class="xp-button">🏪 Vendor</a>
                <?php else: ?>
                    <a href="vendor_upgrade.php" class="xp-button">⭐ Become Vendor</a>
                <?php endif; ?>
                <a href="messages.php" class="xp-button">💬 Messages</a>
                <a href="?logout" class="xp-button" style="background: linear-gradient(135deg, #dc3545, #c82333);">🚪 Logout</a>
            </div>
            
            <!-- Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="notification success">
                    <span class="icon">✓</span>
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="notification error">
                    <span class="icon">✗</span>
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['info'])): ?>
                <div class="notification info">
                    <span class="icon">ℹ</span>
                    <?php echo htmlspecialchars($_SESSION['info']); unset($_SESSION['info']); ?>
                </div>
            <?php endif; ?>
            
            <!-- User Dashboard -->
            <div class="user-dashboard">
                <h3>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>! 👋</h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                    <div style="text-align: center;">
                        <h4 style="color: #ff8c42; margin-bottom: 10px;">Quick Access</h4>
                        <p><a href="orders.php">📦 Order History</a></p>
                        <p><a href="cart.php">🛒 Shopping Cart</a></p>
                        <p><a href="user_favorites.php">❤️ Favorites</a></p>
                    </div>
                    
                    <div style="text-align: center;">
                        <h4 style="color: #ff8c42; margin-bottom: 10px;">Communication</h4>
                        <p><a href="messages.php">💬 Messages</a></p>
                        <p><a href="support.php">🎧 Support</a></p>
                    </div>
                    
                    <?php if (isset($_SESSION['is_vendor']) && $_SESSION['is_vendor']): ?>
                        <div style="text-align: center;">
                            <h4 style="color: #ff8c42; margin-bottom: 10px;">Vendor Panel</h4>
                            <p><a href="vendor_dashboard.php">🏪 Dashboard</a></p>
                            <p><a href="vendor_products.php">📦 My Products</a></p>
                            <p><a href="vendor_sales.php">💰 Sales</a></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="?logout" class="xp-button" style="background: linear-gradient(135deg, #dc3545, #c82333);">🚪 Secure Logout</a>
                </div>
            </div>
            
            <!-- Featured Products -->
            <div class="xp-window products-window">
                <div class="xp-titlebar">
                    <div class="title-content">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                        <span>🔥 Featured Products</span>
                    </div>
                </div>
                
                <?php if (empty($products)): ?>
                    <div style="padding: 40px; text-align: center; color: #b8b8b8;">
                        <h3 style="color: #ff6b35;">📦 No Products Available</h3>
                        <p>The marketplace is being prepared. Check back soon for amazing products!</p>
                        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['is_vendor']) && $_SESSION['is_vendor']): ?>
                            <p style="margin-top: 15px;">
                                <a href="vendor_add_product.php" class="xp-button">➕ Add Your Products</a>
                            </p>
                        <?php elseif (isset($_SESSION['user_id'])): ?>
                            <p style="margin-top: 15px;">
                                <a href="vendor_upgrade.php" class="xp-button">🏪 Become a Vendor</a>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="xp-product">
                                <?php if (!empty($product['image']) && file_exists('uploads/' . $product['image'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
                                <?php else: ?>
                                    <div style="width: 100%; height: 180px; background: linear-gradient(145deg, #2a2a2a, #1a1a1a); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                                        <span style="font-size: 48px; opacity: 0.3;">📦</span>
                                    </div>
                                <?php endif; ?>
                                
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                
                                <?php if (isset($product['display_vendor']) && !empty($product['display_vendor'])): ?>
                                    <p style="font-size: 12px; color: #ff8c42; margin-bottom: 8px;">
                                        🏪 <?php echo htmlspecialchars($product['display_vendor']); ?>
                                        <?php if (isset($product['rating_average']) && $product['rating_average'] > 0): ?>
                                            <span style="margin-left: 8px;">
                                                ⭐ <?php echo number_format($product['rating_average'], 1); ?>
                                                (<?php echo $product['rating_count']; ?>)
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="specs"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?><?php echo strlen($product['description']) > 80 ? '...' : ''; ?></p>
                                <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                                
                                <div style="display: flex; gap: 8px; width: 100%;">
                                    <a href="product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="xp-button" style="flex: 1; text-align: center; padding: 10px;">
                                        👁️ View
                                    </a>
                                    <button onclick="addToCart(<?php echo $product['id']; ?>)" class="xp-button" style="padding: 10px;">
                                        🛒
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="footer">
                <p>© 2025 Market-X - Secure Multi-Vendor Marketplace</p>
                <p style="margin-top: 5px; font-size: 12px; opacity: 0.7;">
                    🔒 All communications encrypted • 🕵️ Complete anonymity • 💰 Monero payments accepted
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Loading screen animation
        setTimeout(function() {
            document.querySelector('.booting-screen').style.display = 'none';
            document.querySelector('.container').style.opacity = '1';
        }, 2500);
        
        // Add to cart function
        function addToCart(productId) {
            // This would make an AJAX call to add the product to cart
            alert('🛒 Product added to cart! (Feature in development)');
            console.log('Adding product to cart:', productId);
        }
        
        // Security features
        document.addEventListener('DOMContentLoaded', function() {
            // Disable right-click context menu for security
            document.addEventListener('contextmenu', function(e) {
                e.preventDefault();
            });
            
            // Detect developer tools
            let devtools = {open: false, orientation: null};
            const threshold = 160;
            
            setInterval(function() {
                if (window.innerHeight - window.outerHeight > threshold || 
                    window.innerWidth - window.outerWidth > threshold) {
                    if (!devtools.open) {
                        devtools.open = true;
                        console.clear();
                        console.log('%c🔒 SECURITY WARNING', 'color: #ff6b35; font-size: 20px; font-weight: bold;');
                        console.log('%cThis marketplace uses advanced security measures. Unauthorized access attempts are logged.', 'color: #ff8c42; font-size: 14px;');
                    }
                } else {
                    devtools.open = false;
                }
            }, 500);
        });
    </script>
</body>
</html>