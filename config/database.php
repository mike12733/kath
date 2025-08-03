<?php
// Database Configuration for XAMPP
define('DB_HOST', 'localhost');
define('DB_NAME', 'lnhs_portal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_URL', 'http://localhost/lnhs_portal');
define('SITE_NAME', 'LNHS Documents Request Portal');
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Database Connection Class
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Utility Functions
function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function generateRequestNumber() {
    return 'REQ' . date('Y') . date('m') . date('d') . '-' . sprintf('%04d', rand(1000, 9999));
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'processing' => '<span class="badge bg-info">Processing</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'denied' => '<span class="badge bg-danger">Denied</span>',
        'ready_for_pickup' => '<span class="badge bg-primary">Ready for Pickup</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'cancelled' => '<span class="badge bg-secondary">Cancelled</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('dashboard.php');
    }
}

function logActivity($user_id, $action, $description = null, $request_id = null) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO activity_logs (user_id, request_id, action, description, ip_address, user_agent) 
              VALUES (:user_id, :request_id, :action, :description, :ip_address, :user_agent)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':request_id', $request_id);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
    $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
    
    return $stmt->execute();
}
?>