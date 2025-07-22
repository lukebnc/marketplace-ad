<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$product_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>
        <div class="content">
            <div class="product-details">
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                </div>
                <div class="product-info">
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
                    <p><strong>Price:</strong> $<?php echo number_format($product['price'], 2); ?></p>
                    <a href="cart.php?action=add&id=<?php echo $product['id']; ?>" class="button">Add to Cart</a>
                    <a href="index.php" class="button">Back to Store</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
