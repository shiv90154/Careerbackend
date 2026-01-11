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
    
    // Get test attempts for the student
    $stmt = $db->prepare("
        SELECT 
            ta.id,
            ta.test_id,
            ta.score,
            ta.total_questions,
            ta.correct_answers,
            ta.time_taken,
            ta.status,
            ta.started_at,
            ta.completed_at,
            t.title as test_title,
            t.passing_score
            
        FROM test_attempts ta
        JOIN tests t ON t.id = ta.test_id
        WHERE ta.user_id = ?
        ORDER BY ta.started_at DESC
    ");
    
    $stmt->execute([$user['id']]);
    $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'attempts' => $attempts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Failed to fetch test attempts: ' . $e->getMessage()
    ]);
}