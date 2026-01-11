<?php

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
        
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];
        
        if ($search) {
            $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR p.transaction_id LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($status) {
            $where[] = "p.status = ?";
            $params[] = $status;
        }
        
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get payments
        $sql = "
            SELECT 
                p.*,
                u.full_name as student_name,
                u.email as student_email,
                c.title as course_title,
                c.price as course_price
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN courses c ON p.course_id = c.id
            $whereClause
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $db->prepare($sql);
        
        // Bind all parameters except LIMIT and OFFSET
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i]);
        }
        
        // Bind LIMIT and OFFSET as integers
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $payments = $stmt->fetchAll();
        
        // Get total count
        $countSql = "
            SELECT COUNT(*) as total
            FROM payments p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN courses c ON p.course_id = c.id
            $whereClause
        ";
        $countStmt = $db->prepare($countSql);
        
        // Bind parameters for count query
        for ($i = 0; $i < count($params); $i++) {
            $countStmt->bindValue($i + 1, $params[$i]);
        }
        
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'payments' => $payments,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => ceil($total / $limit),
                'total_items' => (int)$total
            ]
        ]);
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $paymentId = $data['id'] ?? '';
        $status = $data['status'] ?? '';
        
        if (!$paymentId || !$status) {
            http_response_code(400);
            echo json_encode(['message' => 'Payment ID and status are required']);
            exit;
        }
        
        $validStatuses = ['pending', 'completed', 'failed', 'refunded'];
        if (!in_array($status, $validStatuses)) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid status']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("UPDATE payments SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $paymentId]);
            
            // If payment is completed, ensure enrollment is active
            if ($status === 'completed') {
                $paymentStmt = $db->prepare("SELECT user_id, course_id, enrollment_id FROM payments WHERE id = ?");
                $paymentStmt->execute([$paymentId]);
                $payment = $paymentStmt->fetch();
                
                if ($payment && $payment['enrollment_id']) {
                    $enrollmentStmt = $db->prepare("UPDATE enrollments SET status = 'active', payment_status = 'paid' WHERE id = ?");
                    $enrollmentStmt->execute([$payment['enrollment_id']]);
                }
            }
            
            echo json_encode(['message' => 'Payment status updated successfully']);
            
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