<?php
// Redirect to login page or dashboard based on authentication status
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>