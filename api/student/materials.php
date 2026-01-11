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
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;
    
    // Get materials accessible to the student (from enrolled courses)
    $stmt = $db->prepare("
        SELECT 
            m.id,
            m.title,
            m.description,
            m.file_path,
            m.file_type,
            m.file_size,
            m.created_at,
            c.title as course_title,
            c.id as course_id
            
        FROM materials m
        JOIN courses c ON c.id = m.course_id
        JOIN enrollments e ON e.course_id = c.id
        WHERE e.user_id = ? 
        AND e.status = 'active'
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$user['id'], $limit, $offset]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countStmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM materials m
        JOIN courses c ON c.id = m.course_id
        JOIN enrollments e ON e.course_id = c.id
        WHERE e.user_id = ? 
        AND e.status = 'active'
    ");
    
    $countStmt->execute([$user['id']]);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'materials' => $materials,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Failed to fetch materials: ' . $e->getMessage()
    ]);
}