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

// Generate unique barcode
function generateBarcode() {
    return 'EQ' . date('Y') . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
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

// Generate barcode image (simple text-based for demo)
function generateBarcodeImage($barcode) {
    // This is a simple implementation. In production, you might want to use a proper barcode library
    return "data:image/svg+xml;base64," . base64_encode('
    <svg width="200" height="80" xmlns="http://www.w3.org/2000/svg">
        <rect width="200" height="80" fill="white"/>
        <g fill="black">
            <rect x="10" y="10" width="2" height="50"/>
            <rect x="15" y="10" width="1" height="50"/>
            <rect x="18" y="10" width="3" height="50"/>
            <rect x="25" y="10" width="1" height="50"/>
            <rect x="30" y="10" width="2" height="50"/>
            <rect x="35" y="10" width="1" height="50"/>
            <rect x="40" y="10" width="2" height="50"/>
            <rect x="45" y="10" width="3" height="50"/>
            <rect x="52" y="10" width="1" height="50"/>
            <rect x="57" y="10" width="2" height="50"/>
            <rect x="63" y="10" width="1" height="50"/>
            <rect x="68" y="10" width="3" height="50"/>
            <rect x="75" y="10" width="2" height="50"/>
            <rect x="80" y="10" width="1" height="50"/>
            <rect x="85" y="10" width="2" height="50"/>
            <rect x="90" y="10" width="3" height="50"/>
            <rect x="97" y="10" width="1" height="50"/>
            <rect x="102" y="10" width="2" height="50"/>
            <rect x="108" y="10" width="1" height="50"/>
            <rect x="113" y="10" width="3" height="50"/>
            <rect x="120" y="10" width="2" height="50"/>
            <rect x="125" y="10" width="1" height="50"/>
            <rect x="130" y="10" width="2" height="50"/>
            <rect x="135" y="10" width="3" height="50"/>
            <rect x="142" y="10" width="1" height="50"/>
            <rect x="147" y="10" width="2" height="50"/>
            <rect x="153" y="10" width="1" height="50"/>
            <rect x="158" y="10" width="3" height="50"/>
            <rect x="165" y="10" width="2" height="50"/>
            <rect x="170" y="10" width="1" height="50"/>
            <rect x="175" y="10" width="2" height="50"/>
            <rect x="180" y="10" width="3" height="50"/>
            <rect x="187" y="10" width="1" height="50"/>
        </g>
        <text x="100" y="75" text-anchor="middle" font-family="monospace" font-size="12">' . $barcode . '</text>
    </svg>');
}
?>