<?php

/**
 * CSRF Token Generation API
 * Career Path Institute - Shimla
 */

require_once '../../includes/config.php';
require_once '../../includes/security.php';
require_once '../../includes/response.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        ApiResponse::methodNotAllowed();
    }
    
    $csrfToken = SecurityManager::generateCSRFToken();
    
    ApiResponse::success([
        'csrf_token' => $csrfToken
    ], 'CSRF token generated');
    
} catch (Exception $e) {
    ApiResponse::serverError($e->getMessage());
}
?>