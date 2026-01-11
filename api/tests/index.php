<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../includes/config.php';
require_once '../../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    
    switch ($method) {
        case 'GET':
            handleGetTests($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetTests($db) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    $difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
    $live_only = isset($_GET['live_only']) ? (bool)$_GET['live_only'] : false;
    
    $offset = ($page - 1) * $limit;
    
    // Build query conditions
    $whereConditions = ['t.is_active = 1'];
    $params = [];
    
    if (!empty($type)) {
        $whereConditions[] = "t.type = ?";
        $params[] = $type;
    }
    
    if ($category_id > 0) {
        $whereConditions[] = "t.category_id = ?";
        $params[] = $category_id;
    }
    
    if (!empty($difficulty)) {
        $whereConditions[] = "t.difficulty_level = ?";
        $params[] = $difficulty;
    }
    
    if ($live_only) {
        $whereConditions[] = "t.type = 'live'";
        $whereConditions[] = "t.start_time <= NOW()";
        $whereConditions[] = "t.end_time >= NOW()";
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) as total
        FROM tests t
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $countStmt->bindValue($i + 1, $params[$i]);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetch()['total'];
    
    // Get tests
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
        $whereClause
        GROUP BY t.id
        ORDER BY t.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $db->prepare($query);
    
    // Bind all parameters except LIMIT and OFFSET
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    // Bind LIMIT and OFFSET as integers
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $tests = $stmt->fetchAll();
    
    // Format data
    foreach ($tests as &$test) {
        $test['duration_formatted'] = formatDuration($test['duration_minutes']);
        $test['created_at_formatted'] = date('M d, Y', strtotime($test['created_at']));
        $test['attempt_count'] = 0; // Default value since we don't have test_attempts table yet
        
        // Check if test is live and available
        if ($test['type'] === 'live') {
            $now = time();
            $start_time = $test['start_time'] ? strtotime($test['start_time']) : null;
            $end_time = $test['end_time'] ? strtotime($test['end_time']) : null;
            
            if ($start_time && $end_time) {
                $test['is_live'] = $now >= $start_time && $now <= $end_time;
                $test['is_upcoming'] = $now < $start_time;
                $test['is_expired'] = $now > $end_time;
                $test['start_time_formatted'] = date('M d, Y g:i A', $start_time);
                $test['end_time_formatted'] = date('M d, Y g:i A', $end_time);
            } else {
                $test['is_live'] = false;
                $test['is_upcoming'] = true;
                $test['is_expired'] = false;
                $test['start_time_formatted'] = 'TBD';
                $test['end_time_formatted'] = 'TBD';
            }
        } else {
            $test['is_live'] = false;
            $test['is_upcoming'] = false;
            $test['is_expired'] = false;
        }
        
        $test['difficulty_badge'] = getDifficultyBadge($test['difficulty_level']);
        $test['type_badge'] = getTypeBadge($test['type']);
    }
    
    // Get categories for filter
    $categoriesStmt = $db->query("
        SELECT DISTINCT c.id, c.name 
        FROM categories c 
        INNER JOIN tests t ON c.id = t.category_id 
        WHERE t.is_active = 1
        ORDER BY c.name
    ");
    $categories = $categoriesStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'tests' => $tests,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalRecords / $limit),
            'total_records' => $totalRecords,
            'per_page' => $limit
        ],
        'filters' => [
            'categories' => $categories,
            'types' => ['practice', 'mock', 'live', 'assessment'],
            'difficulty_levels' => ['beginner', 'intermediate', 'advanced']
        ]
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