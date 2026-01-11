<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    
    if ($method === 'GET') {
        handleGetCurrentAffairDetail($db);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetCurrentAffairDetail($db) {
    $affair_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$affair_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Current affair ID is required']);
        return;
    }
    
    // Get current affair details
    $query = "SELECT * FROM current_affairs WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$affair_id]);
    $affair = $stmt->fetch();
    
    if (!$affair) {
        http_response_code(404);
        echo json_encode(['error' => 'Current affair not found']);
        return;
    }
    
    // Check if user is authenticated and has access
    $user = null;
    $has_access = false;
    $payment_required = false;
    
    try {
        // Try to get authenticated user, but don't require it
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $user = Auth::verifyToken();
        }
        
        if ($user) {
            if (!$affair['is_premium']) {
                $has_access = true;
            } else {
                // Check if user has paid for premium content
                $paymentQuery = "
                    SELECT p.* FROM payments p
                    WHERE p.user_id = ? AND p.status = 'completed'
                    AND (p.related_type = 'current_affair' AND p.related_id = ?)
                    OR (p.related_type = 'premium_subscription')
                ";
                $paymentStmt = $db->prepare($paymentQuery);
                $paymentStmt->execute([$user['id'], $affair_id]);
                $payment = $paymentStmt->fetch();
                
                $has_access = (bool)$payment;
                $payment_required = !$has_access;
            }
        } else {
            // Guest user
            $has_access = !$affair['is_premium'];
            $payment_required = $affair['is_premium'];
        }
    } catch (Exception $e) {
        // User not authenticated, continue as guest
        $has_access = !$affair['is_premium'];
        $payment_required = $affair['is_premium'];
    }
    
    // Format data
    $affair['date_formatted'] = date('M d, Y', strtotime($affair['date']));
    $affair['tags'] = $affair['tags'] ? json_decode($affair['tags'], true) : [];
    
    // If premium and no access, show only preview
    if ($affair['is_premium'] && !$has_access) {
        $affair['content'] = substr($affair['content'], 0, 300) . '...';
        $affair['is_preview'] = true;
    } else {
        $affair['is_preview'] = false;
    }
    
    // Update views count
    if (!$affair['is_preview']) {
        $updateViewsQuery = "UPDATE current_affairs SET views_count = views_count + 1 WHERE id = ?";
        $updateViewsStmt = $db->prepare($updateViewsQuery);
        $updateViewsStmt->execute([$affair_id]);
        $affair['views_count']++;
    }
    
    // Get related current affairs (same category, recent)
    $relatedQuery = "
        SELECT id, title, date, category, importance_level, is_premium, price
        FROM current_affairs 
        WHERE id != ? AND category = ? 
        ORDER BY date DESC 
        LIMIT 5
    ";
    $relatedStmt = $db->prepare($relatedQuery);
    $relatedStmt->execute([$affair_id, $affair['category']]);
    $related_affairs = $relatedStmt->fetchAll();
    
    foreach ($related_affairs as &$related) {
        $related['date_formatted'] = date('M d, Y', strtotime($related['date']));
    }
    
    echo json_encode([
        'success' => true,
        'current_affair' => $affair,
        'related_affairs' => $related_affairs,
        'has_access' => $has_access,
        'payment_required' => $payment_required,
        'is_authenticated' => (bool)$user
    ]);
}
?>