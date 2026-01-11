<?php

/**
 * User Logout API
 * Career Path Institute - Shimla
 */

require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/security.php';
require_once '../../includes/response.php';
require_once '../../includes/logger.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::methodNotAllowed();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['csrf_token'])) {
        ApiResponse::error('CSRF token required');
    }
    
    SecurityManager::validateCSRFToken($input['csrf_token']);
    
    // Try to get user (but don't fail if not authenticated)
    try {
        $user = Auth::authenticate();
        $userId = $user['id'];
    } catch (Exception $e) {
        $userId = null;
    }
    
    // Clear session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    
    // Clear any authentication cookies
    if (isset($_COOKIE['auth_token'])) {
        setcookie('auth_token', '', time() - 3600, '/', '', true, true);
    }
    
    Logger::info("User logged out", [
        'user_id' => $userId,
        'ip' => SecurityManager::getClientIP()
    ]);
    
    ApiResponse::success(null, 'Logged out successfully');
    
} catch (Exception $e) {
    Logger::error("Logout error: " . $e->getMessage());
    ApiResponse::serverError($e->getMessage());
}
?>