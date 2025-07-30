<?php
require_once 'includes/functions.php';
require_once 'includes/barcode_generator.php';

// Check if user is logged in
requireLogin();

// Get barcode from query parameter
$barcode = sanitize($_GET['code'] ?? '');

if (empty($barcode)) {
    // Return error image
    header('Content-Type: image/svg+xml');
    echo '<svg width="300" height="100" xmlns="http://www.w3.org/2000/svg"><text x="150" y="50" text-anchor="middle" font-family="Arial" font-size="14" fill="red">Invalid Barcode</text></svg>';
    exit;
}

// Validate barcode format
if (!BarcodeGenerator::validateBarcodeFormat($barcode)) {
    header('Content-Type: image/svg+xml');
    echo '<svg width="300" height="100" xmlns="http://www.w3.org/2000/svg"><text x="150" y="50" text-anchor="middle" font-family="Arial" font-size="14" fill="red">Invalid Format</text></svg>';
    exit;
}

// Generate and output barcode SVG
header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

try {
    echo BarcodeGenerator::generateBarcodeSVG($barcode, 400, 120);
} catch (Exception $e) {
    echo '<svg width="400" height="120" xmlns="http://www.w3.org/2000/svg"><text x="200" y="60" text-anchor="middle" font-family="Arial" font-size="14" fill="red">Error generating barcode</text></svg>';
}
?>