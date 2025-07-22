<?php
// Generate payment address based on selected method
if ($_POST['payment_method'] === 'bitcoin') {
    $payment_address = generate_bitcoin_address();
} elseif ($_POST['payment_method'] === 'monero') {
    $payment_address = generate_monero_address();
}

// Save order to database
$sql = "INSERT INTO orders (user_id, product_id, quantity, total_price, payment_method, payment_address) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiidss", $user_id, $product_id, $quantity, $total_price, $payment_method, $payment_address);
$stmt->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">Payment</div>
        <p>Please send your payment to the following address:</p>
        <p><strong><?php echo $payment_address; ?></strong></p>
        <p>Once the payment is confirmed, your order will be processed.</p>
    </div>
</body>
</html>