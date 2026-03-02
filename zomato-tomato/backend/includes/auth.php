<?php
require_once __DIR__ . '/../config/config.php';

// User registration function
function registerUser($fullName, $email, $phone, $password, $locality = '') {
    $conn = getDBConnection();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Email already registered'];
    }
    $stmt->close();
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, locality, is_new_user) VALUES (?, ?, ?, ?, ?, TRUE)");
    $stmt->bind_param("sssss", $fullName, $email, $phone, $passwordHash, $locality);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Registration failed: ' . $error];
    }
}

// User login function
function loginUser($email, $password) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT user_id, full_name, email, password_hash, is_new_user FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    // Verify password
    if (password_verify($password, $user['password_hash'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_new_user'] = $user['is_new_user'];
        
        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
    } else {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
}

// Logout function
function logoutUser() {
    session_unset();
    session_destroy();
    return true;
}

// Require login middleware
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

// Mark user as not new (after first order)
function markUserAsExisting($userId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE users SET is_new_user = FALSE WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    
    if (isset($_SESSION['is_new_user'])) {
        $_SESSION['is_new_user'] = false;
    }
}
?>
