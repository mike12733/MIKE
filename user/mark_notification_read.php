<?php
session_start();
header('Content-Type: application/json');
require_once '../classes/User.php';
require_once '../classes/DocumentRequest.php';

$user = new User();
$docRequest = new DocumentRequest();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['notification_id']) || !is_numeric($input['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

$docRequest->markNotificationAsRead($input['notification_id']);

echo json_encode(['success' => true]);
?>