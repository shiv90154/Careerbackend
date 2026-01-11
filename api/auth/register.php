<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



/**
 * Secure User Registration API with OTP Verification
 * Career Path Institute - Shimla
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/validation.php';
require_once '../../includes/response.php';
require_once '../../includes/logger.php';
require_once '../../includes/email-service.php';

try {
    // Rate limiting
    $clientIP = SecurityManager::getClientIP();
    if (!SecurityManager::checkRateLimit("register_$clientIP", 5, 15)) {
        ApiResponse::rateLimitExceeded('Too many registration attempts. Please try again later.');
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
        ->required(['full_name', 'email', 'password', 'phone', 'otp'])
        ->email('email')
        ->password('password')
        ->phone('phone')
        ->length('full_name', 2, 100)
        ->custom('otp', function($otp) {
            return preg_match('/^\d{6}$/', $otp);
        }, 'OTP must be 6 digits')
        ->validate();
    
    $db = Database::getInstance()->getConnection();
    
    // Check if email already exists
    $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
    $checkEmailStmt = $db->prepare($checkEmailQuery);
    $checkEmailStmt->execute([$data['email']]);
    
    if ($checkEmailStmt->fetch()) {
        ApiResponse::error('Email already registered', 409);
    }
    
    // Verify OTP
    $otpQuery = "
        SELECT id, expires_at 
        FROM email_otps 
        WHERE email = ? AND otp_code = ? AND purpose = 'registration' AND is_used = 0
        ORDER BY created_at DESC 
        LIMIT 1
    ";
    $otpStmt = $db->prepare($otpQuery);
    $otpStmt->execute([$data['email'], $data['otp']]);
    $otpRecord = $otpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$otpRecord) {
        Logger::warning("Invalid OTP attempt", [
            'email' => $data['email'],
            'otp' => $data['otp'],
            'ip' => $clientIP
        ]);
        ApiResponse::error('Invalid or expired OTP');
    }
    
    // Check OTP expiration
    if (strtotime($otpRecord['expires_at']) < time()) {
        ApiResponse::error('OTP has expired');
    }
    
    $db->beginTransaction();
    
    try {
        // Mark OTP as used
        $updateOtpQuery = "UPDATE email_otps SET is_used = 1 WHERE id = ?";
        $updateOtpStmt = $db->prepare($updateOtpQuery);
        $updateOtpStmt->execute([$otpRecord['id']]);
        
        // Hash password
        $hashedPassword = SecurityManager::hashPassword($data['password']);
        
        // Create user
        $createUserQuery = "
            INSERT INTO users (
                full_name, email, password, phone, role, 
                is_active, email_verified, created_at
            ) VALUES (?, ?, ?, ?, 'student', 1, 1, NOW())
        ";
        $createUserStmt = $db->prepare($createUserQuery);
        $createUserStmt->execute([
            $data['full_name'],
            $data['email'],
            $hashedPassword,
            $data['phone']
        ]);
        
        $userId = $db->lastInsertId();
        
        $db->commit();
        
        // Send welcome email
        $emailService = EmailService::getInstance();
        $emailService->sendWelcomeEmail($data['email'], $data['full_name']);
        
        Logger::info("User registered successfully", [
            'user_id' => $userId,
            'email' => $data['email'],
            'ip' => $clientIP
        ]);
        
        ApiResponse::success([
            'user_id' => $userId,
            'message' => 'Registration successful'
        ], 'Account created successfully');
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    Logger::error("Registration error: " . $e->getMessage(), [
        'input' => $input ?? null,
        'ip' => $clientIP ?? 'unknown'
    ]);
    
    ApiResponse::serverError($e->getMessage());
}
?>