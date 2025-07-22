<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to view your orders.";
    redirect('login.php');
}

// Get the order ID from the query string
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    $_SESSION['error'] = "Invalid order ID.";
    redirect('orders.php');
}

$order_id = $_GET['order_id'];

// Fetch the order details and associated product information
$stmt_order = $conn->prepare("
    SELECT o.id AS order_id, o.total_price, o.payment_method, o.payment_address, o.status, o.created_at, p.name AS product_name, p.price AS product_price, p.digital_link
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt_order->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt_order->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = "Order not found or you do not have permission to view it.";
    redirect('orders.php');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Order</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">View Order</div>
        <div class="content">
            <!-- Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="notification error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <!-- Display the order details -->
            <h3>Order Details</h3>
            <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
            <p><strong>Product Name:</strong> <?php echo htmlspecialchars($order['product_name']); ?></p>
            <p><strong>Product Price:</strong> $<?php echo htmlspecialchars($order['product_price']); ?></p>
            <p><strong>Total Price:</strong> $<?php echo htmlspecialchars($order['total_price']); ?></p>
            <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></p>
            <p><strong>Payment Address:</strong> <?php echo htmlspecialchars($order['payment_address']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($order['status'])); ?></p>
            <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($order['created_at']))); ?></p>
            <?php if (!empty($order['digital_link'])): ?>
                <p><strong>Download Link:</strong> <a href="<?php echo htmlspecialchars($order['digital_link']); ?>" target="_blank">Download</a></p>
            <?php else: ?>
                <p><strong>Download Link:</strong> Not Available</p>
            <?php endif; ?>
            <a href="orders.php" class="button back-to-orders">Back to Orders</a>
        </div>
    </div>
</body>
</html>