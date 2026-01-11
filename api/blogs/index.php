<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    
    switch ($method) {
        case 'GET':
            handleGetBlogs($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetBlogs($db) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $tag = isset($_GET['tag']) ? $_GET['tag'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $featured = isset($_GET['featured']) ? (bool)$_GET['featured'] : false;
    
    $offset = ($page - 1) * $limit;
    
    // Build query conditions
    $whereConditions = ['b.is_published = 1'];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(b.title LIKE ? OR b.content LIKE ? OR b.excerpt LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($category)) {
        $whereConditions[] = "c.name = ?";
        $params[] = $category;
    }
    
    if ($featured) {
        $whereConditions[] = "b.is_featured = 1";
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Tag filter requires different approach
    $tagJoin = '';
    if (!empty($tag)) {
        $tagJoin = "
            INNER JOIN blog_tag_relations btr ON b.id = btr.blog_id
            INNER JOIN blog_tags bt ON btr.tag_id = bt.id AND bt.slug = ?
        ";
        $params[] = $tag;
    }
    
    // Get total count
    $countQuery = "
        SELECT COUNT(DISTINCT b.id) as total
        FROM blogs b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN users u ON b.author_id = u.id
        $tagJoin
        $whereClause
    ";
    
    $countStmt = $db->prepare($countQuery);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $countStmt->bindValue($i + 1, $params[$i]);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetch()['total'];
    
    // Get blogs
    $query = "
        SELECT DISTINCT
            b.id,
            b.title,
            b.slug,
            b.excerpt,
            b.featured_image,
            b.views_count,
            b.created_at,
            u.full_name as author_name,
            c.name as category_name,
            c.id as category_id,
            GROUP_CONCAT(DISTINCT bt.name) as tags
        FROM blogs b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN users u ON b.author_id = u.id
        LEFT JOIN blog_tag_relations btr ON b.id = btr.blog_id
        LEFT JOIN blog_tags bt ON btr.tag_id = bt.id
        $tagJoin
        $whereClause
        GROUP BY b.id
        ORDER BY b.created_at DESC
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
    $blogs = $stmt->fetchAll();
    
    // Format blogs
    foreach ($blogs as &$blog) {
        $blog['tags'] = $blog['tags'] ? explode(',', $blog['tags']) : [];
        $blog['created_at'] = date('M d, Y', strtotime($blog['created_at']));
    }
    
    // Get categories for filter
    $categoriesStmt = $db->query("
        SELECT DISTINCT c.id, c.name 
        FROM categories c 
        INNER JOIN blogs b ON c.id = b.category_id 
        WHERE b.is_published = 1
        ORDER BY c.name
    ");
    $categories = $categoriesStmt->fetchAll();
    
    // Get popular tags
    $tagsStmt = $db->query("
        SELECT bt.name, bt.slug, COUNT(*) as count
        FROM blog_tags bt
        INNER JOIN blog_tag_relations btr ON bt.id = btr.tag_id
        INNER JOIN blogs b ON btr.blog_id = b.id
        WHERE b.is_published = 1
        GROUP BY bt.id
        ORDER BY count DESC
        LIMIT 10
    ");
    $tags = $tagsStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'blogs' => $blogs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalRecords / $limit),
            'total_records' => $totalRecords,
            'per_page' => $limit
        ],
        'filters' => [
            'categories' => $categories,
            'tags' => $tags
        ]
    ]);
}
?>