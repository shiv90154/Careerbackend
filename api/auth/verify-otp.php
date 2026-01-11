<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }


/**
 * Verify OTP API Endpoint
 * Career Pathway Shimla
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../../includes/otp-service.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate required fields
    if (empty($input['email']) || empty($input['otp'])) {
        throw new Exception('Email and OTP are required');
    }
    
    $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }
    
    $otp = trim($input['otp']);
    if (!preg_match('/^\d{6}$/', $otp)) {
        throw new Exception('OTP must be 6 digits');
    }
    
    $purpose = isset($input['purpose']) ? $input['purpose'] : 'registration';
    
    // Validate purpose
    $validPurposes = ['registration', 'password_reset', 'email_change'];
    if (!in_array($purpose, $validPurposes)) {
        throw new Exception('Invalid purpose');
    }
    
    $otpService = new OTPService();
    
    // Verify OTP
    $result = $otpService->verifyOTP($email, $otp, $purpose);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>