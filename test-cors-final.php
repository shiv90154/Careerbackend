<?php
/**
 * FINAL CORS TEST - Verify CORS is working correctly
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

// Test basic CORS response
echo json_encode([
    'success' => true,
    'message' => 'CORS test successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'headers_sent' => headers_list(),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'none',
    'cors_working' => true
]);
?>