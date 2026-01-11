<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    
    switch ($method) {
        case 'GET':
            handleGetCurrentAffairs($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetCurrentAffairs($db) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    $importance = isset($_GET['importance']) ? $_GET['importance'] : '';
    $premium = isset($_GET['premium']) ? (bool)$_GET['premium'] : null;
    
    $offset = ($page - 1) * $limit;
    
    // Build query conditions
    $whereConditions = [];
    $params = [];
    
    if (!empty($category)) {
        $whereConditions[] = "category = ?";
        $params[] = $category;
    }
    
    if (!empty($date)) {
        $whereConditions[] = "date = ?";
        $params[] = $date;
    }
    
    if (!empty($importance)) {
        $whereConditions[] = "importance_level = ?";
        $params[] = $importance;
    }
    
    if ($premium !== null) {
        $whereConditions[] = "is_premium = ?";
        $params[] = $premium ? 1 : 0;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM current_affairs $whereClause";
    $countStmt = $db->prepare($countQuery);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $countStmt->bindValue($i + 1, $params[$i]);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetch()['total'];
    
    // Get current affairs
    $query = "
        SELECT *
        FROM current_affairs
        $whereClause
        ORDER BY date DESC, importance_level DESC
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
    $currentAffairs = $stmt->fetchAll();
    
    // Format data
    foreach ($currentAffairs as &$affair) {
        $affair['date_formatted'] = date('M d, Y', strtotime($affair['date']));
        $affair['tags'] = $affair['tags'] ? json_decode($affair['tags'], true) : [];
        
        // Hide content for premium items if not authenticated
        if ($affair['is_premium']) {
            $affair['content'] = substr($affair['content'], 0, 200) . '...';
            $affair['is_preview'] = true;
        }
    }
    
    // Get categories for filter
    $categoriesStmt = $db->query("
        SELECT DISTINCT category 
        FROM current_affairs 
        WHERE category IS NOT NULL 
        ORDER BY category
    ");
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'current_affairs' => $currentAffairs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalRecords / $limit),
            'total_records' => $totalRecords,
            'per_page' => $limit
        ],
        'filters' => [
            'categories' => $categories,
            'importance_levels' => ['low', 'medium', 'high', 'critical']
        ]
    ]);
}
?>