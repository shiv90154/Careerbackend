<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

$user = Auth::requireAuth();
$data = json_decode(file_get_contents("php://input"), true);

$couponCode = $data['coupon_code'] ?? '';
$courseId = $data['course_id'] ?? '';
$amount = $data['amount'] ?? 0;

if (!$couponCode || !$courseId || !$amount) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required fields']);
    exit;
}

$db = (new Database())->getConnection();

try {
    // Get coupon details
    $stmt = $db->prepare("
        SELECT * FROM coupons 
        WHERE code = ? AND is_active = 1 
        AND (valid_until IS NULL OR valid_until > NOW())
        AND (usage_limit IS NULL OR used_count < usage_limit)
    ");
    $stmt->execute([$couponCode]);
    $coupon = $stmt->fetch();
    
    if (!$coupon) {
        http_response_code(404);
        echo json_encode(['message' => 'Invalid or expired coupon code']);
        exit;
    }
    
    // Check minimum amount
    if ($amount < ($coupon['minimum_amount'] ?: 0)) {
        http_response_code(400);
        echo json_encode(['message' => "Minimum order amount is $" . $coupon['minimum_amount']]);
        exit;
    }
    
    // Calculate discount
    $discountAmount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discountAmount = ($amount * $coupon['discount_value']) / 100;
    } else {
        $discountAmount = $coupon['discount_value'];
    }
    
    $discountAmount = min($discountAmount, $amount);
    $finalAmount = max(0, $amount - $discountAmount);
    
    echo json_encode([
        'coupon' => $coupon,
        'discount_amount' => $discountAmount,
        'final_amount' => $finalAmount,
        'savings' => $discountAmount
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error']);
}
?>