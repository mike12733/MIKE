<?php
require_once 'config/database.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db = new Database();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Include barcode generator
require_once 'includes/barcode_generator.php';

// Generate unique barcode (updated to use new generator)
function generateBarcode() {
    global $db;
    return BarcodeGenerator::generateUniqueBarcode($db);
}

// Log admin activity
function logActivity($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    global $db;
    
    if (!isLoggedIn()) return;
    
    $sql = "INSERT INTO admin_logs (admin_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $_SESSION['admin_id'],
        $action,
        $table_name,
        $record_id,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null,
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    
    $db->query($sql, $params);
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

// Get equipment count by condition
function getEquipmentStats() {
    global $db;
    
    $stats = [
        'total' => 0,
        'good' => 0,
        'fair' => 0,
        'poor' => 0,
        'damaged' => 0,
        'lost' => 0
    ];
    
    $result = $db->fetchAll("SELECT condition_status, SUM(quantity) as count FROM equipment GROUP BY condition_status");
    
    foreach ($result as $row) {
        $stats['total'] += $row['count'];
        $stats[strtolower($row['condition_status'])] = $row['count'];
    }
    
    return $stats;
}

// Search equipment
function searchEquipment($search_term) {
    global $db;
    
    $sql = "SELECT * FROM equipment WHERE 
            item_name LIKE ? OR 
            description LIKE ? OR 
            category LIKE ? OR 
            barcode LIKE ? OR 
            location LIKE ?
            ORDER BY created_at DESC";
    
    $search = "%{$search_term}%";
    return $db->fetchAll($sql, [$search, $search, $search, $search, $search]);
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Generate barcode image using enhanced generator
function generateBarcodeImage($barcode) {
    return BarcodeGenerator::generateBarcodeDataURL($barcode, 300, 100);
}
?>