<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Require authentication
requireAuth('login.php');
$current_user = getCurrentUser();

// Check if user is already a vendor
if ($current_user['is_vendor']) {
    if ($current_user['vendor_approved']) {
        $_SESSION['info'] = "You are already an approved vendor!";
        redirect('vendor_dashboard.php');
    } else {
        $_SESSION['info'] = "Your vendor application is pending approval.";
        redirect('vendor_status.php');
    }
}

// Handle vendor upgrade request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upgrade_to_vendor'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $_SESSION['error'] = "Security token invalid. Please try again.";
        redirect('vendor_upgrade.php');
    }
    
    // Sanitize inputs
    $business_name = sanitizeInput($_POST['business_name']);
    $business_description = sanitizeInput($_POST['business_description']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    // Validation
    $errors = [];
    
    if (empty($business_name)) {
        $errors[] = 'Business name is required';
    }
    
    if (empty($business_description)) {
        $errors[] = 'Business description is required';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Get vendor fee from settings
            $vendor_fee = (float) getSetting('vendor_fee') ?: 100.00;
            
            // Get XMR address and calculate XMR amount
            $xmr_address = getSetting('xmr_address');
            if (empty($xmr_address)) {
                throw new Exception('Payment system is currently unavailable. Please try again later.');
            }
            
            // Fetch current XMR exchange rate
            $xmr_rate = null;
            try {
                $api_url = "https://api.coingecko.com/api/v3/simple/price?ids=monero&vs_currencies=usd";
                $response = file_get_contents($api_url);
                if ($response) {
                    $data = json_decode($response, true);
                    $xmr_rate = $data['monero']['usd'];
                }
            } catch (Exception $e) {
                throw new Exception('Unable to fetch current exchange rates. Please try again later.');
            }
            
            if (!$xmr_rate) {
                throw new Exception('Exchange rate unavailable. Please try again later.');
            }
            
            $xmr_amount = $vendor_fee / $xmr_rate;
            
            // Update user as pending vendor
            $stmt = $conn->prepare("UPDATE users SET is_vendor = 1, vendor_approved = 0 WHERE id = ?");
            $stmt->execute([$current_user['id']]);
            
            // Create vendor record
            $stmt = $conn->prepare("
                INSERT INTO vendors (user_id, business_name, business_description, payment_status, payment_amount, payment_address, created_at)
                VALUES (?, ?, ?, 'pending', ?, ?, NOW())
            ");
            $stmt->execute([
                $current_user['id'],
                encryptData($business_name),
                encryptData($business_description),
                $vendor_fee,
                $xmr_address
            ]);
            
            $vendor_id = $conn->lastInsertId();
            
            // Update user profile with contact info
            $stmt = $conn->prepare("
                UPDATE user_profiles 
                SET phone = ?, address = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                encryptData($phone),
                encryptData($address),
                $current_user['id']
            ]);
            
            $conn->commit();
            
            // Log security event
            logSecurityEvent('vendor_upgrade_initiated', $current_user['id'], true, [
                'business_name' => $business_name,
                'vendor_fee' => $vendor_fee,
                'xmr_amount' => $xmr_amount
            ]);
            
            // Store payment details in session for payment page
            $_SESSION['vendor_payment'] = [
                'vendor_id' => $vendor_id,
                'amount_usd' => $vendor_fee,
                'amount_xmr' => $xmr_amount,
                'xmr_address' => $xmr_address,
                'business_name' => $business_name
            ];
            
            $_SESSION['success'] = "Vendor application submitted! Please complete payment to activate your vendor account.";
            redirect('vendor_payment.php');
            
        } catch (Exception $e) {
            $conn->rollBack();
            logSecurityEvent('vendor_upgrade_failed', $current_user['id'], false, [
                'error' => $e->getMessage()
            ]);
            $_SESSION['error'] = $e->getMessage();
        }
    } else {
        $_SESSION['vendor_errors'] = $errors;
    }
}

$csrf_token = generateCSRFToken();
$vendor_errors = $_SESSION['vendor_errors'] ?? [];
unset($_SESSION['vendor_errors']);

