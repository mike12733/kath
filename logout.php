<?php
session_start();
require_once 'config/database.php';

// Log logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'logout',
            'User logged out successfully',
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
    } catch (PDOException $e) {
        // Ignore logging errors during logout
    }
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit();
?>