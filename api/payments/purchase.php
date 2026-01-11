<?php

/**
 * Secure Payment Purchase API
 * Career Path Institute - Shimla
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/security.php';
require_once '../../includes/validation.php';
require_once '../../includes/response.php';
require_once '../../includes/logger.php';
require_once '../../includes/auth.php';

try {
    // Rate limiting
    $clientIP = SecurityManager::getClientIP();
    if (!SecurityManager::checkRateLimit("purchase_$clientIP", 10, 15)) {
        ApiResponse::rateLimitExceeded('Too many purchase attempts. Please try again later.');
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
        ->required(['item_type', 'item_id'])
        ->enum('item_type', ['course', 'test', 'current_affair', 'material', 'live_class'])
        ->integer('item_id', 1)
        ->length('coupon_code', 0, 50)
        ->validate();
    
    $db = Database::getInstance()->getConnection();
    
    // Get item details and validate
    $item = getItemDetails($db, $data['item_type'], $data['item_id']);
    if (!$item) {
        ApiResponse::notFound('Item not found or not available');
    }
    
    // Validate item is purchasable
    if (!$item['is_purchasable'] || $item['price'] <= 0) {
        ApiResponse::error('Item is not available for purchase', 400);
    }
    
    // Check if user already has access
    if (userHasAccess($db, $user['id'], $data['item_type'], $data['item_id'])) {
        ApiResponse::error('You already have access to this item', 409);
    }
    
    $originalPrice = (float) $item['price'];
    $discountAmount = 0;
    $couponId = null;
    $couponCode = $data['coupon_code'] ?? '';
    
    // Apply coupon if provided
    if ($couponCode) {
        $coupon = validateCoupon($db, $couponCode, $originalPrice, $data['item_type'], $data['item_id']);
        if ($coupon) {
            $couponId = $coupon['id'];
            if ($coupon['discount_type'] === 'percentage') {
                $discountAmount = ($originalPrice * $coupon['discount_value']) / 100;
                if ($coupon['maximum_discount']) {
                    $discountAmount = min($discountAmount, $coupon['maximum_discount']);
                }
            } else {
                $discountAmount = $coupon['discount_value'];
            }
            $discountAmount = min($discountAmount, $originalPrice);
        } else {
            ApiResponse::error('Invalid or expired coupon code', 400);
        }
    }
    
    $finalAmount = $originalPrice - $discountAmount;
    
    // Validate final amount
    if ($finalAmount < 0) {
        $finalAmount = 0;
    }
    
    $db->beginTransaction();
    
    try {
        // Create enrollment record if it's a course
        $enrollmentId = null;
        if ($data['item_type'] === 'course') {
            $enrollmentQuery = "
                INSERT INTO enrollments (user_id, course_id, enrollment_date, status, payment_status) 
                VALUES (?, ?, NOW(), 'pending', 'pending')
            ";
            $enrollmentStmt = $db->prepare($enrollmentQuery);
            $enrollmentStmt->execute([$user['id'], $data['item_id']]);
            $enrollmentId = $db->lastInsertId();
        }
        
        // Generate Razorpay order ID
        $razorpayOrderId = 'order_' . uniqid() . '_' . time();
        
        // Create payment record
        $paymentQuery = "
            INSERT INTO payments (
                user_id, course_id, enrollment_id, payment_method, amount, 
                discount_amount, final_amount, currency, coupon_id, coupon_code,
                status, razorpay_order_id, notes, created_at
            ) VALUES (?, ?, ?, 'razorpay', ?, ?, ?, 'INR', ?, ?, 'pending', ?, ?, NOW())
        ";
        
        $courseId = $data['item_type'] === 'course' ? $data['item_id'] : null;
        $notes = json_encode([
            'item_type' => $data['item_type'],
            'item_id' => $data['item_id'],
            'item_title' => $item['title']
        ]);
        
        $paymentStmt = $db->prepare($paymentQuery);
        $paymentStmt->execute([
            $user['id'], 
            $courseId, 
            $enrollmentId, 
            $originalPrice, 
            $discountAmount, 
            $finalAmount, 
            $couponId, 
            $couponCode,
            $razorpayOrderId,
            $notes
        ]);
        
        $paymentId = $db->lastInsertId();
        
        // Update coupon usage if applied
        if ($couponId) {
            $updateCouponQuery = "
                UPDATE coupons 
                SET used_count = used_count + 1, updated_at = NOW() 
                WHERE id = ?
            ";
            $updateCouponStmt = $db->prepare($updateCouponQuery);
            $updateCouponStmt->execute([$couponId]);
        }
        
        $db->commit();
        
        // Generate Razorpay order (in production, use actual Razorpay API)
        $razorpayOrder = createRazorpayOrder($finalAmount, $razorpayOrderId, $paymentId);
        
        Logger::info("Payment initiated", [
            'user_id' => $user['id'],
            'payment_id' => $paymentId,
            'item_type' => $data['item_type'],
            'item_id' => $data['item_id'],
            'amount' => $finalAmount,
            'razorpay_order_id' => $razorpayOrderId
        ]);
        
        ApiResponse::success([
            'payment_id' => $paymentId,
            'razorpay_order' => $razorpayOrder,
            'item' => [
                'id' => $item['id'],
                'title' => $item['title'],
                'type' => $data['item_type']
            ],
            'pricing' => [
                'original_price' => $originalPrice,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'currency' => 'INR',
                'coupon_applied' => $couponCode
            ]
        ], 'Payment order created successfully');
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    Logger::error("Payment purchase error: " . $e->getMessage(), [
        'input' => $input ?? null,
        'user_id' => $user['id'] ?? null,
        'ip' => $clientIP ?? 'unknown'
    ]);
    
    ApiResponse::serverError($e->getMessage());
}

/**
 * Get item details for purchase
 */
