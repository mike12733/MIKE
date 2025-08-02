<?php
session_start();
header('Content-Type: application/json');
require_once '../classes/User.php';

$user = new User();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$currentUser = $user->getCurrentUser();

// Validate required fields
if (empty($_POST['first_name']) || empty($_POST['last_name'])) {
    echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
    exit();
}

$result = $user->updateProfile($currentUser['id'], $_POST);

echo json_encode($result);
?>