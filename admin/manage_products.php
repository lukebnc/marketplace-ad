<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check admin authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Enhanced product management with vendor integration
$action = $_GET['action'] ?? 'list';
$product_id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_product'])) {
        $product_id = $_POST['product_id'];
        try {
            $stmt = $conn->prepare("UPDATE products SET status = 'active' WHERE id = ?");
            if ($stmt->execute([$product_id])) {
                $message = "Product approved successfully!";
                // Update vendor product count
                $conn->exec("UPDATE vendors v 
                           JOIN products p ON p.vendor_id = v.id 
                           SET v.total_products = (SELECT COUNT(*) FROM products WHERE vendor_id = v.id AND status = 'active') 
                           WHERE p.id = $product_id");
            }
        } catch (Exception $e) {
            $error = "Error approving product: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['reject_product'])) {
        $product_id = $_POST['product_id'];
        try {
            $stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
            if ($stmt->execute([$product_id])) {
                $message = "Product rejected successfully!";
            }
        } catch (Exception $e) {
            $error = "Error rejecting product: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['delete_product'])) {
        $product_id = $_POST['product_id'];
        try {
            // Delete associated image file if exists
            $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product && !empty($product['image']) && file_exists("../uploads/" . $product['image'])) {
                unlink("../uploads/" . $product['image']);
            }
            
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt->execute([$product_id])) {
                $message = "Product deleted successfully!";
            }
        } catch (Exception $e) {
            $error = "Error deleting product: " . $e->getMessage();
        }
    }
}

