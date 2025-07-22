<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a vendor
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to access vendor features.";
    header("Location: login.php");
    exit();
}

// Check if user is a vendor
$stmt = $conn->prepare("SELECT is_vendor, vendor_approved FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['is_vendor']) {
    $_SESSION['error'] = "You need to become a vendor to add products.";
    header("Location: vendor_upgrade.php");
    exit();
}

if (!$user['vendor_approved']) {
    $_SESSION['error'] = "Your vendor account is pending approval. Please wait for admin approval.";
    header("Location: index.php");
    exit();
}

// Get vendor information
$stmt = $conn->prepare("SELECT * FROM vendors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$error = '';

// Handle product addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = "Security token invalid. Please try again.";
    } else {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $price = (float) $_POST['price'];
        $category = sanitizeInput($_POST['category']);
        $stock_quantity = (int) ($_POST['stock_quantity'] ?? 0);
        $product_type = $_POST['type'] ?? 'physical';
        $digital_link = sanitizeInput($_POST['digital_link'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($name) || strlen($name) < 3) {
            $errors[] = "Product name must be at least 3 characters long.";
        }
        
        if (empty($description) || strlen($description) < 10) {
            $errors[] = "Product description must be at least 10 characters long.";
        }
        
        if ($price <= 0 || $price > 999999.99) {
            $errors[] = "Price must be between $0.01 and $999,999.99";
        }
        
        if ($product_type === 'digital' && empty($digital_link)) {
            $errors[] = "Digital products must have a download link.";
        }
        
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            try {
                $validation_errors = validateUploadedFile(
                    $_FILES['image'], 
                    ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 
                    5242880 // 5MB
                );
                
                if (!empty($validation_errors)) {
                    $errors = array_merge($errors, $validation_errors);
                } else {
                    // Generate secure filename
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = generateSecureFilename($_FILES['image']['name'], $_SESSION['user_id']);
                    $upload_path = "uploads/" . $filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_path = $filename;
                    } else {
                        $errors[] = "Failed to upload image.";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Image upload error: " . $e->getMessage();
            }
        }
        
        if (empty($errors)) {
            try {
                // Insert product with pending approval status
                $stmt = $conn->prepare("
                    INSERT INTO products (name, description, price, image, vendor_id, category, stock_quantity, 
                                        status, type, digital_link, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_approval', ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $name, $description, $price, $image_path, $vendor['id'], 
                    $category, $stock_quantity, $product_type, $digital_link
                ]);
                
                if ($result) {
                    $product_id = $conn->lastInsertId();
                    
                    // Update vendor product count
                    $stmt = $conn->prepare("UPDATE vendors SET total_products = total_products + 1 WHERE id = ?");
                    $stmt->execute([$vendor['id']]);
                    
                    // Log activity
                    logSecurityEvent('product_added', $_SESSION['user_id'], true, [
                        'product_id' => $product_id,
                        'product_name' => $name
                    ]);
                    
                    $message = "Product added successfully! It's now pending admin approval.";
                    
                    // Clear form data
                    $_POST = [];
                } else {
                    $error = "Failed to add product. Please try again.";
                }
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = implode(' ', $errors);
        }
    }
}

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Get product categories
$categories = [
    'electronics' => 'Electronics',
    'software' => 'Software',
    'digital_goods' => 'Digital Goods',
    'services' => 'Services',
    'media' => 'Media & Entertainment',
    'tools' => 'Tools & Utilities',
    'education' => 'Education',
    'gaming' => 'Gaming',
    'security' => 'Security & Privacy',
    'other' => 'Other'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Market-X Vendor</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 25%, #2d1810 50%, #1a1a1a 75%, #0a0a0a 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            color: #e5e5e5;
            min-height: 100vh;
        }
        
        .vendor-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-header {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
            text-align: center;
        }
        
        .page-title {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 10px 0;
        }
        
        .vendor-info {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
        }
        
        .form-container {
            background: linear-gradient(145deg, #1f1f1f, #2a2a2a);
            border: 1px solid #3a3a3a;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #ff8c42;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #3a3a3a;
            border-radius: 8px;
            background: #1a1a1a;
            color: #e5e5e5;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-display {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            border: 2px dashed #3a3a3a;
            border-radius: 8px;
            background: #1a1a1a;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .file-input-display:hover, .file-input-wrapper:hover .file-input-display {
            border-color: #ff6b35;
            background: rgba(255, 107, 53, 0.05);
        }
        
        .file-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .radio-option input[type="radio"] {
            width: auto;
            margin: 0;
        }
        
        .digital-fields {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: rgba(255, 107, 53, 0.05);
            border: 1px solid rgba(255, 107, 53, 0.2);
            border-radius: 8px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #4caf50;
        }
        
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #f44336;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #ff8c42;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #ff6b35;
        }
        
        .char-counter {
            font-size: 12px;
            color: #b8b8b8;
            text-align: right;
            margin-top: 5px;
        }
        
        .help-text {
            font-size: 12px;
            color: #b8b8b8;
            margin-top: 5px;
            line-height: 1.4;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="vendor-container">
        <a href="index.php" class="back-link">← Back to Marketplace</a>
        
        <div class="page-header">
            <h1 class="page-title">🏪 Add New Product</h1>
            <div class="vendor-info">
                <?php echo htmlspecialchars($vendor['business_name'] ?? 'Vendor'); ?> Panel
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="name" class="form-label">Product Name *</label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           class="form-input"
                           placeholder="Enter a compelling product name"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           maxlength="100"
                           required>
                    <div class="char-counter">
                        <span id="nameCounter">0</span>/100 characters
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Product Description *</label>
                    <textarea name="description" 
                              id="description" 
                              class="form-textarea"
                              placeholder="Describe your product in detail. Include features, benefits, and any important information buyers should know."
                              maxlength="2000"
                              required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <div class="char-counter">
                        <span id="descCounter">0</span>/2000 characters
                    </div>
                    <div class="help-text">
                        💡 Tip: A detailed description helps customers understand your product better and increases sales.
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price" class="form-label">Price (USD) *</label>
                        <input type="number" 
                               name="price" 
                               id="price" 
                               class="form-input"
                               placeholder="0.00"
                               value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                               min="0.01"
                               max="999999.99"
                               step="0.01"
                               required>
                        <div class="help-text">
                            Enter price in US Dollars. Commission: 5% will be deducted.
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="category" class="form-label">Category *</label>
                        <select name="category" id="category" class="form-select" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?php echo $value; ?>" 
                                        <?php echo (($_POST['category'] ?? '') === $value) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product Type *</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" 
                                   name="type" 
                                   value="physical" 
                                   <?php echo (($_POST['type'] ?? 'physical') === 'physical') ? 'checked' : ''; ?>>
                            <span>📦 Physical Product</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" 
                                   name="type" 
                                   value="digital"
                                   <?php echo (($_POST['type'] ?? '') === 'digital') ? 'checked' : ''; ?>>
                            <span>💾 Digital Product</span>
                        </label>
                    </div>
                    
                    <div id="digitalFields" class="digital-fields">
                        <label for="digital_link" class="form-label">Download Link or Instructions</label>
                        <textarea name="digital_link" 
                                  id="digital_link" 
                                  class="form-textarea"
                                  placeholder="Enter download link, instructions, or digital delivery information..."
                                  rows="3"><?php echo htmlspecialchars($_POST['digital_link'] ?? ''); ?></textarea>
                        <div class="help-text">
                            🔐 This information will be provided to customers after successful purchase.
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="stock_quantity" class="form-label">Stock Quantity</label>
                    <input type="number" 
                           name="stock_quantity" 
                           id="stock_quantity" 
                           class="form-input"
                           placeholder="0"
                           value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? '0'); ?>"
                           min="0">
                    <div class="help-text">
                        Leave as 0 for unlimited stock or digital products.
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image" class="form-label">Product Image</label>
                    <div class="file-input-wrapper">
                        <input type="file" 
                               name="image" 
                               id="image" 
                               class="file-input"
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="file-input-display" id="fileDisplay">
                            <div>
                                <div style="font-size: 48px; margin-bottom: 10px;">📷</div>
                                <div>Click to upload product image</div>
                                <div style="font-size: 12px; color: #b8b8b8; margin-top: 5px;">
                                    JPG, PNG, GIF, WebP • Max 5MB
                                </div>
                            </div>
                        </div>
                    </div>
                    <img id="imagePreview" class="file-preview" style="display: none;">
                </div>
                
                <button type="submit" name="add_product" class="submit-btn" id="submitBtn">
                    🚀 Add Product for Review
                </button>
                
                <div style="margin-top: 15px; font-size: 13px; color: #b8b8b8; text-align: center;">
                    📋 Your product will be reviewed by our admin team before being published to the marketplace.
                    This typically takes 24-48 hours.
                </div>
            </form>
        </div>
    </div>

    <script>
        // Character counters
        function updateCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);
            
            function update() {
                const length = input.value.length;
                counter.textContent = length;
                counter.style.color = length > maxLength * 0.9 ? '#ffc107' : '#b8b8b8';
            }
            
            input.addEventListener('input', update);
            update();
        }
        
        updateCounter('name', 'nameCounter', 100);
        updateCounter('description', 'descCounter', 2000);
        
        // Product type handling
        const typeRadios = document.querySelectorAll('input[name="type"]');
        const digitalFields = document.getElementById('digitalFields');
        
        function toggleDigitalFields() {
            const isDigital = document.querySelector('input[name="type"]:checked').value === 'digital';
            digitalFields.style.display = isDigital ? 'block' : 'none';
            
            const digitalLink = document.getElementById('digital_link');
            if (isDigital) {
                digitalLink.setAttribute('required', 'required');
            } else {
                digitalLink.removeAttribute('required');
            }
        }
        
        typeRadios.forEach(radio => {
            radio.addEventListener('change', toggleDigitalFields);
        });
        
        // Initialize on load
        toggleDigitalFields();
        
        // Image preview
        const imageInput = document.getElementById('image');
        const fileDisplay = document.getElementById('fileDisplay');
        const imagePreview = document.getElementById('imagePreview');
        
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5242880) { // 5MB
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    fileDisplay.innerHTML = `
                        <div>
                            <div style="font-size: 24px; margin-bottom: 5px;">✅</div>
                            <div>Image uploaded: ${file.name}</div>
                            <div style="font-size: 12px; color: #b8b8b8; margin-top: 5px;">
                                Click to change image
                            </div>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            
            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.textContent = '🔄 Adding Product...';
            
            // Basic validation
            const name = document.getElementById('name').value.trim();
            const description = document.getElementById('description').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const category = document.getElementById('category').value;
            
            if (name.length < 3) {
                alert('Product name must be at least 3 characters long.');
                e.preventDefault();
                submitBtn.disabled = false;
                submitBtn.textContent = '🚀 Add Product for Review';
                return;
            }
            
            if (description.length < 10) {
                alert('Product description must be at least 10 characters long.');
                e.preventDefault();
                submitBtn.disabled = false;
                submitBtn.textContent = '🚀 Add Product for Review';
                return;
            }
            
            if (isNaN(price) || price <= 0) {
                alert('Please enter a valid price greater than $0.00.');
                e.preventDefault();
                submitBtn.disabled = false;
                submitBtn.textContent = '🚀 Add Product for Review';
                return;
            }
            
            if (!category) {
                alert('Please select a product category.');
                e.preventDefault();
                submitBtn.disabled = false;
                submitBtn.textContent = '🚀 Add Product for Review';
                return;
            }
            
            // Check digital product requirements
            const isDigital = document.querySelector('input[name="type"]:checked').value === 'digital';
            if (isDigital) {
                const digitalLink = document.getElementById('digital_link').value.trim();
                if (!digitalLink) {
                    alert('Digital products must have download instructions or link.');
                    e.preventDefault();
                    submitBtn.disabled = false;
                    submitBtn.textContent = '🚀 Add Product for Review';
                    return;
                }
            }
        });
        
        // Auto-save draft (optional enhancement)
        const formData = {};
        const inputs = document.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                formData[this.name] = this.value;
                localStorage.setItem('product_draft', JSON.stringify(formData));
            });
        });
        
        // Load draft on page load
        window.addEventListener('load', function() {
            const draft = localStorage.getItem('product_draft');
            if (draft && !<?php echo !empty($_POST) ? 'true' : 'false'; ?>) {
                try {
                    const data = JSON.parse(draft);
                    Object.keys(data).forEach(key => {
                        const element = document.querySelector(`[name="${key}"]`);
                        if (element && !element.value) {
                            element.value = data[key];
                            if (element.type === 'radio' && element.value === data[key]) {
                                element.checked = true;
                            }
                        }
                    });
                    toggleDigitalFields();
                } catch (e) {
                    console.log('No valid draft found');
                }
            }
        });
    </script>
</body>
</html>