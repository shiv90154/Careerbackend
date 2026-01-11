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
        $role = $_GET['role'] ?? '';
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];
        
        if ($role) {
            $where[] = "role = ?";
            $params[] = $role;
        }
        
        if ($search) {
            $where[] = "(full_name LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get users
        $sql = "
            SELECT 
                u.*,
                COUNT(DISTINCT e.id) as enrollment_count,
                COUNT(DISTINCT c.id) as course_count
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.user_id
            LEFT JOIN courses c ON u.id = c.instructor_id
            $whereClause
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users u $whereClause";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'users' => $users,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total
            ]
        ]);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required = ['full_name', 'email', 'password', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['message' => "Field $field is required"]);
                exit;
            }
        }
        
        // Check if email exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$data['email']]);
        
        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['message' => 'Email already exists']);
            exit;
        }
        
        try {
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            
            $stmt = $db->prepare("
                INSERT INTO users (
                    role, full_name, email, password, phone, address, 
                    city, state, pincode, is_active, email_verified
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['role'],
                $data['full_name'],
                $data['email'],
                $passwordHash,
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['pincode'] ?? null,
                $data['is_active'] ?? 1,
                $data['email_verified'] ?? 0
            ]);
            
            $userId = $db->lastInsertId();
            
            echo json_encode([
                'message' => 'User created successfully',
                'user_id' => $userId
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $userId = $data['id'] ?? '';
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['message' => 'User ID required']);
            exit;
        }
        
        try {
            $updateFields = [];
            $params = [];
            
            if (!empty($data['full_name'])) {
                $updateFields[] = "full_name = ?";
                $params[] = $data['full_name'];
            }
            
            if (!empty($data['email'])) {
                // Check if email exists for other users
                $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $checkStmt->execute([$data['email'], $userId]);
                
                if ($checkStmt->fetch()) {
                    http_response_code(409);
                    echo json_encode(['message' => 'Email already exists']);
                    exit;
                }
                
                $updateFields[] = "email = ?";
                $params[] = $data['email'];
            }
            
            if (!empty($data['phone'])) {
                $updateFields[] = "phone = ?";
                $params[] = $data['phone'];
            }
            
            if (isset($data['is_active'])) {
                $updateFields[] = "is_active = ?";
                $params[] = $data['is_active'];
            }
            
            if (!empty($data['password'])) {
                $updateFields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['message' => 'No fields to update']);
                exit;
            }
            
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode(['message' => 'User updated successfully']);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        $userId = $_GET['id'] ?? '';
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['message' => 'User ID required']);
            exit;
        }
        
        try {
            // Check if user has enrollments or courses
            $checkStmt = $db->prepare("
                SELECT 
                    COUNT(DISTINCT e.id) as enrollments,
                    COUNT(DISTINCT c.id) as courses
                FROM users u
                LEFT JOIN enrollments e ON u.id = e.user_id
                LEFT JOIN courses c ON u.id = c.instructor_id
                WHERE u.id = ?
            ");
            $checkStmt->execute([$userId]);
            $counts = $checkStmt->fetch();
            
            if ($counts['enrollments'] > 0 || $counts['courses'] > 0) {
                // Deactivate instead of delete
                $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                $stmt->execute([$userId]);
                echo json_encode(['message' => 'User deactivated successfully']);
            } else {
                // Safe to delete
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                echo json_encode(['message' => 'User deleted successfully']);
            }
            
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