<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }


require_once '../../includes/config.php';
require_once '../../includes/response.php';
require_once '../../includes/logger.php';

try {
    Logger::logRequest();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::methodNotAllowed();
    }
    
    // Clear any server-side sessions if using them
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Clear httpOnly cookies if using them
    if (isset($_COOKIE['auth_token'])) {
        setcookie('auth_token', '', time() - 3600, '/', '', false, true);
    }
    
    Logger::info('User logged out', [
        'ip' => SecurityManager::getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    ApiResponse::success([], 'Logged out successfully');
    
} catch (Exception $e) {
    Logger::error("Logout error: " . $e->getMessage());
    ApiResponse::error('Logout failed: ' . $e->getMessage());
}
?>