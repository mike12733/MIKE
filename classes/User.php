<?php
require_once '../config/lnhs_database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = new LNHSDatabase();
    }
    
    public function register($data) {
        try {
            // Check if email already exists
            $existing = $this->db->fetch(
                "SELECT id FROM users WHERE email = ?", 
                [$data['email']]
            );
            
            if ($existing) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Check if student ID already exists (for students/alumni)
            if (!empty($data['student_id'])) {
                $existing_student = $this->db->fetch(
                    "SELECT id FROM users WHERE student_id = ?", 
                    [$data['student_id']]
                );
                
                if ($existing_student) {
                    return ['success' => false, 'message' => 'Student ID already exists'];
                }
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $sql = "INSERT INTO users (user_type, student_id, email, password, first_name, last_name, middle_name, contact_number, address, year_graduated, course) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['user_type'],
                $data['student_id'] ?? null,
                $data['email'],
                $hashedPassword,
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'] ?? null,
                $data['contact_number'] ?? null,
                $data['address'] ?? null,
                $data['year_graduated'] ?? null,
                $data['course'] ?? null
            ];
            
            $this->db->query($sql, $params);
            
            return ['success' => true, 'message' => 'Registration successful'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function login($email, $password) {
        try {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE email = ? AND is_active = 1", 
                [$email]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                // Start session and store user data
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_start();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        return $this->db->fetch(
            "SELECT * FROM users WHERE id = ?", 
            [$_SESSION['user_id']]
        );
    }
    
    public function getUserById($id) {
        return $this->db->fetch(
            "SELECT * FROM users WHERE id = ?", 
            [$id]
        );
    }
    
    public function updateProfile($userId, $data) {
        try {
            $sql = "UPDATE users SET first_name = ?, last_name = ?, middle_name = ?, contact_number = ?, address = ?, course = ? WHERE id = ?";
            
            $params = [
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'] ?? null,
                $data['contact_number'] ?? null,
                $data['address'] ?? null,
                $data['course'] ?? null,
                $userId
            ];
            
            $this->db->query($sql, $params);
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->getUserById($userId);
            
            if (!password_verify($currentPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $this->db->query(
                "UPDATE users SET password = ? WHERE id = ?", 
                [$hashedPassword, $userId]
            );
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Password change failed: ' . $e->getMessage()];
        }
    }
    
    public function getAllUsers($userType = null) {
        $sql = "SELECT id, user_type, student_id, email, first_name, last_name, contact_number, year_graduated, course, is_active, created_at FROM users";
        $params = [];
        
        if ($userType) {
            $sql .= " WHERE user_type = ?";
            $params[] = $userType;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
}
?>