<?php
require_once __DIR__ . '/../config/config.php';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/chatbot.css">
    <script src="<?php echo SITE_URL; ?>/js/cart.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?php echo SITE_URL; ?>/index.php" class="logo">tomato</a>
                
                <div class="location-selector" id="locationSelector">
                    <span class="location-icon">📍</span>
                    <span class="location-text" id="selectedLocation">Kolkata</span>
                </div>
                
                <div class="search-bar">
                    <input type="text" class="search-input" id="searchInput" placeholder="Search for restaurant, cuisine or a dish">
                    <span class="search-icon" id="searchBtn">🔍</span>
                </div>
                
                <div class="header-actions">
                    <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-link" style="position: relative; margin-right: 20px; text-decoration: none; color: var(--text-primary); font-size: 24px;">
                        🛒
                        <span id="cartBadge" style="position: absolute; top: -8px; right: -8px; background-color: var(--primary-color); color: white; border-radius: 50%; width: 20px; height: 20px; display: none; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;"></span>
                    </a>
                    <?php if ($currentUser): ?>
                        <div class="user-profile" id="userProfile">
                            <div class="avatar">
                                <span><?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?></span>
                            </div>
                            <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                        </div>
                        <div class="user-dropdown" id="userDropdown" style="display: none;">
                            <a href="<?php echo SITE_URL; ?>/profile.php">Profile</a>
                            <a href="<?php echo SITE_URL; ?>/orders.php">Orders</a>
                            <a href="<?php echo SITE_URL; ?>/logout.php">Logout</a>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline">Login</a>
                        <a href="<?php echo SITE_URL; ?>/signup.php" class="btn btn-primary">Signup</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
