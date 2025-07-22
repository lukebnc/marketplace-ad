<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding products to the cart
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Fetch product details
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // Add product to cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += 1;
        } else {
            $_SESSION['cart'][$product_id] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => 1,
                'image' => $product['image']
            ];
        }
        $_SESSION['success'] = "Product added to cart!";
    } else {
        $_SESSION['error'] = "Product not found.";
    }
    redirect('index.php');
}

// Handle removing products from the cart
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $product_id = $_GET['id'];
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['success'] = "Product removed from cart!";
    }
    redirect('cart.php');
}

// Handle updating cart quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        if ($quantity > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $quantity;
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    $_SESSION['success'] = "Cart updated!";
    redirect('cart.php');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">Shopping Cart</div>
        <div class="content">
            <!-- Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="notification error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <!-- Cart Contents -->
            <?php if (empty($_SESSION['cart'])): ?>
                <p>Your cart is empty.</p>
            <?php else: ?>
                <form method="POST" action="">
                    <table>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                        <?php foreach ($_SESSION['cart'] as $product_id => $item): ?>
                        <tr>
                            <td>
                                <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" width="50">
                                <?php echo $item['name']; ?>
                            </td>
                            <td>$<?php echo $item['price']; ?></td>
                            <td>
                                <input type="number" name="quantity[<?php echo $product_id; ?>]" value="<?php echo $item['quantity']; ?>" min="1">
                            </td>
                            <td>$<?php echo $item['price'] * $item['quantity']; ?></td>
                            <td>
                                <a href="cart.php?action=remove&id=<?php echo $product_id; ?>" class="button">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <button type="submit" name="update_cart" class="button">Update Cart</button>
                </form>
                <p>Total: $<?php
                    $total = 0;
                    foreach ($_SESSION['cart'] as $item) {
                        $total += $item['price'] * $item['quantity'];
                    }
                    echo $total;
                ?></p>
                <a href="checkout.php" class="button">Proceed to Checkout</a>
            <?php endif; ?>
            <a href="index.php" class="button">Continue Shopping</a>
        </div>
    </div>
</body>
</html>