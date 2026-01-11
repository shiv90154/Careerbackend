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

$user = Auth::requireRole('admin');
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
        
        if (isset($_GET['id'])) {
            // Get single lesson
            $stmt = $db->prepare("
                SELECT 
                    l.*,
                    cs.title as section_title
                FROM lessons l
                LEFT JOIN course_sections cs ON l.section_id = cs.id
                WHERE l.id = ? AND l.course_id = ?
            ");
            $stmt->execute([$_GET['id'], $courseId]);
            $lesson = $stmt->fetch();
            
            if (!$lesson) {
                http_response_code(404);
                echo json_encode(['message' => 'Lesson not found']);
                exit;
            }
            
            echo json_encode(['lesson' => $lesson]);
        } else {
            // Get all lessons for course
            $stmt = $db->prepare("
                SELECT 
                    l.*,
                    cs.title as section_title
                FROM lessons l
                LEFT JOIN course_sections cs ON l.section_id = cs.id
                WHERE l.course_id = ?
                ORDER BY cs.sort_order, l.sort_order
            ");
            $stmt->execute([$courseId]);
            $lessons = $stmt->fetchAll();
            
            echo json_encode(['lessons' => $lessons]);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required = ['course_id', 'title', 'lesson_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['message' => "Field $field is required"]);
                exit;
            }
        }
        
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title']));
        
        try {
            $stmt = $db->prepare("
                INSERT INTO lessons (
                    course_id, section_id, title, slug, content, video_url,
                    video_duration, lesson_type, file_path, is_preview, sort_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['course_id'],
                $data['section_id'] ?? null,
                $data['title'],
                $slug,
                $data['content'] ?? '',
                $data['video_url'] ?? '',
                $data['video_duration'] ?? null,
                $data['lesson_type'],
                $data['file_path'] ?? '',
                $data['is_preview'] ?? 0,
                $data['sort_order'] ?? 0
            ]);
            
            $lessonId = $db->lastInsertId();
            
            echo json_encode([
                'message' => 'Lesson created successfully',
                'lesson_id' => $lessonId
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $lessonId = $data['id'] ?? '';
        
        if (!$lessonId) {
            http_response_code(400);
            echo json_encode(['message' => 'Lesson ID required']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("
                UPDATE lessons SET
                    section_id = ?, title = ?, content = ?, video_url = ?,
                    video_duration = ?, lesson_type = ?, file_path = ?,
                    is_preview = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['section_id'] ?? null,
                $data['title'],
                $data['content'] ?? '',
                $data['video_url'] ?? '',
                $data['video_duration'] ?? null,
                $data['lesson_type'],
                $data['file_path'] ?? '',
                $data['is_preview'] ?? 0,
                $data['sort_order'] ?? 0,
                $lessonId
            ]);
            
            echo json_encode(['message' => 'Lesson updated successfully']);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        $lessonId = $_GET['id'] ?? '';
        
        if (!$lessonId) {
            http_response_code(400);
            echo json_encode(['message' => 'Lesson ID required']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM lessons WHERE id = ?");
            $stmt->execute([$lessonId]);
            
            echo json_encode(['message' => 'Lesson deleted successfully']);
            
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