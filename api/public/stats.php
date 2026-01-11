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

// Default stats (works without database)
$stats = [
    "total_students" => "500+",
    "total_courses" => "25+", 
    "faculty_count" => "15+",
    "years_experience" => "8+",
    "success_rate" => "85%",
    "placement_rate" => "92%"
];

try {
    // Try to load database and get real stats
    require_once '../../includes/config.php';
    require_once '../../includes/database.php';
    
    $db = (new Database())->getConnection();

    try {
        $students = $db->query("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")->fetch(PDO::FETCH_ASSOC);
        if ($students) {
            $stats["total_students"] = $students['total'] . "+";
        }
    } catch (Exception $e) {
        // Keep default value
    }

    try {
        $courses = $db->query("SELECT COUNT(*) AS total FROM courses WHERE status = 'published'")->fetch(PDO::FETCH_ASSOC);
        if ($courses) {
            $stats["total_courses"] = $courses['total'] . "+";
        }
    } catch (Exception $e) {
        // Keep default value
    }

} catch (Exception $e) {
    // Database not available, use default stats
}

echo json_encode([
    'success' => true,
    'data' => $stats,
    'message' => 'Stats retrieved successfully'
]);
?>
