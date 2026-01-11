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

// Check authentication
$user = Auth::getCurrentUser();
if (!$user || $user['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Get available tests for the student
    $stmt = $db->prepare("
        SELECT 
            t.id,
            t.title,
            t.description,
            t.duration_minutes,
            t.total_questions,
            t.passing_score,
            t.is_active,
            t.created_at,
            
            -- Check if student has attempted this test
            (SELECT COUNT(*) 
             FROM test_attempts ta 
             WHERE ta.test_id = t.id AND ta.user_id = ?) AS attempt_count,
             
            -- Get best score if attempted
            (SELECT MAX(ta.score) 
             FROM test_attempts ta 
             WHERE ta.test_id = t.id AND ta.user_id = ?) AS best_score
             
        FROM tests t
        WHERE t.is_active = 1
        ORDER BY t.created_at DESC
    ");
    
    $stmt->execute([$user['id'], $user['id']]);
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'tests' => $tests
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Failed to fetch tests: ' . $e->getMessage()
    ]);
}