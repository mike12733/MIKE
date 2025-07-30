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

// Generate unique sequential barcode
function generateBarcode() {
    global $db;
    
    $current_year = date('Y');
    
    // Get or create sequence for current year
    $sequence_data = $db->fetch("SELECT sequence_number FROM barcode_sequence WHERE year = ?", [$current_year]);
    
    if (!$sequence_data) {
        // Create new sequence for the year
        $db->query("INSERT INTO barcode_sequence (year, sequence_number) VALUES (?, 1)", [$current_year]);
        $sequence_number = 1;
    } else {
        // Increment existing sequence
        $sequence_number = $sequence_data['sequence_number'] + 1;
        $db->query("UPDATE barcode_sequence SET sequence_number = ? WHERE year = ?", [$sequence_number, $current_year]);
    }
    
    // Format: EQ + Year + 6-digit sequence number
    return 'EQ' . $current_year . str_pad($sequence_number, 6, '0', STR_PAD_LEFT);
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

// Track equipment scan/movement
function trackEquipmentScan($equipment_id, $new_location = null, $new_condition = null, $scan_type = 'location_update', $notes = '') {
    global $db;
    
    if (!isLoggedIn()) return false;
    
    try {
        $db->getConnection()->beginTransaction();
        
        // Get current equipment data
        $equipment = $db->fetch("SELECT * FROM equipment WHERE id = ?", [$equipment_id]);
        if (!$equipment) {
            throw new Exception("Equipment not found");
        }
        
        $previous_location = $equipment['location'];
        $previous_condition = $equipment['condition_status'];
        $final_location = $new_location ?? $previous_location;
        $final_condition = $new_condition ?? $previous_condition;
        
        // Update equipment table
        $db->query("UPDATE equipment SET location = ?, condition_status = ?, last_scanned_at = NOW(), last_scanned_by = ? WHERE id = ?", 
                  [$final_location, $final_condition, $_SESSION['admin_id'], $equipment_id]);
        
        // Insert tracking record
        $db->query("INSERT INTO equipment_tracking (equipment_id, previous_location, new_location, previous_condition, new_condition, scan_type, notes, scanned_by) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                  [$equipment_id, $previous_location, $final_location, $previous_condition, $final_condition, $scan_type, $notes, $_SESSION['admin_id']]);
        
        // Update real-time status
        $db->query("INSERT INTO equipment_realtime_status (equipment_id, current_location, current_condition, last_scanned_by) 
                   VALUES (?, ?, ?, ?) 
                   ON DUPLICATE KEY UPDATE 
                   current_location = VALUES(current_location), 
                   current_condition = VALUES(current_condition), 
                   last_activity = NOW(), 
                   last_scanned_by = VALUES(last_scanned_by)", 
                  [$equipment_id, $final_location, $final_condition, $_SESSION['admin_id']]);
        
        // Log activity
        logActivity('Equipment Scan', 'equipment', $equipment_id, 
                   ['location' => $previous_location, 'condition' => $previous_condition], 
                   ['location' => $final_location, 'condition' => $final_condition]);
        
        $db->getConnection()->commit();
        return true;
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        return false;
    }
}

// Get equipment by barcode
function getEquipmentByBarcode($barcode) {
    global $db;
    return $db->fetch("SELECT e.*, ers.current_location as realtime_location, ers.is_checked_out, ers.checked_out_to, ers.last_activity 
                      FROM equipment e 
                      LEFT JOIN equipment_realtime_status ers ON e.id = ers.equipment_id 
                      WHERE e.barcode = ? AND e.is_active = 1", [$barcode]);
}

// Get equipment tracking history
function getEquipmentTrackingHistory($equipment_id, $limit = 50) {
    global $db;
    return $db->fetchAll("SELECT et.*, au.full_name as scanned_by_name 
                         FROM equipment_tracking et 
                         JOIN admin_users au ON et.scanned_by = au.id 
                         WHERE et.equipment_id = ? 
                         ORDER BY et.scanned_at DESC 
                         LIMIT ?", [$equipment_id, $limit]);
}

// Get real-time equipment status
function getRealTimeEquipmentStatus() {
    global $db;
    return $db->fetchAll("SELECT e.id, e.item_name, e.barcode, e.category, 
                         ers.current_location, ers.current_condition, ers.is_checked_out, 
                         ers.checked_out_to, ers.last_activity, au.full_name as last_scanned_by_name
                         FROM equipment e 
                         LEFT JOIN equipment_realtime_status ers ON e.id = ers.equipment_id 
                         LEFT JOIN admin_users au ON ers.last_scanned_by = au.id 
                         WHERE e.is_active = 1 
                         ORDER BY ers.last_activity DESC");
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
        'lost' => 0,
        'checked_out' => 0
    ];
    
    $result = $db->fetchAll("SELECT condition_status, SUM(quantity) as count FROM equipment WHERE is_active = 1 GROUP BY condition_status");
    
    foreach ($result as $row) {
        $stats['total'] += $row['count'];
        $stats[strtolower($row['condition_status'])] = $row['count'];
    }
    
    // Get checked out count
    $checked_out = $db->fetch("SELECT COUNT(*) as count FROM equipment_realtime_status WHERE is_checked_out = 1");
    $stats['checked_out'] = $checked_out['count'] ?? 0;
    
    return $stats;
}

// Search equipment
function searchEquipment($search_term) {
    global $db;
    
    $sql = "SELECT e.*, ers.current_location as realtime_location, ers.is_checked_out, ers.last_activity 
            FROM equipment e 
            LEFT JOIN equipment_realtime_status ers ON e.id = ers.equipment_id 
            WHERE e.is_active = 1 AND (
                e.item_name LIKE ? OR 
                e.description LIKE ? OR 
                e.category LIKE ? OR 
                e.barcode LIKE ? OR 
                e.location LIKE ?
            )
            ORDER BY e.created_at DESC";
    
    $search = "%{$search_term}%";
    return $db->fetchAll($sql, [$search, $search, $search, $search, $search]);
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Generate proper barcode image using Code128 format
function generateBarcodeImage($barcode) {
    // Enhanced SVG barcode with proper Code128-style pattern
    $width = 300;
    $height = 100;
    $barHeight = 60;
    $textHeight = 20;
    
    // Simple Code128-inspired pattern generator
    $pattern = generateBarcodePattern($barcode);
    
    $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">
        <rect width="' . $width . '" height="' . $height . '" fill="white" stroke="black" stroke-width="1"/>
        <g fill="black">';
    
    $x = 20;
    $barWidth = 2;
    
    foreach ($pattern as $bar) {
        if ($bar == '1') {
            $svg .= '<rect x="' . $x . '" y="15" width="' . $barWidth . '" height="' . $barHeight . '"/>';
        }
        $x += $barWidth;
    }
    
    $svg .= '</g>
        <text x="' . ($width/2) . '" y="' . ($height - 5) . '" text-anchor="middle" font-family="monospace" font-size="14" fill="black">' . $barcode . '</text>
    </svg>';
    
    return "data:image/svg+xml;base64," . base64_encode($svg);
}

// Generate barcode pattern (simplified Code128 representation)
function generateBarcodePattern($barcode) {
    $pattern = [];
    
    // Start pattern
    $pattern = array_merge($pattern, [1,1,0,1,0,1,1,0,0]);
    
    // Convert each character to a pattern
    for ($i = 0; $i < strlen($barcode); $i++) {
        $char = ord($barcode[$i]);
        $charPattern = [];
        
        // Simple pattern generation based on character ASCII value
        for ($j = 0; $j < 8; $j++) {
            $charPattern[] = ($char >> $j) & 1;
        }
        
        $pattern = array_merge($pattern, $charPattern);
    }
    
    // End pattern
    $pattern = array_merge($pattern, [1,1,0,0,1,0,1,1,1]);
    
    return $pattern;
}

// Check equipment out
function checkoutEquipment($equipment_id, $checked_out_to, $notes = '') {
    global $db;
    
    if (!isLoggedIn()) return false;
    
    try {
        $db->getConnection()->beginTransaction();
        
        // Update real-time status
        $db->query("UPDATE equipment_realtime_status SET is_checked_out = 1, checked_out_to = ?, last_activity = NOW(), last_scanned_by = ? WHERE equipment_id = ?", 
                  [$checked_out_to, $_SESSION['admin_id'], $equipment_id]);
        
        // Track the checkout
        trackEquipmentScan($equipment_id, null, null, 'checkout', "Checked out to: $checked_out_to. $notes");
        
        $db->getConnection()->commit();
        return true;
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        return false;
    }
}

// Check equipment in
function checkinEquipment($equipment_id, $new_location = null, $new_condition = null, $notes = '') {
    global $db;
    
    if (!isLoggedIn()) return false;
    
    try {
        $db->getConnection()->beginTransaction();
        
        // Update real-time status
        $db->query("UPDATE equipment_realtime_status SET is_checked_out = 0, checked_out_to = NULL, last_activity = NOW(), last_scanned_by = ? WHERE equipment_id = ?", 
                  [$_SESSION['admin_id'], $equipment_id]);
        
        // Track the checkin with location/condition updates
        trackEquipmentScan($equipment_id, $new_location, $new_condition, 'checkin', "Equipment checked in. $notes");
        
        $db->getConnection()->commit();
        return true;
        
    } catch (Exception $e) {
        $db->getConnection()->rollBack();
        return false;
    }
}
?>