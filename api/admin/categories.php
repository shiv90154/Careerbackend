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
            // Get single category
            $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $category = $stmt->fetch();
            
            if (!$category) {
                http_response_code(404);
                echo json_encode(['message' => 'Category not found']);
                exit;
            }
            
            echo json_encode(['category' => $category]);
        } else {
            // Get all categories
            $stmt = $db->prepare("
                SELECT 
                    c.*,
                    p.name as parent_name,
                    COUNT(DISTINCT co.id) as course_count
                FROM categories c
                LEFT JOIN categories p ON c.parent_id = p.id
                LEFT JOIN courses co ON c.id = co.category_id
                GROUP BY c.id
                ORDER BY c.sort_order, c.name
            ");
            $stmt->execute();
            $categories = $stmt->fetchAll();
            
            echo json_encode(['categories' => $categories]);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Category name is required']);
            exit;
        }
        
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']));
        
        try {
            $stmt = $db->prepare("
                INSERT INTO categories (name, slug, description, parent_id, is_active, sort_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $slug,
                $data['description'] ?? '',
                $data['parent_id'] ?? null,
                $data['is_active'] ?? 1,
                $data['sort_order'] ?? 0
            ]);
            
            $categoryId = $db->lastInsertId();
            
            echo json_encode([
                'message' => 'Category created successfully',
                'category_id' => $categoryId
            ]);
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                http_response_code(409);
                echo json_encode(['message' => 'Category with this name already exists']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $categoryId = $data['id'] ?? '';
        
        if (!$categoryId) {
            http_response_code(400);
            echo json_encode(['message' => 'Category ID required']);
            exit;
        }
        
        if (empty($data['name'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Category name is required']);
            exit;
        }
        
        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['name']));
        
        try {
            $stmt = $db->prepare("
                UPDATE categories SET
                    name = ?, slug = ?, description = ?, parent_id = ?,
                    is_active = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $slug,
                $data['description'] ?? '',
                $data['parent_id'] ?? null,
                $data['is_active'] ?? 1,
                $data['sort_order'] ?? 0,
                $categoryId
            ]);
            
            echo json_encode(['message' => 'Category updated successfully']);
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                http_response_code(409);
                echo json_encode(['message' => 'Category with this name already exists']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'DELETE':
        $categoryId = $_GET['id'] ?? '';
        
        if (!$categoryId) {
            http_response_code(400);
            echo json_encode(['message' => 'Category ID required']);
            exit;
        }
        
        try {
            // Check if category has courses
            $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM courses WHERE category_id = ?");
            $checkStmt->execute([$categoryId]);
            $courseCount = $checkStmt->fetch()['count'];
            
            if ($courseCount > 0) {
                http_response_code(409);
                echo json_encode(['message' => 'Cannot delete category with existing courses']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            
            echo json_encode(['message' => 'Category deleted successfully']);
            
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