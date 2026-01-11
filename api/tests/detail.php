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

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    
    if ($method === 'GET') {
        handleGetTestDetail($db);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetTestDetail($db) {
    $test_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$test_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Test ID is required']);
        return;
    }
    
    // Get test details
    $query = "
        SELECT 
            t.*,
            c.name as category_name,
            u.full_name as created_by_name,
            COUNT(DISTINCT tq.id) as question_count
        FROM tests t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users u ON t.created_by = u.id
        LEFT JOIN test_questions tq ON t.id = tq.test_id
        WHERE t.id = ? AND t.is_active = 1
        GROUP BY t.id
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$test_id]);
    $test = $stmt->fetch();
    
    if (!$test) {
        http_response_code(404);
        echo json_encode(['error' => 'Test not found']);
        return;
    }
    
    // Check if user is authenticated
    $user = null;
    $user_attempts = [];
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
            // Get user's attempts for this test
            $attemptsQuery = "
                SELECT * FROM test_attempts 
                WHERE user_id = ? AND test_id = ? 
                ORDER BY attempt_number DESC
            ";
            $attemptsStmt = $db->prepare($attemptsQuery);
            $attemptsStmt->execute([$user['id'], $test_id]);
            $user_attempts = $attemptsStmt->fetchAll();
            
            // Check if user has access (paid or free test)
            if (!$test['is_premium']) {
                $has_access = true;
            } else {
                // Check if user has paid for this test
                $paymentQuery = "
                    SELECT p.* FROM payments p
                    WHERE p.user_id = ? AND p.status = 'completed'
                    AND p.related_type = 'test' AND p.related_id = ?
                ";
                $paymentStmt = $db->prepare($paymentQuery);
                $paymentStmt->execute([$user['id'], $test_id]);
                $payment = $paymentStmt->fetch();
                
                $has_access = (bool)$payment;
                $payment_required = !$has_access;
            }
        } else {
            // Guest user
            $has_access = !$test['is_premium'];
            $payment_required = $test['is_premium'];
        }
    } catch (Exception $e) {
        // User not authenticated, continue as guest
        $has_access = !$test['is_premium'];
        $payment_required = $test['is_premium'];
    }
    
    // Format test data
    $test['duration_formatted'] = formatDuration($test['duration_minutes']);
    $test['created_at_formatted'] = date('M d, Y', strtotime($test['created_at']));
    
    // Check if test is live and available
    if ($test['type'] === 'live') {
        $now = time();
        $start_time = $test['start_time'] ? strtotime($test['start_time']) : 0;
        $end_time = $test['end_time'] ? strtotime($test['end_time']) : 0;
        
        $test['is_live'] = $start_time && $end_time && $now >= $start_time && $now <= $end_time;
        $test['is_upcoming'] = $start_time && $now < $start_time;
        $test['is_expired'] = $end_time && $now > $end_time;
        
        if ($start_time) {
            $test['start_time_formatted'] = date('M d, Y g:i A', $start_time);
        }
        if ($end_time) {
            $test['end_time_formatted'] = date('M d, Y g:i A', $end_time);
        }
    } else {
        $test['is_live'] = false;
        $test['is_upcoming'] = false;
        $test['is_expired'] = false;
    }
    
    $test['difficulty_badge'] = getDifficultyBadge($test['difficulty_level']);
    $test['type_badge'] = getTypeBadge($test['type']);
    
    // Get sample questions (first 3) for preview
    $sampleQuery = "
        SELECT question_text, question_type, options
        FROM test_questions 
        WHERE test_id = ? 
        ORDER BY order_index ASC 
        LIMIT 3
    ";
    $sampleStmt = $db->prepare($sampleQuery);
    $sampleStmt->execute([$test_id]);
    $sample_questions = $sampleStmt->fetchAll();
    
    // Format sample questions
    foreach ($sample_questions as &$question) {
        if ($question['options']) {
            $question['options'] = json_decode($question['options'], true);
        }
    }
    
    // Calculate user statistics
    $user_stats = null;
    if ($user && !empty($user_attempts)) {
        $best_attempt = array_reduce($user_attempts, function($best, $current) {
            return $current['percentage'] > ($best['percentage'] ?? 0) ? $current : $best;
        });
        
        $user_stats = [
            'total_attempts' => count($user_attempts),
            'max_attempts' => $test['max_attempts'],
            'attempts_remaining' => max(0, $test['max_attempts'] - count($user_attempts)),
            'best_score' => $best_attempt['percentage'] ?? 0,
            'best_marks' => $best_attempt['marks_obtained'] ?? 0,
            'can_attempt' => count($user_attempts) < $test['max_attempts']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'test' => $test,
        'sample_questions' => $sample_questions,
        'user_attempts' => $user_attempts,
        'user_stats' => $user_stats,
        'has_access' => $has_access,
        'payment_required' => $payment_required,
        'is_authenticated' => (bool)$user
    ]);
}

function formatDuration($minutes) {
    if ($minutes >= 60) {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . 'h ' . ($mins > 0 ? $mins . 'm' : '');
    }
    return $minutes . 'm';
}

function getDifficultyBadge($difficulty) {
    $badges = [
        'beginner' => ['color' => 'green', 'label' => 'Beginner'],
        'intermediate' => ['color' => 'yellow', 'label' => 'Intermediate'],
        'advanced' => ['color' => 'red', 'label' => 'Advanced']
    ];
    
    return $badges[$difficulty] ?? $badges['beginner'];
}

function getTypeBadge($type) {
    $badges = [
        'practice' => ['color' => 'blue', 'label' => 'Practice'],
        'mock' => ['color' => 'purple', 'label' => 'Mock Test'],
        'live' => ['color' => 'red', 'label' => 'Live Test'],
        'assessment' => ['color' => 'indigo', 'label' => 'Assessment']
    ];
    
    return $badges[$type] ?? $badges['practice'];
}
?>