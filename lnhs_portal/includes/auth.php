<?php
// Authentication and Session Management for LNHS Documents Request Portal

require_once '../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($email, $password) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE email = ? AND is_active = 1",
                [$email]
            );
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['login_time'] = time();
                
                // Log admin activity if admin user
                if ($user['user_type'] === 'admin') {
                    logAdminActivity($user['id'], 'User Login', 'users', $user['id']);
                }
                
                return [
                    'success' => true,
                    'user' => $user,
                    'redirect' => $this->getRedirectUrl($user['user_type'])
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed. Please try again.'
            ];
        }
    }
    
    public function register($data) {
        try {
            // Validate required fields
            $required = ['email', 'password', 'full_name', 'user_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => ucfirst($field) . ' is required'
                    ];
                }
            }
            
            // Validate email
            if (!validateEmail($data['email'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid email format'
                ];
            }
            
            // Check if email already exists
            $existing = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ?",
                [$data['email']]
            );
            
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Email already registered'
                ];
            }
            
            // Validate password strength
            if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
                ];
            }
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Generate student ID if not provided
            if (empty($data['student_id']) && in_array($data['user_type'], ['student', 'alumni'])) {
                $data['student_id'] = $this->generateStudentId($data['user_type']);
            }
            
            // Insert user
            $userId = $this->db->insert('users', $data);
            
            if ($userId) {
                return [
                    'success' => true,
                    'message' => 'Registration successful. You can now login.',
                    'user_id' => $userId
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Registration failed. Please try again.'
                ];
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Registration failed. Please try again.'
            ];
        }
    }
    
    public function logout() {
        // Log admin activity before logout
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            logAdminActivity($_SESSION['user_id'], 'User Logout', 'users', $_SESSION['user_id']);
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Redirect to login page
        header('Location: ../login.php');
        exit();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['login_time']);
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'admin';
    }
    
    public function isStudent() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'student';
    }
    
    public function isAlumni() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'alumni';
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../login.php');
            exit();
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: ../dashboard.php');
            exit();
        }
    }
    
    public function requireStudentOrAlumni() {
        $this->requireLogin();
        if (!$this->isStudent() && !$this->isAlumni()) {
            header('Location: ../admin/dashboard.php');
            exit();
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    public function updateProfile($userId, $data) {
        try {
            // Remove password from data if not being updated
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $result = $this->db->update(
                'users',
                $data,
                'id = ?',
                [$userId]
            );
            
            if ($result) {
                // Update session data if current user
                if ($userId == $_SESSION['user_id']) {
                    if (isset($data['full_name'])) {
                        $_SESSION['full_name'] = $data['full_name'];
                    }
                    if (isset($data['email'])) {
                        $_SESSION['email'] = $data['email'];
                    }
                }
                
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No changes made'
                ];
            }
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Profile update failed. Please try again.'
            ];
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current user
            $user = $this->db->fetchOne(
                "SELECT password FROM users WHERE id = ?",
                [$userId]
            );
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }
            
            // Validate new password
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long'
                ];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $this->db->update(
                'users',
                ['password' => $hashedPassword],
                'id = ?',
                [$userId]
            );
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Password change failed'
                ];
            }
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Password change failed. Please try again.'
            ];
        }
    }
    
    private function generateStudentId($userType) {
        $prefix = $userType === 'student' ? 'STU' : 'ALM';
        $year = date('Y');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        return $prefix . $year . $random;
    }
    
    private function getRedirectUrl($userType) {
        switch ($userType) {
            case 'admin':
                return '../admin/dashboard.php';
            case 'student':
            case 'alumni':
                return '../dashboard.php';
            default:
                return '../dashboard.php';
        }
    }
    
    public function checkSessionTimeout() {
        if ($this->isLoggedIn()) {
            $timeout = SESSION_TIMEOUT;
            if (time() - $_SESSION['login_time'] > $timeout) {
                $this->logout();
            } else {
                // Update login time
                $_SESSION['login_time'] = time();
            }
        }
    }
}

// Initialize authentication
$auth = new Auth();

// Check session timeout on every request
$auth->checkSessionTimeout();

// CSRF protection
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Function to check if user can access specific page
function canAccessPage($requiredUserType = null) {
    global $auth;
    
    if (!$auth->isLoggedIn()) {
        return false;
    }
    
    if ($requiredUserType) {
        switch ($requiredUserType) {
            case 'admin':
                return $auth->isAdmin();
            case 'student':
                return $auth->isStudent();
            case 'alumni':
                return $auth->isAlumni();
            case 'student_or_alumni':
                return $auth->isStudent() || $auth->isAlumni();
            default:
                return true;
        }
    }
    
    return true;
}
?>