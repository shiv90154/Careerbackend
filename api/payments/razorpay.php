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

$user = Auth::requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db = (new Database())->getConnection();

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        if ($data['action'] === 'create_order') {
            // Create Razorpay order
            $courseId = $data['course_id'];
            $couponCode = $data['coupon_code'] ?? '';
            
            // Get course details
            $courseStmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND status = 'published'");
            $courseStmt->execute([$courseId]);
            $course = $courseStmt->fetch();
            
            if (!$course) {
                http_response_code(404);
                echo json_encode(['message' => 'Course not found']);
                exit;
            }
            
            $amount = $course['discount_price'] ?: $course['price'];
            $discountAmount = 0;
            $couponId = null;
            
            // Apply coupon if provided
            if ($couponCode) {
                $couponStmt = $db->prepare("
                    SELECT * FROM coupons 
                    WHERE code = ? AND is_active = 1 
                    AND (valid_until IS NULL OR valid_until > NOW())
                    AND (usage_limit IS NULL OR used_count < usage_limit)
                ");
                $couponStmt->execute([$couponCode]);
                $coupon = $couponStmt->fetch();
                
                if ($coupon && $amount >= ($coupon['minimum_amount'] ?: 0)) {
                    $couponId = $coupon['id'];
                    if ($coupon['discount_type'] === 'percentage') {
                        $discountAmount = ($amount * $coupon['discount_value']) / 100;
                    } else {
                        $discountAmount = $coupon['discount_value'];
                    }
                    $discountAmount = min($discountAmount, $amount);
                }
            }
            
            $finalAmount = $amount - $discountAmount;
            
            if ($finalAmount <= 0) {
                // Free enrollment
                try {
                    $db->beginTransaction();
                    
                    $enrollStmt = $db->prepare("
                        INSERT INTO enrollments (user_id, course_id, payment_status, payment_amount, status)
                        VALUES (?, ?, 'free', 0, 'active')
                    ");
                    $enrollStmt->execute([$user['id'], $courseId]);
                    
                    if ($couponId) {
                        $updateCouponStmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                        $updateCouponStmt->execute([$couponId]);
                    }
                    
                    $db->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'free_enrollment' => true,
                        'message' => 'Enrolled successfully'
                    ]);
                } catch (Exception $e) {
                    $db->rollBack();
                    http_response_code(500);
                    echo json_encode(['message' => 'Enrollment failed']);
                }
                exit;
            }
            
            // Create Razorpay order
            $orderData = [
                'amount' => $finalAmount * 100, // Amount in paise
                'currency' => 'INR',
                'receipt' => 'course_' . $courseId . '_user_' . $user['id'] . '_' . time(),
                'notes' => [
                    'course_id' => $courseId,
                    'user_id' => $user['id'],
                    'coupon_id' => $couponId
                ]
            ];
            
            // Mock Razorpay order creation (replace with actual Razorpay API call)
            $razorpayOrderId = 'order_' . uniqid();
            
            // Store payment record
            $paymentStmt = $db->prepare("
                INSERT INTO payments (
                    user_id, course_id, payment_method, amount, discount_amount, 
                    final_amount, coupon_id, status, transaction_id
                ) VALUES (?, ?, 'razorpay', ?, ?, ?, ?, 'pending', ?)
            ");
            $paymentStmt->execute([
                $user['id'], $courseId, $amount, $discountAmount, 
                $finalAmount, $couponId, $razorpayOrderId
            ]);
            
            echo json_encode([
                'success' => true,
                'order_id' => $razorpayOrderId,
                'amount' => $finalAmount,
                'currency' => 'INR',
                'key' => RAZORPAY_KEY_ID,
                'course_title' => $course['title']
            ]);
            
        } elseif ($data['action'] === 'verify_payment') {
            // Verify payment and complete enrollment
            $paymentId = $data['payment_id'];
            $orderId = $data['order_id'];
            $signature = $data['signature'];
            
            // Verify signature (implement actual Razorpay signature verification)
            $isValidSignature = true; // Mock verification
            
            if ($isValidSignature) {
                try {
                    $db->beginTransaction();
                    
                    // Update payment status
                    $updatePaymentStmt = $db->prepare("
                        UPDATE payments 
                        SET status = 'completed', payment_date = NOW() 
                        WHERE transaction_id = ?
                    ");
                    $updatePaymentStmt->execute([$orderId]);
                    
                    // Get payment details
                    $paymentStmt = $db->prepare("SELECT * FROM payments WHERE transaction_id = ?");
                    $paymentStmt->execute([$orderId]);
                    $payment = $paymentStmt->fetch();
                    
                    if ($payment) {
                        // Create enrollment
                        $enrollStmt = $db->prepare("
                            INSERT INTO enrollments (
                                user_id, course_id, payment_status, payment_amount, 
                                payment_id, status
                            ) VALUES (?, ?, 'paid', ?, ?, 'active')
                        ");
                        $enrollStmt->execute([
                            $payment['user_id'], $payment['course_id'], 
                            $payment['final_amount'], $payment['id']
                        ]);
                        
                        // Update coupon usage
                        if ($payment['coupon_id']) {
                            $updateCouponStmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                            $updateCouponStmt->execute([$payment['coupon_id']]);
                        }
                    }
                    
                    $db->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Payment verified and enrollment completed'
                    ]);
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    http_response_code(500);
                    echo json_encode(['message' => 'Payment verification failed']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'Invalid payment signature']);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
}
?>