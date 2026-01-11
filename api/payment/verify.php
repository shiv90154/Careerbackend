<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$orderId = $data['order_id'];
$paymentId = $data['razorpay_payment_id'];

$sql = "INSERT INTO payments 
(order_id, transaction_id, status) 
VALUES ('$orderId', '$paymentId', 'success')";

$conn->query($sql);

echo json_encode(["status" => "success"]);
