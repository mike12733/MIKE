<?php
session_start();
require_once '../classes/User.php';
require_once '../classes/DocumentRequest.php';

$user = new User();
$docRequest = new DocumentRequest();

// Check if user is logged in and is admin
if (!$user->isLoggedIn()) {
    header('Location: ../lnhs_index.php');
    exit();
}

$currentUser = $user->getCurrentUser();

if ($currentUser['user_type'] !== 'admin') {
    header('Location: ../user/dashboard.php');
    exit();
}

// Get all requests
$requests = $docRequest->getAllRequests();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="lnhs_document_requests_' . date('Y-m-d') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, [
    'Request Number',
    'Student Name',
    'Student ID',
    'Email',
    'Contact Number',
    'Document Type',
    'Purpose',
    'Status',
    'Submitted Date',
    'Preferred Release Date',
    'Admin Notes',
    'Rejection Reason'
]);

// Write data rows
foreach ($requests as $request) {
    fputcsv($output, [
        $request['request_number'],
        $request['user_name'],
        $request['student_id'],
        $request['email'],
        $request['contact_number'],
        $request['document_type_name'],
        $request['purpose'],
        ucfirst(str_replace('_', ' ', $request['status'])),
        date('Y-m-d H:i:s', strtotime($request['created_at'])),
        $request['preferred_release_date'] ? date('Y-m-d', strtotime($request['preferred_release_date'])) : '',
        $request['admin_notes'] ?? '',
        $request['rejection_reason'] ?? ''
    ]);
}

// Close output stream
fclose($output);
exit();
?>