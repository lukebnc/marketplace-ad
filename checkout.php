<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Fetch dynamic settings
$store_name = getSetting('store_name');
$xmr_address = getSetting('xmr_address');

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to proceed to checkout.";
    redirect('login.php');
}

// Redirect to cart if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty.";
    redirect('cart.php');
}

// Fetch the total cart price in USD
$total_usd = 0;
foreach ($_SESSION['cart'] as $product_id => $item) {
    $total_usd += $item['price'] * $item['quantity'];
}

// Fetch the current XMR exchange rate using CoinGecko API
$xmr_rate = null;
try {
    $api_url = "https://api.coingecko.com/api/v3/simple/price?ids=monero&vs_currencies=usd";
    $response = file_get_contents($api_url);
    if ($response) {
        $data = json_decode($response, true);
        $xmr_rate = $data['monero']['usd']; // Get the USD price of 1 XMR
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to fetch Monero exchange rate. Please try again later.";
    redirect('cart.php');
}

// Convert the total USD amount to XMR
if ($xmr_rate) {
    $total_xmr = $total_usd / $xmr_rate;
} else {
    $_SESSION['error'] = "Failed to fetch Monero exchange rate. Please try again later.";
    redirect('cart.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Payment method: Monero (XMR)
    $payment_method = 'xmr';
    $payment_address = $xmr_address;  // Use the dynamic Monero address

    // Save order to database
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, payment_method, payment_address, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $total_usd, $payment_method, $payment_address]);

    // Clear the cart
    unset($_SESSION['cart']);

    // Redirect to a success page
    $_SESSION['success'] = "Order placed successfully! Please complete your Monero (XMR) payment.";
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - <?php echo htmlspecialchars($store_name); ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        /* General Reset */
        body {
            font-family: Arial, sans-serif;
            background-color: #121212; /* Dark background */
            color: #e0e0e0; /* Light text */
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Window Styling */
        .window {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #1e1e1e; /* Darker gray for content box */
            border: 1px solid #333;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        /* Title Bar */
        .title-bar {
            font-size: 1.5em;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
            color: #bb86fc; /* Accent color for titles */
        }

        /* Notifications */
        .notification {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .notification.error {
            background-color: #cf6679; /* Red error */
            color: #fff;
            border: 1px solid #b00020;
        }
        .notification.success {
            background-color: #00c853; /* Green success */
            color: #fff;
            border: 1px solid #007e33;
        }

        /* Cart Summary Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #444;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #2a2a2a; /* Dark header */
            font-weight: bold;
            color: #bb86fc; /* Accent color for headers */
        }
        table tr:nth-child(even) {
            background-color: #242424; /* Slightly lighter rows */
        }
        table tr:hover {
            background-color: #333; /* Hover effect */
        }

        /* Payment Details */
        .xmr-payment {
            margin-top: 20px;
            padding: 15px;
            background-color: #242424; /* Dark background for payment section */
            border: 1px solid #444;
            border-radius: 4px;
        }
        .xmr-payment p {
            margin: 0 0 10px;
            font-size: 1em;
            color: #e0e0e0; /* Light text */
        }
        .xmr-payment code {
            display: block;
            word-wrap: break-word;
            background-color: #333; /* Dark background for code block */
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
            margin-bottom: 10px;
            color: #bb86fc; /* Accent color for Monero address */
        }
        .qr-code {
            display: block;
            margin: 10px auto;
            max-width: 200px;
            background-color: #1e1e1e; /* Dark background for QR code */
            padding: 10px;
            border-radius: 4px;
        }

        /* Buttons */
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #bb86fc; /* Accent color for buttons */
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            margin-right: 10px;
        }
        .button:hover {
            background-color: #9c27b0; /* Darker accent on hover */
        }
        .button.back {
            background-color: #757575; /* Gray for secondary button */
        }
        .button.back:hover {
            background-color: #5f5f5f; /* Darker gray on hover */
        }
    </style>
</head>
<body>
    <div class="window">
        <div class="title-bar">Checkout - <?php echo htmlspecialchars($store_name); ?></div>
        <div class="content">
            <!-- Notifications -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="notification error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Cart Summary -->
            <h3>Order Summary</h3>
            <table>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
                <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td>$<?php echo htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong>$<?php echo htmlspecialchars(number_format($total_usd, 2)); ?></strong></td>
                </tr>
            </table>

            <!-- Payment Form -->
            <h3>Payment Details</h3>
            <form method="POST" action="">
                <p>Please send the exact amount of <strong><?php echo htmlspecialchars(number_format($total_xmr, 8)); ?> XMR</strong> to the following Monero address:</p>
                <div class="xmr-payment">
                    <p><strong>Monero Address:</strong></p>
                    <code><?php echo htmlspecialchars($xmr_address); ?></code>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($xmr_address); ?>" alt="Monero QR Code" class="qr-code">
                </div>
                <button type="submit" class="button">Complete Order</button>
                <a href="cart.php" class="button back">Back to Cart</a>
            </form>
        </div>
    </div>
</body>
</html>