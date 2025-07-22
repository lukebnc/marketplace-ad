<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('manage_products.php');
}

$product_id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    redirect('manage_products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = sanitizeInput($_POST['price']);
    $type = sanitizeInput($_POST['type']);
    $image = sanitizeInput($_POST['image']);

    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, type = ?, image = ? WHERE id = ?");
    $stmt->execute([$name, $description, $price, $type, $image, $product_id]);

    redirect('manage_products.php');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">Edit Product</div>
        <form method="POST" action="">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" value="<?php echo $product['name']; ?>" required>
            <label for="description">Description:</label>
            <textarea name="description" id="description" required><?php echo $product['description']; ?></textarea>
            <label for="price">Price:</label>
            <input type="number" step="0.01" name="price" id="price" value="<?php echo $product['price']; ?>" required>
            <label for="type">Type:</label>
            <select name="type" id="type" required>
                <option value="physical" <?php echo $product['type'] === 'physical' ? 'selected' : ''; ?>>Physical</option>
                <option value="digital" <?php echo $product['type'] === 'digital' ? 'selected' : ''; ?>>Digital</option>
            </select>
            <label for="image">Image URL:</label>
            <input type="text" name="image" id="image" value="<?php echo $product['image']; ?>" required>
            <button type="submit" class="button">Update Product</button>
        </form>
        <a href="manage_products.php">Back to Manage Products</a>
    </div>
</body>
</html>