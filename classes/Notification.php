<?php
require_once '../config/database.php';

class Notification {
    private $conn;
    private $table_name = "notifications";

    public $id;
    public $user_id;
    public $request_id;
    public $title;
    public $message;
    public $type;
    public $is_read;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create notification
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, request_id, title, message, type) 
                  VALUES (:user_id, :request_id, :title, :message, :type)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->title = sanitize($this->title);
        $this->message = sanitize($this->message);
        $this->type = sanitize($this->type);

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":request_id", $this->request_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":message", $this->message);
        $stmt->bindParam(":type", $this->type);

        return $stmt->execute();
    }

    // Get user notifications
    public function getUserNotifications($user_id, $limit = 10) {
        $query = "SELECT n.*, dr.request_number 
                  FROM " . $this->table_name . " n 
                  LEFT JOIN document_requests dr ON n.request_id = dr.id 
                  WHERE n.user_id = :user_id 
                  ORDER BY n.created_at DESC";
        
        if($limit) {
            $query .= " LIMIT :limit";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        
        if($limit) {
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get unread notification count
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND is_read = 0";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    // Mark notification as read
    public function markAsRead($notification_id, $user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $notification_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    // Mark all notifications as read
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = 1 
                  WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    // Delete notification
    public function delete($notification_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $notification_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    // Create system notification for all users
    public function createSystemNotification($title, $message, $type = 'info') {
        // Get all active users
        $users_query = "SELECT id FROM users WHERE status = 'active'";
        $users_stmt = $this->conn->prepare($users_query);
        $users_stmt->execute();
        $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

        $success_count = 0;
        foreach($users as $user) {
            $this->user_id = $user['id'];
            $this->request_id = null;
            $this->title = $title;
            $this->message = $message;
            $this->type = $type;
            
            if($this->create()) {
                $success_count++;
            }
        }

        return $success_count;
    }

    // Get notification types with badges
    public function getTypeBadge($type) {
        $badges = [
            'info' => '<span class="badge bg-info">Info</span>',
            'success' => '<span class="badge bg-success">Success</span>',
            'warning' => '<span class="badge bg-warning">Warning</span>',
            'error' => '<span class="badge bg-danger">Error</span>'
        ];
        return $badges[$type] ?? '<span class="badge bg-secondary">' . ucfirst($type) . '</span>';
    }

    // Clean old notifications (admin function)
    public function cleanOldNotifications($days = 30) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":days", $days, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Send email notification (if email notifications are enabled)
    public function sendEmail($user_email, $subject, $message) {
        // Check if email notifications are enabled
        $settings_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'email_notifications'";
        $settings_stmt = $this->conn->prepare($settings_query);
        $settings_stmt->execute();
        $result = $settings_stmt->fetch(PDO::FETCH_ASSOC);

        if($result && $result['setting_value'] == '1') {
            // Simple email function (you can integrate with PHPMailer or other email libraries)
            $headers = "From: " . "noreply@lnhs.edu.ph" . "\r\n";
            $headers .= "Reply-To: " . "registrar@lnhs.edu.ph" . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

            $email_body = "
            <html>
            <body>
                <h2>LNHS Documents Request Portal</h2>
                <p>" . nl2br($message) . "</p>
                <hr>
                <p><small>This is an automated message. Please do not reply to this email.</small></p>
            </body>
            </html>";

            return mail($user_email, $subject, $email_body, $headers);
        }

        return false;
    }
}
?>