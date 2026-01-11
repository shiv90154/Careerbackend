<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }


require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/response.php';
require_once '../../includes/logger.php';

try {
    Logger::logRequest();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        ApiResponse::methodNotAllowed();
    }

    // Get Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        ApiResponse::unauthorized('Authentication token required');
    }
    
    $token = $matches[1];
    
    try {
        $decoded = JWT::decode($token, JWT_SECRET, [JWT_ALGORITHM]);
        
        // Get user from database
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("
            SELECT id, role, full_name, email, phone, is_active, email_verified, last_login, created_at
            FROM users 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$decoded->user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            ApiResponse::unauthorized('User not found or inactive');
        }
        
        // Update last login
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        ApiResponse::success([
            'user' => [
                'id' => (int)$user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'is_active' => (bool)$user['is_active'],
                'email_verified' => (bool)$user['email_verified'],
                'last_login' => $user['last_login'],
                'created_at' => $user['created_at']
            ]
        ], 'User authenticated successfully');
        
    } catch (Exception $e) {
        Logger::warning("Authentication failed: " . $e->getMessage(), [
            'token' => substr($token, 0, 20) . '...',
            'ip' => SecurityManager::getClientIP()
        ]);
        
        ApiResponse::unauthorized('Invalid or expired token');
    }

} catch (Exception $e) {
    Logger::error("Auth me error: " . $e->getMessage());
    ApiResponse::unauthorized('Authentication failed');
}
?>