// Get products with vendor information
try {
    $filter = $_GET['filter'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if ($filter !== 'all') {
        $where_conditions[] = "p.status = ?";
        $params[] = $filter;
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR u.username LIKE ? OR v.business_name LIKE ?)";
        $search_term = "%$search%";
        $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $stmt = $conn->prepare("
        SELECT p.*, u.username as vendor_username, v.business_name, v.status as vendor_status,
               COALESCE(v.business_name, u.username) as display_vendor,
               (SELECT COUNT(*) FROM orders WHERE product_id = p.id) as order_count,
               (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating
        FROM products p
        LEFT JOIN vendors v ON p.vendor_id = v.id
        LEFT JOIN users u ON v.user_id = u.id
        $where_clause
        ORDER BY p.created_at DESC
    ");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stats_stmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
        FROM products
    ");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error loading products: " . $e->getMessage();
    $products = [];
    $stats = ['total' => 0, 'active' => 0, 'pending' => 0, 'inactive' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Market-X Admin</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 25%, #2d1810 50%, #1a1a1a 75%, #0a0a0a 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            color: #e5e5e5;
            min-height: 100vh;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
        }
        
        .page-title {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .breadcrumb {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(145deg, #1f1f1f, #2a2a2a);
            border: 1px solid #3a3a3a;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #ff6b35;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #b8b8b8;
            font-size: 14px;
        }
        
        .controls {
            background: linear-gradient(145deg, #1f1f1f, #2a2a2a);
            border: 1px solid #3a3a3a;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-box input {
            padding: 10px;
            border: 2px solid #3a3a3a;
            border-radius: 8px;
            background: #1a1a1a;
            color: #e5e5e5;
            width: 250px;
        }
        
        .search-box input:focus {
            border-color: #ff6b35;
            outline: none;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
        }
        
        .filter-tab {
            padding: 10px 16px;
            background: linear-gradient(145deg, #2a2a2a, #3a3a3a);
            border: 1px solid #4a4a4a;
            border-radius: 8px;
            color: #e5e5e5;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active,
        .filter-tab:hover {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            border-color: #ff6b35;
        }
        
        .action-btn {
            padding: 10px 16px;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.3);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .product-card {
            background: linear-gradient(145deg, #1f1f1f, #2a2a2a);
            border: 1px solid #3a3a3a;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(255, 107, 53, 0.2);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(145deg, #2a2a2a, #1a1a1a);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #3a3a3a;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-title {
            color: #ff6b35;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .product-vendor {
            color: #ff8c42;
            font-size: 12px;
            margin-bottom: 8px;
        }
        
        .product-desc {
            color: #b8b8b8;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 12px;
            max-height: 40px;
            overflow: hidden;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #e5e5e5;
        }
        
        .product-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }
        
        .status-pending_approval {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        
        .status-inactive {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }
        
        .product-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #32cd32);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #ffed4e);
            color: #333;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #20c997);
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #4caf50;
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #f44336;
        }
        
        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                justify-content: center;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="page-header">
            <h1 class="page-title">📦 Product Management</h1>
            <div class="breadcrumb">
                <a href="index.php">Admin Dashboard</a> → Product Management
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['active']); ?></div>
                <div class="stat-label">Active Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['inactive']); ?></div>
                <div class="stat-label">Inactive Products</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="search-box">
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="text" 
                           name="search" 
                           placeholder="Search products, vendors..."
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($_GET['filter'] ?? 'all'); ?>">
                    <button type="submit" class="action-btn">🔍 Search</button>
                </form>
            </div>
            
            <div class="filter-tabs">
                <a href="?filter=all&search=<?php echo urlencode($_GET['search'] ?? ''); ?>" 
                   class="filter-tab <?php echo ($_GET['filter'] ?? 'all') === 'all' ? 'active' : ''; ?>">
                    All (<?php echo $stats['total']; ?>)
                </a>
                <a href="?filter=active&search=<?php echo urlencode($_GET['search'] ?? ''); ?>" 
                   class="filter-tab <?php echo ($_GET['filter'] ?? '') === 'active' ? 'active' : ''; ?>">
                    Active (<?php echo $stats['active']; ?>)
                </a>
                <a href="?filter=pending_approval&search=<?php echo urlencode($_GET['search'] ?? ''); ?>" 
                   class="filter-tab <?php echo ($_GET['filter'] ?? '') === 'pending_approval' ? 'active' : ''; ?>">
                    Pending (<?php echo $stats['pending']; ?>)
                </a>
                <a href="?filter=inactive&search=<?php echo urlencode($_GET['search'] ?? ''); ?>" 
                   class="filter-tab <?php echo ($_GET['filter'] ?? '') === 'inactive' ? 'active' : ''; ?>">
                    Inactive (<?php echo $stats['inactive']; ?>)
                </a>
            </div>
            
            <a href="add_product.php" class="action-btn">➕ Add New Product</a>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="stat-card" style="text-align: center; padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;">📦</div>
                <h3 style="color: #ff6b35; margin-bottom: 10px;">No Products Found</h3>
                <p style="color: #b8b8b8;">
                    <?php if (!empty($_GET['search'])): ?>
                        No products match your search criteria.
                    <?php else: ?>
                        No products have been added to the marketplace yet.
                    <?php endif; ?>
                </p>
                <a href="add_product.php" class="action-btn" style="margin-top: 15px;">➕ Add First Product</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php if (!empty($product['image']) && file_exists("../uploads/" . $product['image'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image" 
                                 style="object-fit: cover;">
                        <?php else: ?>
                            <div class="product-image">📦</div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <div class="product-title">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </div>
                            
                            <?php if (!empty($product['display_vendor'])): ?>
                                <div class="product-vendor">
                                    🏪 <?php echo htmlspecialchars($product['display_vendor']); ?>
                                    <?php if ($product['vendor_status'] !== 'active'): ?>
                                        <span style="color: #ffc107;">(Pending Vendor)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-desc">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>
                                <?php echo strlen($product['description']) > 100 ? '...' : ''; ?>
                            </div>
                            
                            <div class="product-meta">
                                <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                <div class="product-status status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?>
                                </div>
                            </div>
                            
                            <div style="margin-bottom: 15px; font-size: 12px; color: #b8b8b8;">
                                📊 <?php echo $product['order_count'] ?? 0; ?> orders
                                <?php if (isset($product['avg_rating']) && $product['avg_rating'] > 0): ?>
                                    • ⭐ <?php echo number_format($product['avg_rating'], 1); ?>
                                <?php endif; ?>
                                • 📅 <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                            </div>
                            
                            <div class="product-actions">
                                <?php if ($product['status'] === 'pending_approval'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="approve_product" class="btn-sm btn-success" 
                                                onclick="return confirm('Approve this product?')">
                                            ✅ Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="reject_product" class="btn-sm btn-warning"
                                                onclick="return confirm('Reject this product?')">
                                            ❌ Reject
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn-sm btn-info">
                                    ✏️ Edit
                                </a>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh data
        setInterval(() => {
            const urlParams = new URLSearchParams(window.location.search);
            const noAutoRefresh = urlParams.get('no_refresh');
            if (!noAutoRefresh) {
                // Only refresh if no forms are being filled
                const forms = document.querySelectorAll('form');
                let hasActiveForm = false;
                forms.forEach(form => {
                    const inputs = form.querySelectorAll('input[type="text"], textarea');
                    inputs.forEach(input => {
                        if (input.value && document.activeElement === input) {
                            hasActiveForm = true;
                        }
                    });
                });
                
                if (!hasActiveForm) {
                    // Subtle refresh indication
                    console.log('Refreshing product data...');
                }
            }
        }, 60000);
    </script>
</body>
</html>