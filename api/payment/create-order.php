<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once "../../config/razorpay.php";

$orderData = [
  'amount' => 999 * 100,
  'currency' => 'INR',
  'receipt' => 'ORDER_' . time()
];

$order = $razorpay->order->create($orderData);

echo json_encode([
  "order_id" => $order['id'],
  "amount" => 999,
  "currency" => "INR"
]);
