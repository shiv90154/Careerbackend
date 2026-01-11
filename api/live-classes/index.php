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
            handleGetLiveClasses($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetLiveClasses($db) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $upcoming = isset($_GET['upcoming']) ? (bool)$_GET['upcoming'] : false;
    
    $offset = ($page - 1) * $limit;
    
    // Build query conditions
    $whereConditions = [];
    $params = [];
    
    if (!empty($status)) {
        $whereConditions[] = "lc.status = ?";
        $params[] = $status;
    }
    
    if ($upcoming) {
        $whereConditions[] = "lc.scheduled_at > NOW()";
        $whereConditions[] = "lc.status = 'scheduled'";
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) as total
        FROM live_classes lc
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $countStmt->bindValue($i + 1, $params[$i]);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetch()['total'];
    
    // Get live classes
    $query = "
        SELECT 
            lc.*,
            u.full_name as instructor_name,
            u.profile_image as instructor_image,
            c.title as course_title,
            COUNT(lce.id) as enrolled_count
        FROM live_classes lc
        LEFT JOIN users u ON lc.instructor_id = u.id
        LEFT JOIN courses c ON lc.course_id = c.id
        LEFT JOIN live_class_enrollments lce ON lc.id = lce.live_class_id
        $whereClause
        GROUP BY lc.id
        ORDER BY lc.scheduled_at ASC
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
    $liveClasses = $stmt->fetchAll();
    
    // Format data
    foreach ($liveClasses as &$class) {
        $class['scheduled_at_formatted'] = date('M d, Y g:i A', strtotime($class['scheduled_at']));
        $class['is_upcoming'] = strtotime($class['scheduled_at']) > time();
        $class['is_live'] = $class['status'] === 'live';
        $class['can_join'] = $class['is_live'] || (strtotime($class['scheduled_at']) - time() <= 900); // 15 minutes before
    }
    
    echo json_encode([
        'success' => true,
        'live_classes' => $liveClasses,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalRecords / $limit),
            'total_records' => $totalRecords,
            'per_page' => $limit
        ]
    ]);
}
?>