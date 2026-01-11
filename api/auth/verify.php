<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

try {
    $user = Auth::requireAuth();
    
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT id, role, full_name, email, profile_image FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    
    if (!$userData) {
        http_response_code(401);
        echo json_encode(['message' => 'User not found']);
        exit;
    }
    
    echo json_encode([
        'user' => [
            'id' => $userData['id'],
            'name' => $userData['full_name'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'profile_image' => $userData['profile_image']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['message' => 'Authentication failed']);
}
?>