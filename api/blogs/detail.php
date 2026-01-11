<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    
    switch ($method) {
        case 'GET':
            handleGetBlogDetail($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetBlogDetail($db) {
    $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (empty($slug) && !$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Blog slug or ID is required']);
        return;
    }
    
    // Build query based on available parameter
    if (!empty($slug)) {
        $whereClause = "b.slug = ?";
        $param = $slug;
    } else {
        $whereClause = "b.id = ?";
        $param = $id;
    }
    
    // Get blog details
    $query = "
        SELECT 
            b.*,
            u.full_name as author_name,
            u.bio as author_bio,
            u.profile_image as author_image,
            c.name as category_name,
            c.id as category_id
        FROM blogs b
        LEFT JOIN users u ON b.author_id = u.id
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE $whereClause AND b.is_published = 1
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$param]);
    $blog = $stmt->fetch();
    
    if (!$blog) {
        http_response_code(404);
        echo json_encode(['error' => 'Blog not found']);
        return;
    }
    
    // Get blog tags
    $tagsQuery = "
        SELECT bt.name, bt.slug
        FROM blog_tags bt
        INNER JOIN blog_tag_relations btr ON bt.id = btr.tag_id
        WHERE btr.blog_id = ?
    ";
    $tagsStmt = $db->prepare($tagsQuery);
    $tagsStmt->execute([$blog['id']]);
    $blog['tags'] = $tagsStmt->fetchAll();
    
    // Update view count
    $updateViewsStmt = $db->prepare("UPDATE blogs SET views_count = views_count + 1 WHERE id = ?");
    $updateViewsStmt->execute([$blog['id']]);
    $blog['views_count']++;
    
    // Get related blogs
    $relatedQuery = "
        SELECT b.id, b.title, b.slug, b.excerpt, b.featured_image, b.created_at
        FROM blogs b
        WHERE b.category_id = ? AND b.id != ? AND b.is_published = 1
        ORDER BY b.created_at DESC
        LIMIT 4
    ";
    $relatedStmt = $db->prepare($relatedQuery);
    $relatedStmt->execute([$blog['category_id'], $blog['id']]);
    $relatedBlogs = $relatedStmt->fetchAll();
    
    // Format dates
    $blog['created_at_formatted'] = date('M d, Y', strtotime($blog['created_at']));
    $blog['updated_at_formatted'] = date('M d, Y', strtotime($blog['updated_at']));
    
    foreach ($relatedBlogs as &$relatedBlog) {
        $relatedBlog['created_at_formatted'] = date('M d, Y', strtotime($relatedBlog['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'blog' => $blog,
        'related_blogs' => $relatedBlogs
    ]);
}
?>