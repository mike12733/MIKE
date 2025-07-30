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

// Generate unique barcode with better format
function generateBarcode() {
    return 'EQ' . date('Y') . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

// Generate enhanced barcode image using Code128 format
function generateBarcodeImage($barcode) {
    // Create a more realistic barcode SVG
    $width = 300;
    $height = 100;
    $barWidth = 2;
    $bars = [];
    
    // Simple pattern for demonstration - in production, use proper Code128 encoding
    $pattern = str_split(md5($barcode));
    $x = 20;
    
    foreach ($pattern as $i => $char) {
        if ($i % 2 == 0) {
            $bars[] = '<rect x="' . $x . '" y="15" width="' . $barWidth . '" height="50" fill="black"/>';
        }
        $x += $barWidth + 1;
        if ($x > $width - 40) break;
    }
    
    $svg = '
    <svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">
        <rect width="' . $width . '" height="' . $height . '" fill="white" stroke="#ccc"/>
        ' . implode('', $bars) . '
        <text x="' . ($width/2) . '" y="85" text-anchor="middle" font-family="Arial" font-size="12" fill="black">' . $barcode . '</text>
    </svg>';
    
    return "data:image/svg+xml;base64," . base64_encode($svg);
}

// Update equipment location via barcode scan
function updateEquipmentLocation($barcode, $new_location, $notes = '') {
    global $db;
    
    if (!isLoggedIn()) return false;
    
    try {
        // Get current equipment data
        $equipment = $db->fetch("SELECT * FROM equipment WHERE barcode = ?", [$barcode]);
        
        if (!$equipment) {
            return ['success' => false, 'message' => 'Equipment not found with barcode: ' . $barcode];
        }
        
        $previous_location = $equipment['location'];
        
        // Update equipment location and scan info
        $db->query("UPDATE equipment SET location = ?, last_scanned_at = NOW(), last_scanned_by = ? WHERE barcode = ?", 
                  [$new_location, $_SESSION['admin_id'], $barcode]);
        
        // Record location history
        $db->query("INSERT INTO location_history (equipment_id, previous_location, new_location, scanned_by, notes) VALUES (?, ?, ?, ?, ?)",
                  [$equipment['id'], $previous_location, $new_location, $_SESSION['admin_id'], $notes]);
        
        // Log activity
        logActivity('Location Update via Barcode', 'equipment', $equipment['id'], 
                   ['location' => $previous_location], 
                   ['location' => $new_location, 'barcode' => $barcode]);
        
        return [
            'success' => true, 
            'message' => "Location updated successfully for {$equipment['item_name']}",
            'equipment' => $equipment,
            'previous_location' => $previous_location,
            'new_location' => $new_location
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error updating location: ' . $e->getMessage()];
    }
}

// Get equipment by barcode
function getEquipmentByBarcode($barcode) {
    global $db;
    return $db->fetch("SELECT * FROM equipment WHERE barcode = ?", [$barcode]);
}

// Get location history for equipment
function getLocationHistory($equipment_id, $limit = 10) {
    global $db;
    
    $sql = "SELECT lh.*, au.full_name as scanned_by_name 
            FROM location_history lh 
            LEFT JOIN admin_users au ON lh.scanned_by = au.id 
            WHERE lh.equipment_id = ? 
            ORDER BY lh.created_at DESC 
            LIMIT ?";
    
    return $db->fetchAll($sql, [$equipment_id, $limit]);
}

// Get recent location changes
function getRecentLocationChanges($limit = 20) {
    global $db;
    
    $sql = "SELECT lh.*, e.item_name, e.barcode, au.full_name as scanned_by_name
            FROM location_history lh
            LEFT JOIN equipment e ON lh.equipment_id = e.id
            LEFT JOIN admin_users au ON lh.scanned_by = au.id
            ORDER BY lh.created_at DESC
            LIMIT ?";
    
    return $db->fetchAll($sql, [$limit]);
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

// Get location statistics
function getLocationStats() {
    global $db;
    
    $locations = $db->fetchAll("SELECT location, COUNT(*) as count FROM equipment WHERE location IS NOT NULL AND location != '' GROUP BY location ORDER BY count DESC");
    return $locations;
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

// Check if barcode exists
function barcodeExists($barcode, $exclude_id = null) {
    global $db;
    
    $sql = "SELECT id FROM equipment WHERE barcode = ?";
    $params = [$barcode];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    return $db->fetch($sql, $params) !== false;
}
?>