<?php
session_start();
header('Content-Type: application/json');
require_once '../classes/User.php';
require_once '../classes/DocumentRequest.php';

$user = new User();
$docRequest = new DocumentRequest();

// Check if user is logged in and is admin
if (!$user->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$currentUser = $user->getCurrentUser();

if ($currentUser['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['request_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Request ID and status are required']);
    exit();
}

$requestId = $input['request_id'];
$newStatus = $input['status'];
$notes = $input['notes'] ?? null;
$rejectionReason = $input['rejection_reason'] ?? null;

// Validate status
$validStatuses = ['pending', 'processing', 'approved', 'denied', 'ready_for_pickup', 'completed'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$result = $docRequest->updateRequestStatus($requestId, $newStatus, $currentUser['id'], $notes, $rejectionReason);

echo json_encode($result);
?>