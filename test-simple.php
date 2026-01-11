<?php
/**
 * Simple API Test - No Dependencies
 * Career Path Institute - Shimla
 */

// Set CORS headers manually
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple response
echo json_encode([
    'success' => true,
    'message' => 'Simple API test successful!',
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => [
        'origin' => $_SERVER['HTTP_ORIGIN'] ?? 'No origin',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'No user agent',
        'host' => $_SERVER['HTTP_HOST'] ?? 'No host'
    ],
    'php_info' => [
        'version' => PHP_VERSION,
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]
]);
?>