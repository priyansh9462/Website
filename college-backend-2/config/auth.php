<?php
require_once 'database.php';
require_once 'validation.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }
    
    public function register($data) {
        // Validate input
        $required = ['student_id', 'first_name', 'last_name', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("$field is required");
            }
        }
        
        // Sanitize inputs
        $student_id = Validator::sanitizeInput($data['student_id']);
        $first_name = Validator::sanitizeInput($data['first_name']);
        $last_name = Validator::sanitizeInput($data['last_name']);
        $email = Validator::sanitizeInput($data['email']);
        $phone = isset($data['phone']) ? Validator::sanitizeInput($data['phone']) : null;
        
        // Validate email
        if (!Validator::validateEmail($email)) {
            throw new Exception("Invalid email format");
        }
        
        // Validate password
        if (!Validator::validatePassword($data['password'])) {
            throw new Exception("Password must be at least 8 characters");
        }
        
        // Check if email or student ID exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR student_id = ?");
        $stmt->execute([$email, $student_id]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception("Email or Student ID already exists");
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $this->db->prepare("
            INSERT INTO users (student_id, first_name, last_name, email, phone, password_hash)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $student_id,
            $first_name,
            $last_name,
            $email,
            $phone,
            $hashedPassword
        ]);
        
        if (!$success) {
            throw new Exception("Registration failed");
        }
        
        return $this->db->lastInsertId();
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("
            SELECT id, student_id, first_name, last_name, email, password_hash, role 
            FROM users 
            WHERE email = ? AND is_active = TRUE
        ");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Invalid credentials");
        }
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Invalid credentials");
        }
        
        unset($user['password_hash']);
        return $user;
    }
    
    public function getUserById($id) {
        $stmt = $this->db->prepare("
            SELECT id, student_id, first_name, last_name, email, phone, role, created_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>