function getItemDetails($db, $itemType, $itemId) {
    $queries = [
        'course' => "
            SELECT id, title, price, discount_price, 
                   (price > 0 OR discount_price > 0) as is_purchasable,
                   COALESCE(discount_price, price) as price
            FROM courses 
            WHERE id = ? AND status = 'published'
        ",
        'test' => "
            SELECT id, title, price, 
                   (is_free = 0 AND price > 0) as is_purchasable
            FROM tests 
            WHERE id = ? AND is_active = 1
        ",
        'current_affair' => "
            SELECT id, title, 0 as price, 0 as is_purchasable
            FROM current_affairs 
            WHERE id = ? AND is_active = 1
        ",
        'material' => "
            SELECT id, title, price,
                   (is_free = 0 AND price > 0) as is_purchasable
            FROM materials 
            WHERE id = ? AND is_active = 1
        ",
        'live_class' => "
            SELECT id, title, price,
                   (is_free = 0 AND price > 0) as is_purchasable
            FROM live_classes 
            WHERE id = ? AND is_active = 1
        "
    ];
    
    if (!isset($queries[$itemType])) {
        return null;
    }
    
    $stmt = $db->prepare($queries[$itemType]);
    $stmt->execute([$itemId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Check if user already has access to item
 */
function userHasAccess($db, $userId, $itemType, $itemId) {
    $queries = [
        'course' => "
            SELECT COUNT(*) as count FROM enrollments 
            WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'
        ",
        'test' => "
            SELECT COUNT(*) as count FROM payments 
            WHERE user_id = ? AND JSON_EXTRACT(notes, '$.item_type') = 'test' 
            AND JSON_EXTRACT(notes, '$.item_id') = ? AND status = 'completed'
        ",
        'material' => "
            SELECT COUNT(*) as count FROM payments 
            WHERE user_id = ? AND JSON_EXTRACT(notes, '$.item_type') = 'material' 
            AND JSON_EXTRACT(notes, '$.item_id') = ? AND status = 'completed'
        ",
        'live_class' => "
            SELECT COUNT(*) as count FROM live_class_enrollments 
            WHERE user_id = ? AND live_class_id = ? AND payment_status = 'completed'
        "
    ];
    
    if (!isset($queries[$itemType])) {
        return false;
    }
    
    $stmt = $db->prepare($queries[$itemType]);
    $stmt->execute([$userId, $itemId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
}

/**
 * Validate coupon code
 */
function validateCoupon($db, $couponCode, $amount, $itemType, $itemId) {
    $query = "
        SELECT * FROM coupons 
        WHERE code = ? AND is_active = 1 
        AND (starts_at IS NULL OR starts_at <= NOW()) 
        AND (expires_at IS NULL OR expires_at >= NOW())
        AND (usage_limit IS NULL OR used_count < usage_limit)
        AND (minimum_amount IS NULL OR ? >= minimum_amount)
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$couponCode, $amount]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        return null;
    }
    
    // Check if coupon is applicable to this item type/category
    if ($coupon['applicable_courses'] || $coupon['applicable_categories']) {
        $applicableCourses = $coupon['applicable_courses'] ? 
            json_decode($coupon['applicable_courses'], true) : [];
        $applicableCategories = $coupon['applicable_categories'] ? 
            json_decode($coupon['applicable_categories'], true) : [];
        
        if ($itemType === 'course') {
            if (!empty($applicableCourses) && !in_array($itemId, $applicableCourses)) {
                // Check if course category is applicable
                $categoryQuery = "SELECT category_id FROM courses WHERE id = ?";
                $categoryStmt = $db->prepare($categoryQuery);
                $categoryStmt->execute([$itemId]);
                $courseCategory = $categoryStmt->fetchColumn();
                
                if (!$courseCategory || !in_array($courseCategory, $applicableCategories)) {
                    return null;
                }
            }
        }
    }
    
    return $coupon;
}

/**
 * Create Razorpay order (mock implementation)
 */
function createRazorpayOrder($amount, $orderId, $paymentId) {
    // In production, use actual Razorpay API
    if (empty(RAZORPAY_KEY_ID)) {
        // Mock order for development
        return [
            'id' => $orderId,
            'amount' => $amount * 100, // Convert to paise
            'currency' => 'INR',
            'receipt' => 'receipt_' . $paymentId,
            'status' => 'created'
        ];
    }
    
    // Production Razorpay integration would go here
    // Example:
    // $api = new Razorpay\Api\Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    // return $api->order->create([
    //     'receipt' => 'receipt_' . $paymentId,
    //     'amount' => $amount * 100,
    //     'currency' => 'INR'
    // ]);
    
    return [
        'id' => $orderId,
        'amount' => $amount * 100,
        'currency' => 'INR',
        'receipt' => 'receipt_' . $paymentId,
        'status' => 'created'
    ];
}
?>