<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { 
    http_response_code(200); 
    exit(); 
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Default courses (works without database)
$defaultCourses = [
    [
        'id' => 1,
        'title' => 'HPAS Mains Complete Course',
        'slug' => 'hpas-mains-complete',
        'short_description' => 'Comprehensive preparation for HPAS Mains examination',
        'description' => 'Complete course covering all subjects for HPAS Mains with expert faculty guidance.',
        'thumbnail' => null,
        'category_id' => 1,
        'category_name' => 'HPAS Preparation',
        'instructor_name' => 'Dr. Rajesh Kumar',
        'level' => 'intermediate',
        'price' => 15000.00,
        'discount_price' => 12000.00,
        'duration_days' => 180,
        'total_lessons' => 120,
        'is_featured' => 1,
        'is_free' => 0,
        'status' => 'published',
        'enrollment_count' => 45,
        'rating_avg' => 4.5,
        'created_at' => '2024-01-15 10:00:00'
    ],
    [
        'id' => 2,
        'title' => 'Banking PO Preparation',
        'slug' => 'banking-po-preparation',
        'short_description' => 'Complete banking PO exam preparation course',
        'description' => 'Comprehensive course for banking PO exams with quantitative aptitude, reasoning, and English.',
        'thumbnail' => null,
        'category_id' => 2,
        'category_name' => 'Banking',
        'instructor_name' => 'Prof. Anita Sharma',
        'level' => 'beginner',
        'price' => 8000.00,
        'discount_price' => 6000.00,
        'duration_days' => 120,
        'total_lessons' => 80,
        'is_featured' => 0,
        'is_free' => 0,
        'status' => 'published',
        'enrollment_count' => 32,
        'rating_avg' => 4.2,
        'created_at' => '2024-02-01 09:00:00'
    ],
    [
        'id' => 3,
        'title' => 'SSC CGL Foundation Course',
        'slug' => 'ssc-cgl-foundation',
        'short_description' => 'Foundation course for SSC CGL examination',
        'description' => 'Build strong foundation for SSC CGL with comprehensive study material and practice tests.',
        'thumbnail' => null,
        'category_id' => 3,
        'category_name' => 'SSC',
        'instructor_name' => 'Mr. Vikram Singh',
        'level' => 'beginner',
        'price' => 0.00,
        'discount_price' => null,
        'duration_days' => 90,
        'total_lessons' => 60,
        'is_featured' => 1,
        'is_free' => 1,
        'status' => 'published',
        'enrollment_count' => 128,
        'rating_avg' => 4.7,
        'created_at' => '2024-01-20 11:00:00'
    ]
];

try {
    // Try to load database and get real courses
    require_once '../../includes/config.php';
    require_once '../../includes/database.php';
    
    $db = (new Database())->getConnection();
    
    // Get parameters
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
    
    // Bind all parameters
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $courses = $stmt->fetchAll();
    
    // Get total count
    $countSql = "SELECT COUNT(DISTINCT c.id) as total FROM courses c WHERE $whereClause";
    $countStmt = $db->prepare($countSql);
    
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

} catch (Exception $e) {
    // Database not available, use default courses
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 12;
    
    // Apply basic filtering to default courses
    $filteredCourses = $defaultCourses;
    
    if (isset($_GET['featured']) && $_GET['featured']) {
        $filteredCourses = array_filter($filteredCourses, function($course) {
            return $course['is_featured'] == 1;
        });
    }
    
    if (isset($_GET['price']) && $_GET['price'] === 'free') {
        $filteredCourses = array_filter($filteredCourses, function($course) {
            return $course['price'] == 0;
        });
    }
    
    // Apply pagination
    $total = count($filteredCourses);
    $offset = ($page - 1) * $limit;
    $paginatedCourses = array_slice($filteredCourses, $offset, $limit);
    
    echo json_encode([
        'success' => true,
        'courses' => array_values($paginatedCourses),
        'pagination' => [
            'current_page' => (int)$page,
            'total_pages' => ceil($total / $limit),
            'total_items' => (int)$total,
            'per_page' => (int)$limit
        ]
    ]);
}
?>