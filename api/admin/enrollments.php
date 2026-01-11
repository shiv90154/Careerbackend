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
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $courseId = $_GET['course_id'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];
        
        if ($search) {
            $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR c.title LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($status) {
            $where[] = "e.status = ?";
            $params[] = $status;
        }
        
        if ($courseId) {
            $where[] = "e.course_id = ?";
            $params[] = $courseId;
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get enrollments
        $sql = "
            SELECT 
                e.*,
                u.full_name as student_name,
                u.email as student_email,
                c.title as course_title,
                c.price as course_price,
                p.final_amount as payment_amount,
                p.status as payment_status
            FROM enrollments e
            LEFT JOIN users u ON e.user_id = u.id
            LEFT JOIN courses c ON e.course_id = c.id
            LEFT JOIN payments p ON e.payment_id = p.id
            $whereClause
            ORDER BY e.enrollment_date DESC
            LIMIT $limit OFFSET $offset
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $enrollments = $stmt->fetchAll();
        
        // Get total count
        $countSql = "
            SELECT COUNT(*) as total 
            FROM enrollments e
            LEFT JOIN users u ON e.user_id = u.id
            LEFT JOIN courses c ON e.course_id = c.id
            $whereClause
        ";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'enrollments' => $enrollments,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total
            ]
        ]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
}
?>