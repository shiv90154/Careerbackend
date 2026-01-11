<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = (new Database())->getConnection();
    
    switch ($method) {
        case 'GET':
            handleGetMaterials($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

function handleGetMaterials($db) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    $offset = ($page - 1) * $limit;
    
    // Build query conditions
    $whereConditions = [];
    $params = [];
    
    if (!empty($type)) {
        $whereConditions[] = "m.type = ?";
        $params[] = $type;
    }
    
    if ($course_id > 0) {
        $whereConditions[] = "m.course_id = ?";
        $params[] = $course_id;
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(m.title LIKE ? OR m.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    $countQuery = "
        SELECT COUNT(*) as total
        FROM materials m
        $whereClause
    ";
    $countStmt = $db->prepare($countQuery);
    
    // Bind parameters for count query
    for ($i = 0; $i < count($params); $i++) {
        $countStmt->bindValue($i + 1, $params[$i]);
    }
    
    $countStmt->execute();
    $totalRecords = $countStmt->fetch()['total'];
    
    // Get materials
    $query = "
        SELECT 
            m.*,
            c.title as course_title,
            cat.name as category_name,
            u.full_name as created_by_name
        FROM materials m
        LEFT JOIN courses c ON m.course_id = c.id
        LEFT JOIN categories cat ON m.category_id = cat.id
        LEFT JOIN users u ON m.created_by = u.id
        $whereClause
        ORDER BY m.created_at DESC
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
    $materials = $stmt->fetchAll();
    
    // Format data
    foreach ($materials as &$material) {
        $material['file_size_formatted'] = $material['file_size'] ? formatFileSize($material['file_size']) : null;
        $material['created_at_formatted'] = date('M d, Y', strtotime($material['created_at']));
        $material['type_icon'] = getFileTypeIcon($material['type']);
    }
    
    // Get material types for filter
    $typesStmt = $db->query("
        SELECT DISTINCT type 
        FROM materials 
        ORDER BY type
    ");
    $types = $typesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'materials' => $materials,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalRecords / $limit),
            'total_records' => $totalRecords,
            'per_page' => $limit
        ],
        'filters' => [
            'types' => $types
        ]
    ]);
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function getFileTypeIcon($type) {
    $icons = [
        'pdf' => 'file-text',
        'doc' => 'file-text',
        'video' => 'play-circle',
        'audio' => 'headphones',
        'image' => 'image',
        'link' => 'external-link',
        'zip' => 'archive',
        'ppt' => 'presentation'
    ];
    
    return $icons[$type] ?? 'file';
}
?>