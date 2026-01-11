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

// Default categories (works without database)
$defaultCategories = [
    [
        'id' => 1,
        'name' => 'HPAS Preparation',
        'slug' => 'hpas-preparation',
        'description' => 'Himachal Pradesh Administrative Service preparation courses',
        'image' => null,
        'course_count' => 8
    ],
    [
        'id' => 2,
        'name' => 'Banking',
        'slug' => 'banking',
        'description' => 'Banking sector competitive exam preparation',
        'image' => null,
        'course_count' => 5
    ],
    [
        'id' => 3,
        'name' => 'SSC',
        'slug' => 'ssc',
        'description' => 'Staff Selection Commission exam preparation',
        'image' => null,
        'course_count' => 6
    ],
    [
        'id' => 4,
        'name' => 'Teaching',
        'slug' => 'teaching',
        'description' => 'Teaching exam preparation courses',
        'image' => null,
        'course_count' => 4
    ]
];

try {
    // Try to load database and get real categories
    require_once '../../includes/config.php';
    require_once '../../includes/database.php';
    
    $db = (new Database())->getConnection();
    
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

} catch (Exception $e) {
    // Database not available, use default categories
    echo json_encode([
        'success' => true,
        'categories' => $defaultCategories
    ]);
}
?>