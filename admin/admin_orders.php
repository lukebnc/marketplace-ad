<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if user is not an admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

// Fetch orders from database
$stmt = $conn->prepare("SELECT * FROM orders WHERE admin_sent_link IS NULL ORDER BY created_at DESC");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Manage Orders</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">Manage Orders</div>
        <div class="content">
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Total Price</th>
                    <th>Payment Method</th>
                    <th>Payment Address</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Send Link</th>
                </tr>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td>$<?php echo htmlspecialchars($order['total_price']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_address']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($order['status'])); ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($order['created_at']))); ?></td>
                        <td>
                            <form method="POST" action="send_link.php">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="text" name="digital_link" placeholder="Enter Download Link" required>
                                <button type="submit" class="button">Send Link</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
