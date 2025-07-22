<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect to login if admin is not logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = sanitizeInput($_POST['price']);

    // Handle image upload if provided
    $imageFileName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Define the folder to save images (root folder of dashboard)
        $uploadDir = '../'; // root folder (dashboard/)

        // Get the file info
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileSize = $_FILES['image']['size'];
        $fileType = $_FILES['image']['type'];

        // Generate a unique file name to avoid overwriting
        $newFileName = uniqid() . '_' . basename($fileName);
        $destination = $uploadDir . $newFileName;

        // Allowed file types (you can modify this list as needed)
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        // Validate file type
        if (in_array($fileType, $allowedTypes)) {
            // Move the uploaded file to the desired directory
            if (move_uploaded_file($fileTmpPath, $destination)) {
                $imageFileName = $newFileName;
            } else {
                $error = "There was an error uploading the image.";
            }
        } else {
            $error = "Only JPEG, PNG, and GIF image formats are allowed.";
        }
    }

    // Insert product details into the database
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $description, $price, $imageFileName]);

    // Redirect to the product dashboard
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Digital Product</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
    <div class="window">
        <div class="title-bar">Add Digital Product</div>

        <!-- Display errors if any -->
        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required>

            <label for="description">Description:</label>
            <textarea name="description" id="description" required></textarea>

            <label for="price">Price:</label>
            <input type="number" step="0.01" name="price" id="price" required>

            <label for="image">Product Image:</label>
            <input type="file" name="image" id="image" accept="image/*">

            <button type="submit" class="button">Add Digital Product</button>
        </form>
        <a href="index.php">Back to Dashboard</a>
    </div>
</body>
</html>