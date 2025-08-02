<?php
header('Content-Type: application/json');
require_once '../classes/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Validate required fields
$required_fields = ['user_type', 'student_id', 'first_name', 'last_name', 'email', 'password'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required']);
        exit();
    }
}

// Validate email format
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Validate password strength
if (strlen($_POST['password']) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    exit();
}

// For alumni, year_graduated is required
if ($_POST['user_type'] === 'alumni' && empty($_POST['year_graduated'])) {
    echo json_encode(['success' => false, 'message' => 'Year graduated is required for alumni']);
    exit();
}

$user = new User();
$result = $user->register($_POST);

echo json_encode($result);
?>