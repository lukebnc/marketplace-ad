<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect to login if admin is not logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $order_id = sanitizeInput($_POST['order_id']);
    $admin_sent_link = sanitizeInput($_POST['admin_sent_link']);

    // Validate input
    if (empty($order_id)) {
        $_SESSION['error'] = "Order ID is required.";
        redirect('assign_download_link.php');
    }

    // Update the admin_sent_link in the orders table
    $stmt = $conn->prepare("UPDATE orders SET admin_sent_link = ? WHERE id = ?");
    $stmt->execute([$admin_sent_link, $order_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "Download link assigned successfully.";
    } else {
        $_SESSION['error'] = "Failed to assign the download link. Please check the Order ID.";
    }

    redirect('assign_download_link.php');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assign Download Link</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">Assign Download Link</div>

        <!-- Display notifications -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="notification error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="order_id">Order ID:</label>
            <input type="text" name="order_id" id="order_id" required placeholder="Enter Order ID">

            <label for="admin_sent_link">Download Link (URL):</label>
            <input type="url" name="admin_sent_link" id="admin_sent_link" placeholder="https://example.com/download.zip" required>

            <button type="submit" class="button">Assign Download Link</button>
        </form>
        <a href="index.php">Back to Dashboard</a>
    </div>
</body>
</html>