<?php

/**
 * Get Current User API
 * Career Path Institute - Shimla
 */

require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/response.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        ApiResponse::methodNotAllowed();
    }
    
    $user = Auth::authenticate();
    
    ApiResponse::success([
        'user' => [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'last_login' => $user['last_login'],
            'email_verified' => $user['email_verified']
        ]
    ], 'User authenticated');
    
} catch (Exception $e) {
    ApiResponse::unauthorized('Not authenticated');
}
?>