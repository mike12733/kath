<?php
require_once '../config/database.php';

class DocumentRequest {
    private $conn;
    private $requests_table = "document_requests";
    private $types_table = "document_types";
    private $attachments_table = "request_attachments";

    public $id;
    public $user_id;
    public $document_type_id;
    public $request_number;
    public $purpose;
    public $preferred_release_date;
    public $status;
    public $admin_notes;
    public $denial_reason;
    public $processed_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new document request
    public function create() {
        $query = "INSERT INTO " . $this->requests_table . " 
                  (user_id, document_type_id, request_number, purpose, preferred_release_date) 
                  VALUES (:user_id, :document_type_id, :request_number, :purpose, :preferred_release_date)";

        $stmt = $this->conn->prepare($query);

        // Generate unique request number
        $this->request_number = generateRequestNumber();
        
        // Sanitize inputs
        $this->purpose = sanitize($this->purpose);

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":document_type_id", $this->document_type_id);
        $stmt->bindParam(":request_number", $this->request_number);
        $stmt->bindParam(":purpose", $this->purpose);
        $stmt->bindParam(":preferred_release_date", $this->preferred_release_date);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Log activity
            logActivity($this->user_id, 'request_created', 'Document request created: ' . $this->request_number, $this->id);
            
            // Create notification
            $this->createNotification($this->user_id, 'Request Submitted', 'Your document request ' . $this->request_number . ' has been submitted successfully.');
            
            return true;
        }
        return false;
    }

    // Get all document types
    public function getDocumentTypes() {
        $query = "SELECT * FROM " . $this->types_table . " WHERE status = 'active' ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user's requests
    public function getUserRequests($user_id) {
        $query = "SELECT dr.*, dt.name as document_name, dt.fee, dt.processing_days 
                  FROM " . $this->requests_table . " dr 
                  LEFT JOIN " . $this->types_table . " dt ON dr.document_type_id = dt.id 
                  WHERE dr.user_id = :user_id 
                  ORDER BY dr.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all requests (for admin)
    public function getAllRequests($status = null, $limit = null) {
        $query = "SELECT dr.*, dt.name as document_name, dt.fee, 
                         u.first_name, u.last_name, u.email, u.student_id, u.user_type 
                  FROM " . $this->requests_table . " dr 
                  LEFT JOIN " . $this->types_table . " dt ON dr.document_type_id = dt.id 
                  LEFT JOIN users u ON dr.user_id = u.id";

        if($status) {
            $query .= " WHERE dr.status = :status";
        }

        $query .= " ORDER BY dr.created_at DESC";

        if($limit) {
            $query .= " LIMIT :limit";
        }

        $stmt = $this->conn->prepare($query);
        
        if($status) {
            $stmt->bindParam(":status", $status);
        }
        
        if($limit) {
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get request by ID
    public function getRequestById($id) {
        $query = "SELECT dr.*, dt.name as document_name, dt.description, dt.requirements, dt.fee, dt.processing_days,
                         u.first_name, u.last_name, u.email, u.student_id, u.phone, u.user_type 
                  FROM " . $this->requests_table . " dr 
                  LEFT JOIN " . $this->types_table . " dt ON dr.document_type_id = dt.id 
                  LEFT JOIN users u ON dr.user_id = u.id 
                  WHERE dr.id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Update request status
    public function updateStatus($request_id, $status, $admin_notes = null, $processed_by = null, $denial_reason = null) {
        $query = "UPDATE " . $this->requests_table . " 
                  SET status = :status, admin_notes = :admin_notes, processed_by = :processed_by, 
                      denial_reason = :denial_reason, processed_at = CURRENT_TIMESTAMP";

        if($status === 'approved') {
            $query .= ", approved_at = CURRENT_TIMESTAMP";
        }

        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":admin_notes", $admin_notes);
        $stmt->bindParam(":processed_by", $processed_by);
        $stmt->bindParam(":denial_reason", $denial_reason);
        $stmt->bindParam(":id", $request_id);

        if($stmt->execute()) {
            // Get request details for notification
            $request = $this->getRequestById($request_id);
            
            // Log activity
            logActivity($processed_by, 'status_updated', "Request status updated to: $status", $request_id);
            
            // Create notification for user
            $message = $this->getStatusMessage($status, $denial_reason);
            $this->createNotification($request['user_id'], 'Request Status Updated', $message);
            
            return true;
        }
        return false;
    }

    // Upload attachment
    public function uploadAttachment($request_id, $file) {
        $upload_dir = '../uploads/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_error = $file['error'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        
        if($file_error === 0) {
            if(in_array($file_ext, $allowed_types)) {
                if($file_size <= MAX_FILE_SIZE) {
                    // Generate unique filename
                    $new_filename = uniqid('', true) . '.' . $file_ext;
                    $file_destination = $upload_dir . $new_filename;
                    
                    if(move_uploaded_file($file_tmp, $file_destination)) {
                        // Save to database
                        $query = "INSERT INTO " . $this->attachments_table . " 
                                  (request_id, file_name, file_path, file_type, file_size) 
                                  VALUES (:request_id, :file_name, :file_path, :file_type, :file_size)";
                        
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(":request_id", $request_id);
                        $stmt->bindParam(":file_name", $file_name);
                        $stmt->bindParam(":file_path", $file_destination);
                        $stmt->bindParam(":file_type", $file_ext);
                        $stmt->bindParam(":file_size", $file_size);
                        
                        return $stmt->execute();
                    }
                }
            }
        }
        return false;
    }

    // Get request attachments
    public function getRequestAttachments($request_id) {
        $query = "SELECT * FROM " . $this->attachments_table . " WHERE request_id = :request_id ORDER BY uploaded_at";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":request_id", $request_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get dashboard statistics
    public function getDashboardStats($user_id = null) {
        $stats = [];
        
        if($user_id) {
            // User statistics
            $query = "SELECT 
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_for_pickup,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                      FROM " . $this->requests_table . " WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
        } else {
            // Admin statistics
            $query = "SELECT 
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_for_pickup,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied
                      FROM " . $this->requests_table;
            
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create notification
    private function createNotification($user_id, $title, $message) {
        $query = "INSERT INTO notifications (user_id, title, message, type) VALUES (:user_id, :title, :message, 'info')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":message", $message);
        return $stmt->execute();
    }

    // Get status message for notifications
    private function getStatusMessage($status, $denial_reason = null) {
        switch($status) {
            case 'processing':
                return 'Your document request is now being processed by our office.';
            case 'approved':
                return 'Your document request has been approved and is being prepared.';
            case 'denied':
                $message = 'Your document request has been denied.';
                if($denial_reason) {
                    $message .= ' Reason: ' . $denial_reason;
                }
                return $message;
            case 'ready_for_pickup':
                return 'Your document is ready for pickup. Please visit our office during office hours.';
            case 'completed':
                return 'Your document request has been completed. Thank you!';
            default:
                return 'Your document request status has been updated.';
        }
    }

    // Delete request (admin only)
    public function deleteRequest($request_id) {
        // First delete attachments
        $attachments = $this->getRequestAttachments($request_id);
        foreach($attachments as $attachment) {
            if(file_exists($attachment['file_path'])) {
                unlink($attachment['file_path']);
            }
        }

        // Delete request (cascading will handle attachments and notifications)
        $query = "DELETE FROM " . $this->requests_table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $request_id);
        
        return $stmt->execute();
    }
}
?>