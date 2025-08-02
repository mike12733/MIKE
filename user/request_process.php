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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$currentUser = $user->getCurrentUser();

// Validate required fields
if (empty($_POST['document_type_id']) || empty($_POST['purpose'])) {
    echo json_encode(['success' => false, 'message' => 'Document type and purpose are required']);
    exit();
}

// Prepare request data
$requestData = [
    'user_id' => $currentUser['id'],
    'document_type_id' => $_POST['document_type_id'],
    'purpose' => $_POST['purpose'],
    'preferred_release_date' => !empty($_POST['preferred_release_date']) ? $_POST['preferred_release_date'] : null
];

// Create the request
$result = $docRequest->createRequest($requestData);

if ($result['success']) {
    $requestId = $result['request_id'];
    
    // Handle file uploads if any
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        $uploadErrors = [];
        
        for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['attachments']['name'][$i],
                    'type' => $_FILES['attachments']['type'][$i],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                    'size' => $_FILES['attachments']['size'][$i]
                ];
                
                $uploadResult = $docRequest->uploadAttachment($requestId, $file);
                if (!$uploadResult['success']) {
                    $uploadErrors[] = $uploadResult['message'];
                }
            }
        }
        
        if (!empty($uploadErrors)) {
            $result['message'] .= ' However, some files failed to upload: ' . implode(', ', $uploadErrors);
        }
    }
}

echo json_encode($result);
?>