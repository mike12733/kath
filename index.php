<?php
require_once 'config/database.php';

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to appropriate dashboard based on user type
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('dashboard.php');
    }
} else {
    // Redirect to login page
    redirect('login.php');
}
?>