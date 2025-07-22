<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect to login if not logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = sanitizeInput($_POST['store_name']);
    $xmr_address = sanitizeInput($_POST['xmr_address']);

    // Update store name
    $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE key_name = 'store_name'");
    $stmt->execute([$store_name]);

    // Update XMR address
    $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE key_name = 'xmr_address'");
    $stmt->execute([$xmr_address]);

    $_SESSION['success'] = "Settings updated successfully!";
    redirect('manage_payments.php');
}

// Fetch current settings
$stmt = $conn->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key_name']] = $row['value'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Payments</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen">
        <h1>Booting Admin Panel...</h1>
        <div class="loading-bar">
            <span></span>
        </div>
        <p class="boot-message">Please wait while the system initializes...</p>
    </div>
    <div class="window">
        <div class="title-bar">
            <span>Manage Payments</span>
            <div class="buttons">
                <a href="../logout.php" class="button">Logout</a>
            </div>
        </div>
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <h3>Update Payment Settings</h3>
            <form method="POST" action="">
                <label for="store_name">Store Name:</label>
                <input type="text" name="store_name" id="store_name" value="<?php echo htmlspecialchars($settings['store_name']); ?>" required>

                <label for="xmr_address">Monero Address:</label>
                <input type="text" name="xmr_address" id="xmr_address" value="<?php echo htmlspecialchars($settings['xmr_address']); ?>" required>

                <button type="submit" class="button">Save Changes</button>
            </form>
<br>
<br>
<br>
<br>
<br>
<br>
        </div>
        <footer class="admin-footer">
            <p>System Time: <span id="system-time"></span></p>
        </footer>
    </div>
</body>
</html>