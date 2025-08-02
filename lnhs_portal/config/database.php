<?php
// Database configuration for LNHS Documents Request Portal

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'lnhs_documents_portal');

// Application settings
define('SITE_NAME', 'LNHS Documents Request Portal');
define('SITE_URL', 'http://localhost/lnhs_portal');
define('UPLOAD_PATH', '../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Email settings (for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@lnhs.edu.ph');
define('SMTP_FROM_NAME', 'LNHS Documents Portal');

// Session settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('SESSION_NAME', 'lnhs_portal_session');

// Security settings
define('CSRF_TOKEN_NAME', 'lnhs_csrf_token');
define('PASSWORD_MIN_LENGTH', 8);

class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USERNAME,
                DB_PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw new Exception("Database operation failed");
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->query($sql, $data);
        
        return $this->connection->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "$column = :$column";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollback() {
        return $this->connection->rollback();
    }
}

// Helper functions
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function generateRequestNumber() {
    $prefix = 'REQ';
    $year = date('Y');
    $month = date('m');
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . $year . $month . $random;
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    return date($format, strtotime($datetime));
}

function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'badge bg-warning',
        'processing' => 'badge bg-info',
        'approved' => 'badge bg-success',
        'denied' => 'badge bg-danger',
        'ready_for_pickup' => 'badge bg-primary',
        'completed' => 'badge bg-secondary'
    ];
    return $classes[$status] ?? 'badge bg-secondary';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'approved' => 'Approved',
        'denied' => 'Denied',
        'ready_for_pickup' => 'Ready for Pickup',
        'completed' => 'Completed'
    ];
    return $texts[$status] ?? 'Unknown';
}

function getUserTypeText($userType) {
    $texts = [
        'student' => 'Student',
        'alumni' => 'Alumni',
        'admin' => 'Administrator'
    ];
    return $texts[$userType] ?? 'Unknown';
}

function logAdminActivity($adminId, $action, $tableAffected = null, $recordId = null, $oldValues = null, $newValues = null) {
    $db = Database::getInstance();
    
    $data = [
        'admin_id' => $adminId,
        'action' => $action,
        'table_affected' => $tableAffected,
        'record_id' => $recordId,
        'old_values' => $oldValues ? json_encode($oldValues) : null,
        'new_values' => $newValues ? json_encode($newValues) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    return $db->insert('admin_logs', $data);
}

function sendNotification($userId, $title, $message, $type = 'portal') {
    $db = Database::getInstance();
    
    $data = [
        'user_id' => $userId,
        'title' => $title,
        'message' => $message,
        'type' => $type
    ];
    
    return $db->insert('notifications', $data);
}

function getUnreadNotificationsCount($userId) {
    $db = Database::getInstance();
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
        [$userId]
    );
    return $result['count'] ?? 0;
}

function getSystemSetting($key, $default = null) {
    $db = Database::getInstance();
    $result = $db->fetchOne(
        "SELECT setting_value FROM system_settings WHERE setting_key = ?",
        [$key]
    );
    return $result ? $result['setting_value'] : $default;
}

function updateSystemSetting($key, $value) {
    $db = Database::getInstance();
    return $db->update(
        'system_settings',
        ['setting_value' => $value],
        'setting_key = ?',
        [$key]
    );
}
?>