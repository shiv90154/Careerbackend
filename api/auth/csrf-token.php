<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }


require_once '../../includes/config.php';
require_once '../../includes/security.php';
require_once '../../includes/response.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        ApiResponse::methodNotAllowed();
    }
    
    $token = SecurityManager::generateCSRFToken();
    
    ApiResponse::success([
        'csrf_token' => $token
    ], 'CSRF token generated successfully');
    
} catch (Exception $e) {
    ApiResponse::error('Failed to generate CSRF token: ' . $e->getMessage());
}
?>