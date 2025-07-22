<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to view your orders.";
    redirect('login.php');
}

// Fetch orders for the logged-in user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT o.id AS order_id, o.price as total_price, o.payment_address, o.status, 
           COALESCE(o.admin_sent_link, '') as admin_sent_link
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">My Orders</div>
        <div class="content">
            <!-- Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="notification error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <!-- Orders Table -->
            <?php if (empty($orders)): ?>
                <p>You have no orders yet.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Order ID</th>
                        <th>Total Price</th>
                        <th>Payment Address</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td>$<?php echo htmlspecialchars($order['total_price']); ?></td>
                            <td><?php echo htmlspecialchars($order['payment_address']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($order['status'])); ?></td>
                            <td>
                                <!-- Display Download Link as Plain Text -->
                                <?php if (!empty($order['admin_sent_link'])): ?>
                                    <?php echo htmlspecialchars($order['admin_sent_link']); ?>
                                <?php else: ?>
                                    <span>Product is Processing Manually. Product will Shows here!</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
            <a href="index.php" class="button back-to-store">Back to Store</a>
        </div>
    </div>
</body>
</html>