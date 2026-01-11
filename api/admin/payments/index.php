<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

/*
|--------------------------------------------------------------------------
| AUTH CHECK
|--------------------------------------------------------------------------
| Agar admin hai → sab payments
| Agar student hai → sirf apne payments
*/
$user = requireAuth();

$db = (new Database())->getConnection();

if ($user->role === 'admin') {

    // 🔹 Admin: all payments
    $stmt = $db->query("
        SELECT 
            p.id,
            u.full_name AS student_name,
            u.email,
            c.title AS course_title,
            p.amount,
            p.discount_amount,
            p.final_amount,
            p.payment_method,
            p.transaction_id,
            p.status,
            p.payment_date,
            p.created_at
        FROM payments p
        JOIN users u ON u.id = p.student_id
        JOIN courses c ON c.id = p.course_id
        ORDER BY p.created_at DESC
    ");

    $payments = $stmt->fetchAll();

} else {

    // 🔹 Student: only own payments
    $stmt = $db->prepare("
        SELECT 
            p.id,
            c.title AS course_title,
            p.amount,
            p.discount_amount,
            p.final_amount,
            p.payment_method,
            p.transaction_id,
            p.status,
            p.payment_date,
            p.created_at
        FROM payments p
        JOIN courses c ON c.id = p.course_id
        WHERE p.student_id = ?
        ORDER BY p.created_at DESC
    ");

    $stmt->execute([$user->id]);
    $payments = $stmt->fetchAll();
}

echo json_encode($payments);
