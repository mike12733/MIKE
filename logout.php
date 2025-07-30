<?php
require_once 'includes/functions.php';

// Log logout activity if user is logged in
if (isLoggedIn()) {
    logActivity('Logout', 'admin_users', $_SESSION['admin_id']);
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>