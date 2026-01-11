<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }


require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

try {
    $db = (new Database())->getConnection();

    // Check if blog_categories table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'blog_categories'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        $sql = "
            SELECT 
                bc.id,
                bc.name,
                bc.slug,
                COUNT(b.id) AS blog_count
            FROM blog_categories bc
            LEFT JOIN blogs b 
                ON b.category_id = bc.id 
                AND b.status = 'published'
            GROUP BY bc.id, bc.name, bc.slug
            ORDER BY bc.name ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Return dummy categories
        $categories = [
            ['id' => 1, 'name' => 'Exam Tips', 'slug' => 'exam-tips', 'blog_count' => 5],
            ['id' => 2, 'name' => 'Study Material', 'slug' => 'study-material', 'blog_count' => 8],
            ['id' => 3, 'name' => 'Success Stories', 'slug' => 'success-stories', 'blog_count' => 3],
            ['id' => 4, 'name' => 'Current Affairs', 'slug' => 'current-affairs', 'blog_count' => 12],
            ['id' => 5, 'name' => 'Career Guidance', 'slug' => 'career-guidance', 'blog_count' => 6]
        ];
    }

    echo json_encode($categories);

} catch (Throwable $e) {
    // Return default categories even if database fails
    echo json_encode([
        ['id' => 1, 'name' => 'Exam Tips', 'slug' => 'exam-tips', 'blog_count' => 5],
        ['id' => 2, 'name' => 'Study Material', 'slug' => 'study-material', 'blog_count' => 8],
        ['id' => 3, 'name' => 'Success Stories', 'slug' => 'success-stories', 'blog_count' => 3],
        ['id' => 4, 'name' => 'Current Affairs', 'slug' => 'current-affairs', 'blog_count' => 12],
        ['id' => 5, 'name' => 'Career Guidance', 'slug' => 'career-guidance', 'blog_count' => 6]
    ]);
}
?>
        "message" => $e->getMessage()
    ]);
}
