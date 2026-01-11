<?php
/**
 * Send OTP API Endpoint
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
    if (empty($input['email'])) {
        throw new Exception('Email is required');
    }
    
    $email = filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }
    
    $purpose = isset($input['purpose']) ? $input['purpose'] : 'registration';
    $name = isset($input['name']) ? trim($input['name']) : '';
    
    // Validate purpose
    $validPurposes = ['registration', 'password_reset', 'email_change'];
    if (!in_array($purpose, $validPurposes)) {
        throw new Exception('Invalid purpose');
    }
    
    $otpService = new OTPService();
    
    // Check if there's already a pending OTP
    if ($otpService->hasPendingOTP($email, $purpose)) {
        $remainingTime = $otpService->getOTPRemainingTime($email, $purpose);
        
        if ($remainingTime > 300) { // More than 5 minutes remaining
            throw new Exception("Please wait " . ceil($remainingTime / 60) . " minutes before requesting a new OTP");
        }
    }
    
    // For registration, check if email already exists
    if ($purpose === 'registration') {
        require_once '../../includes/database.php';
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Email already registered. Please use login instead.');
        }
    }
    
    // Generate and send OTP
    $result = $otpService->generateAndSendOTP($email, $purpose, $name);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'expires_in' => $result['expires_in']
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