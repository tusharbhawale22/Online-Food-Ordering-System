<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tomato_db');

// Razorpay configuration
define('RAZORPAY_KEY_ID', 'rzp_test_S7zx9cjkll3hsp');
define('RAZORPAY_KEY_SECRET', '0Fja59ILqJVPSfBQEm1LLYf6');

// Site configuration
define('SITE_URL', 'http://localhost/zomato-tomato');
define('SITE_NAME', 'Tomato');

// Path configuration for new structure
define('ROOT_PATH', dirname(__DIR__, 2)); // Points to zomato-tomato root
define('BACKEND_PATH', __DIR__ . '/..');
define('FRONTEND_PATH', ROOT_PATH . '/frontend');
define('ASSETS_URL', SITE_URL . '/frontend/assets');
define('PAGES_URL', SITE_URL . '/frontend/pages');
define('API_URL', SITE_URL . '/backend/api');

// Create database connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Helper function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Helper function to get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $conn = getDBConnection();
    $userId = getCurrentUserId();
    
    $stmt = $conn->prepare("SELECT user_id, full_name, email, phone, locality, city, is_new_user FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $user;
}

// Helper function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Helper function to format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}
?>
