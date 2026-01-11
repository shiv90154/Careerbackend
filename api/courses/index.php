<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    
    if ($method === 'GET') {
        handleGetCourses($db);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetCourses($db) {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 12;
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    $level = $_GET['level'] ?? '';
    $price = $_GET['price'] ?? '';
    $featured = $_GET['featured'] ?? '';
    
    $offset = ($page - 1) * $limit;
    
    $where = ["c.status = 'published'"];
    $params = [];
    
    if ($category) {
        $where[] = "c.category_id = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $where[] = "(c.title LIKE ? OR c.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($level) {
        $where[] = "c.level = ?";
        $params[] = $level;
    }
    
    if ($price === 'free') {
        $where[] = "c.price = 0";
    } elseif ($price === 'paid') {
        $where[] = "c.price > 0";
    }
    
    if ($featured) {
        $where[] = "c.is_featured = 1";
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Get courses
    $sql = "
        SELECT 
            c.*,
            cat.name as category_name,
            u.full_name as instructor_name,
            COUNT(DISTINCT e.id) as enrollment_count
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN users u ON c.instructor_id = u.id
        LEFT JOIN enrollments e ON c.id = e.course_id
        WHERE $whereClause
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $db->prepare($sql);
    
    // Bind all parameters except LIMIT and OFFSET
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    // Bind LIMIT and OFFSET as integers
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $courses = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(DISTINCT c.id) as total FROM courses c WHERE $whereClause";
    $countStmt = $db->prepare($countSql);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $countStmt->bindValue($i + 1, $params[$i]);
    }
    
    $countStmt->execute();
    $total = $countStmt->fetch()['total'];
    
    echo json_encode([
        'success' => true,
        'courses' => $courses,
        'pagination' => [
            'current_page' => (int)$page,
            'total_pages' => ceil($total / $limit),
            'total_items' => (int)$total,
            'per_page' => (int)$limit
        ]
    ]);
}
?>