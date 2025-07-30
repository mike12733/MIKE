<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid equipment ID']);
    exit;
}

$equipment_id = (int)$_GET['id'];

try {
    // Get location history for the equipment
    $history = getLocationHistory($equipment_id, 50);
    
    // Format dates for display
    foreach ($history as &$record) {
        $record['created_at'] = formatDateTime($record['created_at']);
    }
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching location history: ' . $e->getMessage()
    ]);
}
?>