<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(PAGES_URL . '/index.php');
}

$error = '';
$redirectUrl = $_GET['redirect'] ?? PAGES_URL . '/index.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = loginUser($email, $password);
        
        if ($result['success']) {
            redirect($redirectUrl);
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tomato</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/auth.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <h1>tomato</h1>
            </div>
            
            <h2 class="auth-title">Welcome Back</h2>
            <p class="auth-subtitle">Login to continue ordering delicious food</p>
            
            <?php if ($error): ?>
                <div class="error-message" style="background-color: #ffe6e6; padding: 12px; border-radius: 8px; margin-bottom: 20px; color: var(--primary-color);">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-toggle">
                        <input type="password" id="password" name="password" class="form-input" required 
                               placeholder="Enter your password">
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">👁️</button>
                    </div>
                </div>
                
                <button type="submit" class="btn-auth">Login</button>
            </form>
            
            <div class="auth-link">
                Don't have an account? <a href="<?php echo PAGES_URL; ?>/signup.php">Sign Up</a>
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
