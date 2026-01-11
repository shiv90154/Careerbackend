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
        // Get user's enrollments
        $stmt = $db->prepare("
            SELECT 
                e.*,
                c.title as course_title,
                c.thumbnail,
                c.description,
                c.instructor_id,
                u.full_name as instructor_name,
                c.total_lessons,
                c.duration_hours,
                COUNT(DISTINCT lp.id) as completed_lessons,
                CASE 
                    WHEN c.total_lessons > 0 
                    THEN ROUND((COUNT(DISTINCT lp.id) / c.total_lessons) * 100, 2)
                    ELSE 0 
                END as progress_percentage
            FROM enrollments e
            LEFT JOIN courses c ON e.course_id = c.id
            LEFT JOIN users u ON c.instructor_id = u.id
            LEFT JOIN lesson_progress lp ON e.course_id = lp.course_id AND e.user_id = lp.user_id AND lp.is_completed = 1
            WHERE e.user_id = ?
            GROUP BY e.id
            ORDER BY e.enrollment_date DESC
        ");
        $stmt->execute([$user['id']]);
        $enrollments = $stmt->fetchAll();
        
        echo json_encode(['enrollments' => $enrollments]);
        break;
        
    case 'POST':
        // Enroll in course
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['course_id'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Course ID required']);
            exit;
        }
        
        // Check if already enrolled
        $checkStmt = $db->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
        $checkStmt->execute([$user['id'], $data['course_id']]);
        
        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['message' => 'Already enrolled in this course']);
            exit;
        }
        
        // Check course exists and is published
        $courseStmt = $db->prepare("SELECT id, price FROM courses WHERE id = ? AND status = 'published'");
        $courseStmt->execute([$data['course_id']]);
        $course = $courseStmt->fetch();
        
        if (!$course) {
            http_response_code(404);
            echo json_encode(['message' => 'Course not found or not available']);
            exit;
        }
        
        try {
            $db->beginTransaction();
            
            // Create enrollment
            $enrollStmt = $db->prepare("
                INSERT INTO enrollments (user_id, course_id, payment_status, payment_amount)
                VALUES (?, ?, ?, ?)
            ");
            
            $paymentStatus = $course['price'] > 0 ? 'pending' : 'free';
            $enrollStmt->execute([
                $user['id'],
                $data['course_id'],
                $paymentStatus,
                $course['price']
            ]);
            
            $enrollmentId = $db->lastInsertId();
            
            // If free course, activate immediately
            if ($course['price'] == 0) {
                $updateStmt = $db->prepare("UPDATE enrollments SET status = 'active' WHERE id = ?");
                $updateStmt->execute([$enrollmentId]);
            }
            
            $db->commit();
            
            echo json_encode([
                'message' => 'Enrollment successful',
                'enrollment_id' => $enrollmentId,
                'requires_payment' => $course['price'] > 0
            ]);
            
        } catch (PDOException $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
}
?>