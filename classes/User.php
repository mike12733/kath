<?php
require_once '../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $student_id;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $user_type;
    public $phone;
    public $address;
    public $graduation_year;
    public $course_strand;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // User registration
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (student_id, email, password, first_name, last_name, user_type, phone, address, graduation_year, course_strand) 
                  VALUES (:student_id, :email, :password, :first_name, :last_name, :user_type, :phone, :address, :graduation_year, :course_strand)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->student_id = sanitize($this->student_id);
        $this->email = sanitize($this->email);
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->first_name = sanitize($this->first_name);
        $this->last_name = sanitize($this->last_name);
        $this->user_type = sanitize($this->user_type);
        $this->phone = sanitize($this->phone);
        $this->address = sanitize($this->address);

        // Bind values
        $stmt->bindParam(":student_id", $this->student_id);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":user_type", $this->user_type);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":graduation_year", $this->graduation_year);
        $stmt->bindParam(":course_strand", $this->course_strand);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // User login
    public function login($email, $password) {
        $query = "SELECT id, student_id, email, password, first_name, last_name, user_type, status 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND status = 'active' LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['student_id'] = $row['student_id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['user_type'] = $row['user_type'];
                
                // Log activity
                logActivity($row['id'], 'login', 'User logged in');
                
                return true;
            }
        }
        return false;
    }

    // Check if email exists
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Check if student ID exists
    public function studentIdExists($student_id) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE student_id = :student_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":student_id", $student_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Update user profile
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                  SET first_name = :first_name, last_name = :last_name, phone = :phone, 
                      address = :address, graduation_year = :graduation_year, course_strand = :course_strand 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->first_name = sanitize($this->first_name);
        $this->last_name = sanitize($this->last_name);
        $this->phone = sanitize($this->phone);
        $this->address = sanitize($this->address);

        // Bind values
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":graduation_year", $this->graduation_year);
        $stmt->bindParam(":course_strand", $this->course_strand);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Change password
    public function changePassword($current_password, $new_password) {
        // First verify current password
        $query = "SELECT password FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($current_password, $row['password'])) {
                // Update password
                $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt->bindParam(":password", $hashed_password);
                $stmt->bindParam(":id", $this->id);
                
                return $stmt->execute();
            }
        }
        return false;
    }

    // Get all users (for admin)
    public function getAllUsers($user_type = null) {
        $query = "SELECT id, student_id, email, first_name, last_name, user_type, phone, status, created_at 
                  FROM " . $this->table_name;
        
        if($user_type) {
            $query .= " WHERE user_type = :user_type";
        }
        
        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        if($user_type) {
            $stmt->bindParam(":user_type", $user_type);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update user status (for admin)
    public function updateUserStatus($user_id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $user_id);
        
        return $stmt->execute();
    }

    // Logout
    public static function logout() {
        session_destroy();
        return true;
    }
}
?>