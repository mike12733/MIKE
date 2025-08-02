<?php
header('Content-Type: application/json');
require_once '../classes/User.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

$user = new User();
$result = $user->login($email, $password);

if ($result['success']) {
    $result['user_type'] = $result['user']['user_type'];
}

echo json_encode($result);
?>