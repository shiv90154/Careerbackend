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
        if (isset($_GET['id'])) {
            // Get single course
            $stmt = $db->prepare("
                SELECT 
                    c.*,
                    cat.name as category_name,
                    u.full_name as instructor_name
                FROM courses c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $course = $stmt->fetch();
            
            if (!$course) {
                http_response_code(404);
                echo json_encode(['message' => 'Course not found']);
                exit;
            }
            
            echo json_encode(['course' => $course]);
        } else {
            // Get all courses
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $where = [];
            $params = [];
            
            if ($search) {
                $where[] = "(c.title LIKE ? OR c.description LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($status) {
                $where[] = "c.status = ?";
                $params[] = $status;
            }
            
            $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            
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
                $whereClause
                GROUP BY c.id
                ORDER BY c.created_at DESC
                LIMIT $limit OFFSET $offset
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $courses = $stmt->fetchAll();
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM courses c $whereClause";
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch()['total'];
            
            echo json_encode([
                'courses' => $courses,
                'pagination' => [
                    'current_page' => (int)$page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => (int)$total
                ]
            ]);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required = ['title', 'category_id', 'instructor_id', 'price'];
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
                INSERT INTO courses (
                    title, slug, description, short_description, category_id, 
                    instructor_id, level, language, price, discount_price,
                    duration_hours, requirements, what_you_learn, target_audience,
                    status, is_featured, certificate_available
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['title'],
                $slug,
                $data['description'] ?? '',
                $data['short_description'] ?? '',
                $data['category_id'],
                $data['instructor_id'],
                $data['level'] ?? 'beginner',
                $data['language'] ?? 'English',
                $data['price'],
                $data['discount_price'] ?? null,
                $data['duration_hours'] ?? null,
                $data['requirements'] ?? '',
                $data['what_you_learn'] ?? '',
                $data['target_audience'] ?? '',
                $data['status'] ?? 'draft',
                $data['is_featured'] ?? 0,
                $data['certificate_available'] ?? 1
            ]);
            
            $courseId = $db->lastInsertId();
            
            echo json_encode([
                'message' => 'Course created successfully',
                'course_id' => $courseId
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $courseId = $data['id'] ?? '';
        
        if (!$courseId) {
            http_response_code(400);
            echo json_encode(['message' => 'Course ID required']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("
                UPDATE courses SET
                    title = ?, description = ?, short_description = ?, category_id = ?,
                    instructor_id = ?, level = ?, language = ?, price = ?, discount_price = ?,
                    duration_hours = ?, requirements = ?, what_you_learn = ?, target_audience = ?,
                    status = ?, is_featured = ?, certificate_available = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['title'],
                $data['description'] ?? '',
                $data['short_description'] ?? '',
                $data['category_id'],
                $data['instructor_id'],
                $data['level'] ?? 'beginner',
                $data['language'] ?? 'English',
                $data['price'],
                $data['discount_price'] ?? null,
                $data['duration_hours'] ?? null,
                $data['requirements'] ?? '',
                $data['what_you_learn'] ?? '',
                $data['target_audience'] ?? '',
                $data['status'] ?? 'draft',
                $data['is_featured'] ?? 0,
                $data['certificate_available'] ?? 1,
                $courseId
            ]);
            
            echo json_encode(['message' => 'Course updated successfully']);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        $courseId = $_GET['id'] ?? '';
        
        if (!$courseId) {
            http_response_code(400);
            echo json_encode(['message' => 'Course ID required']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);
            
            echo json_encode(['message' => 'Course deleted successfully']);
            
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