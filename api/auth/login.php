<?php

/**
 * Secure User Login API
 * Career Path Institute - Shimla
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/validation.php';
require_once '../../includes/response.php';
require_once '../../includes/logger.php';
require_once '../../includes/jwt.php';

try {
    // Rate limiting
    $clientIP = SecurityManager::getClientIP();
    if (!SecurityManager::checkRateLimit("login_$clientIP", 10, 15)) {
        ApiResponse::rateLimitExceeded('Too many login attempts. Please try again later.');
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::methodNotAllowed();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        ApiResponse::error('Invalid JSON input');
    }
    
    // Validate CSRF token
    if (!isset($input['csrf_token'])) {
        ApiResponse::error('CSRF token required');
    }
    
    SecurityManager::validateCSRFToken($input['csrf_token']);
    
    // Validate input
    $validator = new Validator($input);
    $data = $validator
        ->required(['email', 'password'])
        ->email('email')
        ->length('password', 1, 255)
        ->validate();
    
    // Check login attempts
    SecurityManager::checkLoginAttempts($data['email']);
    
    $db = Database::getInstance()->getConnection();
    
    // Get user
    $userQuery = "
        SELECT id, full_name, email, password, role, is_active, email_verified, last_login
        FROM users 
        WHERE email = ? AND is_active = 1
        LIMIT 1
    ";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute([$data['email']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !SecurityManager::verifyPassword($data['password'], $user['password'])) {
        SecurityManager::recordFailedLogin($data['email']);
        
        Logger::warning("Failed login attempt", [
            'email' => $data['email'],
            'ip' => $clientIP,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        ApiResponse::unauthorized('Invalid email or password');
    }
    
    // Check if email is verified
    if (!$user['email_verified']) {
        ApiResponse::error('Please verify your email before logging in', 403);
    }
    
    // Reset login attempts on successful login
    SecurityManager::resetLoginAttempts($data['email']);
    
    // Update last login
    $updateLoginQuery = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $updateLoginStmt = $db->prepare($updateLoginQuery);
    $updateLoginStmt->execute([$user['id']]);
    
    // Generate JWT token
    $tokenPayload = [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + JWT_EXPIRY
    ];
    
    $token = JWT::encode($tokenPayload, JWT_SECRET, JWT_ALGORITHM);
    
    // Log successful login
    Logger::info("User logged in successfully", [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'ip' => $clientIP
    ]);
    
    ApiResponse::success([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'last_login' => $user['last_login']
        ]
    ], 'Login successful');
    
} catch (Exception $e) {
    Logger::error("Login error: " . $e->getMessage(), [
        'input' => $input ?? null,
        'ip' => $clientIP ?? 'unknown'
    ]);
    
    ApiResponse::serverError($e->getMessage());
}
?>
