<?php

require_once '../includes/config.php';

// Simple test response
echo json_encode([
    'success' => true,
    'message' => 'CORS is working correctly!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'No origin header'
    ]
]);
?>