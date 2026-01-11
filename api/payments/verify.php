<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    $user = getCurrentUser($db);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    if ($method === 'POST') {
        handlePaymentVerification($db, $user);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handlePaymentVerification($db, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $payment_id = (int)($input['payment_id'] ?? 0);
    $razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
    $razorpay_order_id = $input['razorpay_order_id'] ?? '';
    $razorpay_signature = $input['razorpay_signature'] ?? '';
    
    if (!$payment_id || !$razorpay_payment_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Payment details are required']);
        return;
    }
    
    // Get payment details
    $paymentQuery = "
        SELECT p.*, e.id as enrollment_id 
        FROM payments p 
        LEFT JOIN enrollments e ON p.enrollment_id = e.id
        WHERE p.id = ? AND p.user_id = ? AND p.status = 'pending'
    ";
    $paymentStmt = $db->prepare($paymentQuery);
    $paymentStmt->execute([$payment_id, $user['id']]);
    $payment = $paymentStmt->fetch();
<?php
/**
 * Secure Payment Verification API
 * Career Path Institute - Shimla
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/validation.php';
require_once '../../includes/response.php';
require_once '../../includes/logger.php';
require_once '../../includes/email-service.php';
require_once '../../includes/auth.php';

try {
    // Rate limiting
    $clientIP = SecurityManager::getClientIP();
    if (!SecurityManager::checkRateLimit("payment_verify_$clientIP", 20, 15)) {
        ApiResponse::rateLimitExceeded('Too many payment verification attempts. Please try again later.');
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
    
    // Authenticate user
    $user = Auth::authenticate();
    
    // Validate input
    $validator = new Validator($input);
    $data = $validator
        ->required(['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'])
        ->length('razorpay_order_id', 10, 50)
        ->length('razorpay_payment_id', 10, 50)
        ->length('razorpay_signature', 10, 200)
        ->validate();
    
    $db = Database::getInstance()->getConnection();
    
    // Get payment record
    $paymentQuery = "
        SELECT p.*, c.title as course_title, u.full_name, u.email
        FROM payments p
        LEFT JOIN courses c ON p.course_id = c.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.razorpay_order_id = ? AND p.user_id = ? AND p.status = 'pending'
        LIMIT 1
    ";
    $paymentStmt = $db->prepare($paymentQuery);
    $paymentStmt->execute([$data['razorpay_order_id'], $user['id']]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        Logger::warning("Payment verification attempted for non-existent or processed payment", [
            'user_id' => $user['id'],
            'razorpay_order_id' => $data['razorpay_order_id'],
            'ip' => $clientIP
        ]);
        ApiResponse::notFound('Payment not found or already processed');
    }
    
    // Verify Razorpay signature
    $isSignatureValid = verifyRazorpaySignature(
        $data['razorpay_order_id'],
        $data['razorpay_payment_id'],
        $data['razorpay_signature']
    );
    
    if (!$isSignatureValid) {
        Logger::security("Invalid Razorpay signature detected", [
            'user_id' => $user['id'],
            'payment_id' => $payment['id'],
            'razorpay_order_id' => $data['razorpay_order_id'],
            'razorpay_payment_id' => $data['razorpay_payment_id'],
            'ip' => $clientIP
        ]);
        ApiResponse::error('Payment verification failed', 400);
    }
    
    $db->beginTransaction();
    
    try {
        // Update payment status
        $updatePaymentQuery = "
            UPDATE payments 
            SET status = 'completed', 
                razorpay_payment_id = ?, 
                razorpay_signature = ?,
                completed_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ";
        $updatePaymentStmt = $db->prepare($updatePaymentQuery);
        $updatePaymentStmt->execute([
            $data['razorpay_payment_id'],
            $data['razorpay_signature'],
            $payment['id']
        ]);
        
        // Update enrollment status
        if ($payment['enrollment_id']) {
            $updateEnrollmentQuery = "
                UPDATE enrollments 
                SET payment_status = 'completed', 
                    status = 'active',
                    updated_at = NOW()
                WHERE id = ?
            ";
            $updateEnrollmentStmt = $db->prepare($updateEnrollmentQuery);
            $updateEnrollmentStmt->execute([$payment['enrollment_id']]);
        }
        
        // Update coupon usage if applicable
        if ($payment['coupon_id']) {
            $updateCouponQuery = "
                UPDATE coupons 
                SET used_count = used_count + 1,
                    updated_at = NOW()
                WHERE id = ?
            ";
            $updateCouponStmt = $db->prepare($updateCouponQuery);
            $updateCouponStmt->execute([$payment['coupon_id']]);
        }
        
        $db->commit();
        
        // Send confirmation emails
        $emailService = EmailService::getInstance();
        
        // Payment confirmation
        $emailService->sendPaymentConfirmation(
            $payment['email'],
            $payment['full_name'],
            $payment['course_title'] ?? 'Course',
            $payment['final_amount'],
            $data['razorpay_payment_id']
        );
        
        // Enrollment confirmation if course enrollment
        if ($payment['course_id']) {
            $emailService->sendEnrollmentConfirmation(
                $payment['email'],
                $payment['full_name'],
                $payment['course_title']
            );
        }
        
        Logger::info("Payment verified successfully", [
            'user_id' => $user['id'],
            'payment_id' => $payment['id'],
            'amount' => $payment['final_amount'],
            'razorpay_payment_id' => $data['razorpay_payment_id'],
            'ip' => $clientIP
        ]);
        
        ApiResponse::success([
            'payment_id' => $payment['id'],
            'transaction_id' => $data['razorpay_payment_id'],
            'amount' => $payment['final_amount'],
            'status' => 'completed'
        ], 'Payment verified successfully');
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    Logger::error("Payment verification error: " . $e->getMessage(), [
        'input' => $input ?? null,
        'user_id' => $user['id'] ?? null,
        'ip' => $clientIP ?? 'unknown'
    ]);
    
    ApiResponse::serverError($e->getMessage());
}

/**
 * Verify Razorpay payment signature
 */
function verifyRazorpaySignature($orderId, $paymentId, $signature) {
    if (empty(RAZORPAY_KEY_SECRET)) {
        Logger::warning("Razorpay key secret not configured");
        return false; // In development, you might want to return true for testing
    }
    
    $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, RAZORPAY_KEY_SECRET);
    
    return hash_equals($expectedSignature, $signature);
}
?>
    
    if (!$signature_valid) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payment signature']);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        // Update payment status
        $updatePaymentQuery = "
            UPDATE payments 
            SET status = 'completed', transaction_id = ?, payment_date = NOW(), updated_at = NOW()
            WHERE id = ?
        ";
        $updatePaymentStmt = $db->prepare($updatePaymentQuery);
        $updatePaymentStmt->execute([$razorpay_payment_id, $payment_id]);
        
        // Update enrollment status if exists
        if ($payment['enrollment_id']) {
            $updateEnrollmentQuery = "
                UPDATE enrollments 
                SET payment_status = 'paid', status = 'active', updated_at = NOW()
                WHERE id = ?
            ";
            $updateEnrollmentStmt = $db->prepare($updateEnrollmentQuery);
            $updateEnrollmentStmt->execute([$payment['enrollment_id']]);
        }
        
        // Create notification
        $notificationQuery = "
            INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $notificationStmt = $db->prepare($notificationQuery);
        $notificationStmt->execute([
            $user['id'],
            'Payment Successful',
            'Your payment has been processed successfully. You now have access to the premium content.',
            'payment',
            $payment['related_id'],
            $payment['related_type']
        ]);
        
        // Log activity
        $activityQuery = "
            INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description)
            VALUES (?, ?, ?, ?, ?)
        ";
        $activityStmt = $db->prepare($activityQuery);
        $activityStmt->execute([
            $user['id'],
            'payment_completed',
            $payment['related_type'],
            $payment['related_id'],
            "Payment completed for {$payment['related_type']} ID: {$payment['related_id']}"
        ]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully',
            'payment_id' => $payment_id,
            'access_granted' => true
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

function verifyRazorpaySignature($order_id, $payment_id, $signature) {
    // In a real implementation, verify using Razorpay's webhook signature
    // $expected_signature = hash_hmac('sha256', $order_id . "|" . $payment_id, $razorpay_secret);
    // return hash_equals($expected_signature, $signature);
    
    // For demo purposes, return true
    return true;
}
?>