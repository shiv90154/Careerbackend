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

    // Check if testimonials table exists, if not return dummy data
    $stmt = $db->prepare("SHOW TABLES LIKE 'testimonials'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        $stmt = $db->prepare("
            SELECT 
                id,
                student_name,
                student_initials,
                course_name,
                message,
                rating
            FROM testimonials
            WHERE status = 'published'
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Return dummy testimonials data
        $testimonials = [
            [
                'id' => 1,
                'student_name' => 'Rahul Sharma',
                'student_initials' => 'RS',
                'course_name' => 'HPAS Preparation',
                'message' => 'Excellent coaching and guidance. Cleared HPAS in first attempt!',
                'rating' => 5
            ],
            [
                'id' => 2,
                'student_name' => 'Priya Thakur',
                'student_initials' => 'PT',
                'course_name' => 'Banking Preparation',
                'message' => 'Great faculty and study material. Highly recommended!',
                'rating' => 5
            ],
            [
                'id' => 3,
                'student_name' => 'Amit Kumar',
                'student_initials' => 'AK',
                'course_name' => 'SSC Preparation',
                'message' => 'Best institute in Shimla for competitive exam preparation.',
                'rating' => 4
            ]
        ];
    }

    echo json_encode($testimonials);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => "Failed to load testimonials: " . $e->getMessage()
    ]);
}
?>
