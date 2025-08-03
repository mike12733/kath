<?php
require_once 'config/database.php';

// Log the logout activity if user is logged in
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login page
redirect('login.php');
?>