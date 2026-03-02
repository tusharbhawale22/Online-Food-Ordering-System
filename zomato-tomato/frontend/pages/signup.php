<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(PAGES_URL . '/index.php');
}

$error = '';
$success = '';

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $locality = sanitizeInput($_POST['locality'] ?? '');
    
    // Validation
    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = registerUser($fullName, $email, $phone, $password, $locality);
        
        if ($result['success']) {
            $success = $result['message'];
            // Auto login after registration
            loginUser($email, $password);
            header("refresh:2;url=" . PAGES_URL . "/index.php");
        } else {
            $error = $result['message'];
        }
    }
}

// Fetch new user offer
$conn = getDBConnection();
$offerQuery = "SELECT * FROM offers WHERE is_new_user_only = TRUE AND is_active = TRUE LIMIT 1";
$offerResult = $conn->query($offerQuery);
$offer = $offerResult->fetch_assoc();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Tomato</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <h1>tomato</h1>
            </div>
            
            <?php if ($offer): ?>
            <div class="offer-banner">
                <h3>🎉 Welcome Offer!</h3>
                <p><?php echo htmlspecialchars($offer['description']); ?></p>
                <div class="offer-code"><?php echo htmlspecialchars($offer['offer_code']); ?></div>
            </div>
            <?php endif; ?>
            
            <h2 class="auth-title">Create Account</h2>
            <p class="auth-subtitle">Join Tomato and start ordering delicious food</p>
            
            <?php if ($error): ?>
                <div class="error-message" style="background-color: #ffe6e6; padding: 12px; border-radius: 8px; margin-bottom: 20px; color: var(--primary-color);">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message" style="background-color: #e6f7ed; padding: 12px; border-radius: 8px; margin-bottom: 20px; color: var(--success-color);">
                    <?php echo htmlspecialchars($success); ?> Redirecting...
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" class="form-input" required 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-input" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="locality">Locality</label>
                    <select id="locality" name="locality" class="form-input">
                        <option value="">Select your locality</option>
                        <option value="Southern Avenue">Southern Avenue</option>
                        <option value="Park Street">Park Street</option>
                        <option value="Salt Lake">Salt Lake</option>
                        <option value="Ballygunge">Ballygunge</option>
                        <option value="Alipore">Alipore</option>
                        <option value="Jadavpur">Jadavpur</option>
                        <option value="Howrah">Howrah</option>
                        <option value="Dum Dum">Dum Dum</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <div class="password-toggle">
                        <input type="password" id="password" name="password" class="form-input" required minlength="6">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">👁️</button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password *</label>
                    <div class="password-toggle">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required minlength="6">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="btn-auth">Create Account</button>
            </form>
            
            <div class="auth-link">
                Already have an account? <a href="<?php echo PAGES_URL; ?>/login.php">Login</a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
