<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    
    if ($method === 'GET') {
        handleGetCategories($db);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetCategories($db) {
    // Get all active categories
    $query = "
        SELECT 
            c.id,
            c.name,
            c.slug,
            c.description,
            c.image,
            COUNT(DISTINCT co.id) as course_count
        FROM categories c
        LEFT JOIN courses co ON c.id = co.category_id AND co.status = 'published'
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY c.sort_order ASC, c.name ASC
    ";
    
    $stmt = $db->query($query);
    $categories = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
}
?>