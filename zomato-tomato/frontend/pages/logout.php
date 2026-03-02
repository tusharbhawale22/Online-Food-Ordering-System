<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';

logoutUser();
redirect(SITE_URL . '/index.php');
?>
