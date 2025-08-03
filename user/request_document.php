<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] === 'admin') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_type_id = $_POST['document_type_id'];
    $purpose = trim($_POST['purpose']);
    $preferred_release_date = $_POST['preferred_release_date'];
    
    // Validation
    if (empty($document_type_id) || empty($purpose) || empty($preferred_release_date)) {
        $_SESSION['error'] = 'Please fill in all required fields.';
        header('Location: dashboard.php');
        exit();
    }
    
    // Check if preferred date is not in the past
    if (strtotime($preferred_release_date) < strtotime(date('Y-m-d'))) {
        $_SESSION['error'] = 'Preferred release date cannot be in the past.';
        header('Location: dashboard.php');
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert document request
        $stmt = $pdo->prepare("INSERT INTO document_requests (user_id, document_type_id, purpose, preferred_release_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $document_type_id, $purpose, $preferred_release_date]);
        $request_id = $pdo->lastInsertId();
        
        // Handle file uploads
        if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
            $upload_dir = '../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['attachments']['name'][$key];
                    $file_type = $_FILES['attachments']['type'][$key];
                    
                    // Validate file type
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                    if (!in_array($file_type, $allowed_types)) {
                        continue; // Skip invalid files
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $unique_filename = 'request_' . $request_id . '_' . time() . '_' . $key . '.' . $file_extension;
                    $file_path = $upload_dir . $unique_filename;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        // Save file info to database
                        $stmt = $pdo->prepare("INSERT INTO request_attachments (request_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$request_id, $file_name, $unique_filename, $file_type]);
                    }
                }
            }
        }
        
        // Create notification for admin
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            1, // Admin user ID
            'New Document Request',
            'A new document request has been submitted by ' . $_SESSION['full_name'] . ' (Request #' . $request_id . ')',
            'portal'
        ]);
        
        // Log the request
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            'document_request',
            'Submitted document request #' . $request_id,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        
        $pdo->commit();
        
        $_SESSION['success'] = 'Document request submitted successfully! Request ID: #' . $request_id;
        header('Location: dashboard.php');
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error submitting request: ' . $e->getMessage();
        header('Location: dashboard.php');
        exit();
    }
} else {
    header('Location: dashboard.php');
    exit();
}
?>