// Get vendor fee from settings
$vendor_fee = getSetting('vendor_fee') ?: '100.00';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade to Vendor - Market-X</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        /* Market-X Vendor Upgrade - Orange Dark Theme */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 25%, #2d1810 50%, #1a1a1a 75%, #0a0a0a 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            color: #e5e5e5;
        }
        
        .upgrade-container {
            max-width: 800px;
            margin: 0 auto;
            background: linear-gradient(145deg, #1f1f1f, #2a2a2a);
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 107, 53, 0.1);
            overflow: hidden;
            border: 1px solid #3a3a3a;
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            box-shadow: 0 4px 20px rgba(255, 107, 53, 0.3);
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 20px solid transparent;
            border-right: 20px solid transparent;
            border-top: 20px solid #ff8c42;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 0 0 30px rgba(255, 107, 53, 0.3);
        }
        
        .header p {
            margin: 0;
            font-size: 1.2em;
            opacity: 0.95;
        }
        
        .content {
            padding: 40px;
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
        }
        
        .benefits-section {
            margin-bottom: 40px;
        }
        
        .benefits-section h2 {
            color: #ff6b35;
            margin-bottom: 20px;
            font-size: 1.8em;
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .benefit-card {
            background: linear-gradient(145deg, #2a2a2a, #1f1f1f);
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ff6b35;
            border: 1px solid #3a3a3a;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .benefit-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.2);
        }
        
        .benefit-card h4 {
            margin: 0 0 10px 0;
            color: #ff6b35;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .benefit-card p {
            margin: 0;
            color: #b8b8b8;
            line-height: 1.5;
        }
        
        .pricing-section {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.15), rgba(255, 107, 53, 0.05));
            border: 1px solid rgba(255, 107, 53, 0.3);
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 40px;
            text-align: center;
        }
        
        .pricing-section h3 {
            margin: 0 0 20px 0;
            color: #ff6b35;
        }
        
        .price {
            font-size: 3em;
            font-weight: bold;
            color: #ff8c42;
            margin: 0;
            text-shadow: 0 0 20px rgba(255, 107, 53, 0.3);
        }
        
        .price-details {
            margin-top: 10px;
            color: #b8b8b8;
            font-size: 14px;
        }
        
        .form-section {
            margin-top: 40px;
        }
        
        .form-section h3 {
            color: #ff6b35;
            margin-bottom: 20px;
            font-size: 1.6em;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #e5e5e5;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #3a3a3a;
            border-radius: 10px;
            font-size: 16px;
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            color: #e5e5e5;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 15px rgba(255, 107, 53, 0.2);
            background: linear-gradient(145deg, #1f1f1f, #151515);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #666;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .upgrade-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }
        
        .upgrade-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
        }
        
        .upgrade-button:active {
            transform: translateY(0);
        }
        
        .notification {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification.success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.15), rgba(76, 175, 80, 0.05));
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        
        .notification.error {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.15), rgba(244, 67, 54, 0.05));
            color: #f44336;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        
        .error-list {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: #f44336;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link a {
            color: #ff6b35;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .back-link a:hover {
            color: #ff8c42;
            text-decoration: underline;
        }
        
        .security-note {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 193, 7, 0.05));
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .security-note h5 {
            margin: 0 0 10px 0;
            color: #ffb347;
        }
        
        .security-note p {
            color: #b8b8b8;
            margin: 0;
        }
        
        .commission-info {
            background: linear-gradient(145deg, #2a2a2a, #1f1f1f);
            border: 1px solid #3a3a3a;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .commission-info h4 {
            margin: 0 0 15px 0;
            color: #ff6b35;
        }
        
        .commission-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .commission-item {
            text-align: center;
            background: linear-gradient(145deg, #1a1a1a, #0f0f0f);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #3a3a3a;
        }
        
        .commission-item .value {
            font-size: 2em;
            font-weight: bold;
            color: #ff6b35;
            display: block;
            text-shadow: 0 0 10px rgba(255, 107, 53, 0.3);
        }
        
        .commission-item .label {
            font-size: 14px;
            color: #b8b8b8;
            margin-top: 5px;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .upgrade-container {
                border-radius: 10px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .benefits-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .commission-details {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="upgrade-container">
        <div class="header">
            <h1>🚀 Become a Vendor</h1>
            <p>Start selling on Market-X and grow your business!</p>
        </div>
        
        <div class="content">
            <!-- Notifications -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="notification success">
                    ✓ <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="notification error">
                    ✗ <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($vendor_errors)): ?>
                <div class="notification error">
                    ✗ <strong>Please correct the following errors:</strong>
                    <ul class="error-list">
                        <?php foreach ($vendor_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Benefits Section -->
            <div class="benefits-section">
                <h2>✨ Vendor Benefits</h2>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <h4>💰 <span>Earn Money</span></h4>
                        <p>Start earning by selling your products to thousands of customers on our platform.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <h4>🛍️ <span>Easy Management</span></h4>
                        <p>Complete vendor dashboard to manage products, orders, and customer communications.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <h4>📊 <span>Analytics</span></h4>
                        <p>Detailed sales analytics and customer insights to grow your business.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <h4>💬 <span>Direct Communication</span></h4>
                        <p>Communicate directly with customers through our secure messaging system.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <h4>🔒 <span>Secure Payments</span></h4>
                        <p>All transactions are secured with advanced encryption and crypto payments.</p>
                    </div>
                    
                    <div class="benefit-card">
                        <h4>🌟 <span>Rating System</span></h4>
                        <p>Build your reputation with customer reviews and ratings system.</p>
                    </div>
                </div>
            </div>
            
            <!-- Commission Info -->
            <div class="commission-info">
                <h4>📈 Commission Structure</h4>
                <div class="commission-details">
                    <div class="commission-item">
                        <span class="value">5%</span>
                        <div class="label">Platform Commission<br><small>Competitive rates</small></div>
                    </div>
                    <div class="commission-item">
                        <span class="value">95%</span>
                        <div class="label">You Keep<br><small>Maximum earnings</small></div>
                    </div>
                </div>
            </div>
            
            <!-- Pricing Section -->
            <div class="pricing-section">
                <h3>🎯 One-time Setup Fee</h3>
                <div class="price">$<?php echo number_format((float)$vendor_fee, 2); ?></div>
                <div class="price-details">
                    Lifetime vendor access • No monthly fees • Unlimited products<br>
                    <small>Payment in Monero (XMR) for maximum security and privacy</small>
                </div>
            </div>
            
            <!-- Application Form -->
            <div class="form-section">
                <h3>📋 Vendor Application</h3>
                
                <form method="POST" action="" id="vendorForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="business_name">Business Name: *</label>
                        <input type="text" 
                               name="business_name" 
                               id="business_name" 
                               placeholder="Your business or store name"
                               required 
                               maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label for="business_description">Business Description: *</label>
                        <textarea name="business_description" 
                                  id="business_description" 
                                  placeholder="Describe your business, what products you sell, your experience..."
                                  required 
                                  maxlength="1000"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Contact Phone: *</label>
                        <input type="tel" 
                               name="phone" 
                               id="phone" 
                               placeholder="Your contact phone number"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Business Address: *</label>
                        <textarea name="address" 
                                  id="address" 
                                  placeholder="Your business address or location"
                                  required
                                  maxlength="500"></textarea>
                    </div>
                    
                    <button type="submit" name="upgrade_to_vendor" class="upgrade-button">
                        🚀 Apply for Vendor Status - $<?php echo number_format((float)$vendor_fee, 2); ?>
                    </button>
                </form>
                
                <div class="security-note">
                    <h5>🔒 Security & Privacy Notice</h5>
                    <p>All your information is encrypted and stored securely. We use Monero (XMR) payments for maximum privacy and security. Your vendor application will be reviewed within 24 hours after payment confirmation.</p>
                </div>
            </div>
            
            <div class="back-link">
                <a href="index.php">← Back to Marketplace</a>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation
        document.getElementById('vendorForm').addEventListener('submit', function(e) {
            const businessName = document.getElementById('business_name').value.trim();
            const businessDescription = document.getElementById('business_description').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            
            if (!businessName || !businessDescription || !phone || !address) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            if (businessDescription.length < 50) {
                e.preventDefault();
                alert('Business description should be at least 50 characters long.');
                return false;
            }
            
            if (!confirm('Are you ready to pay $<?php echo $vendor_fee; ?> to become a vendor? You will be redirected to the secure payment page.')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Character counter for description
        const descriptionField = document.getElementById('business_description');
        const maxLength = 1000;
        
        // Add character counter
        const counter = document.createElement('small');
        counter.style.color = '#666';
        counter.style.float = 'right';
        descriptionField.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - descriptionField.value.length;
            counter.textContent = `${remaining} characters remaining`;
            
            if (remaining < 100) {
                counter.style.color = '#dc3545';
            } else if (remaining < 200) {
                counter.style.color = '#ffc107';
            } else {
                counter.style.color = '#666';
            }
        }
        
        descriptionField.addEventListener('input', updateCounter);
        updateCounter();
    </script>
</body>
</html>