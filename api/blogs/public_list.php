<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }


require_once '../../includes/config.php';
require_once '../../includes/database.php';

try {
    $db = (new Database())->getConnection();

    // Check if blogs table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'blogs'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        $stmt = $db->query("
            SELECT 
                id,
                title, 
                slug, 
                excerpt, 
                featured_image,
                published_at,
                view_count,
                is_featured
            FROM blogs 
            WHERE status='published'
            ORDER BY published_at DESC
            LIMIT 20
        ");
        $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Return dummy blogs
        $blogs = [
            [
                'id' => 1,
                'title' => 'How to Crack HPAS Exam in First Attempt',
                'slug' => 'how-to-crack-hpas-exam',
                'excerpt' => 'Complete guide to prepare for HPAS examination with proven strategies and tips.',
                'featured_image' => '/images/blog1.jpg',
                'published_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'view_count' => 150,
                'is_featured' => 1
            ],
            [
                'id' => 2,
                'title' => 'Banking Exam Preparation Strategy',
                'slug' => 'banking-exam-preparation-strategy',
                'excerpt' => 'Essential tips and tricks for banking competitive exams preparation.',
                'featured_image' => '/images/blog2.jpg',
                'published_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'view_count' => 89,
                'is_featured' => 0
            ],
            [
                'id' => 3,
                'title' => 'Current Affairs for Competitive Exams',
                'slug' => 'current-affairs-competitive-exams',
                'excerpt' => 'Stay updated with latest current affairs for your competitive exam preparation.',
                'featured_image' => '/images/blog3.jpg',
                'published_at' => date('Y-m-d H:i:s', strtotime('-1 week')),
                'view_count' => 234,
                'is_featured' => 0
            ]
        ];
    }

    echo json_encode(['blogs' => $blogs]);

} catch (Exception $e) {
    // Return default blogs even if database fails
    echo json_encode([
        'blogs' => [
            [
                'id' => 1,
                'title' => 'How to Crack HPAS Exam in First Attempt',
                'slug' => 'how-to-crack-hpas-exam',
                'excerpt' => 'Complete guide to prepare for HPAS examination with proven strategies and tips.',
                'featured_image' => '/images/blog1.jpg',
                'published_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'view_count' => 150,
                'is_featured' => 1
            ]
        ]
    ]);
}
?>
