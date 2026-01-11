<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

$user = Auth::requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db = (new Database())->getConnection();

switch ($method) {
    case 'GET':
        $courseId = $_GET['course_id'] ?? '';
        
        if (!$courseId) {
            http_response_code(400);
            echo json_encode(['message' => 'Course ID required']);
            exit;
        }
        
        // Check enrollment
        $enrollStmt = $db->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active'");
        $enrollStmt->execute([$user['id'], $courseId]);
        
        if (!$enrollStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['message' => 'Not enrolled in this course']);
            exit;
        }
        
        // Get lesson progress
        $stmt = $db->prepare("
            SELECT 
                l.id as lesson_id,
                l.title,
                l.lesson_type,
                l.video_duration,
                lp.is_completed,
                lp.completion_date,
                lp.watch_time,
                lp.last_position
            FROM lessons l
            LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = ?
            WHERE l.course_id = ?
            ORDER BY l.sort_order
        ");
        $stmt->execute([$user['id'], $courseId]);
        $progress = $stmt->fetchAll();
        
        echo json_encode(['progress' => $progress]);
        break;
        
    case 'POST':
        // Update lesson progress
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required = ['course_id', 'lesson_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['message' => "Field $field is required"]);
                exit;
            }
        }
        
        // Check enrollment
        $enrollStmt = $db->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active'");
        $enrollStmt->execute([$user['id'], $data['course_id']]);
        
        if (!$enrollStmt->fetch()) {
            http_response_code(403);
            echo json_encode(['message' => 'Not enrolled in this course']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO lesson_progress (user_id, lesson_id, course_id, is_completed, completion_date, watch_time, last_position)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    is_completed = VALUES(is_completed),
                    completion_date = VALUES(completion_date),
                    watch_time = VALUES(watch_time),
                    last_position = VALUES(last_position),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $completionDate = !empty($data['is_completed']) ? date('Y-m-d H:i:s') : null;
            
            $stmt->execute([
                $user['id'],
                $data['lesson_id'],
                $data['course_id'],
                $data['is_completed'] ?? 0,
                $completionDate,
                $data['watch_time'] ?? 0,
                $data['last_position'] ?? 0
            ]);
            
            // Update enrollment progress
            $progressStmt = $db->prepare("
                UPDATE enrollments e
                SET progress_percentage = (
                    SELECT ROUND((COUNT(CASE WHEN lp.is_completed = 1 THEN 1 END) / COUNT(*)) * 100, 2)
                    FROM lessons l
                    LEFT JOIN lesson_progress lp ON l.id = lp.lesson_id AND lp.user_id = e.user_id
                    WHERE l.course_id = e.course_id
                )
                WHERE user_id = ? AND course_id = ?
            ");
            $progressStmt->execute([$user['id'], $data['course_id']]);
            
            echo json_encode(['message' => 'Progress updated successfully']);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
}
?>