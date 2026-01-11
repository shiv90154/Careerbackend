<?php

require_once "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$orderId = $data['order_id'];
$paymentId = $data['razorpay_payment_id'];

$sql = "INSERT INTO payments 
(order_id, transaction_id, status) 
VALUES ('$orderId', '$paymentId', 'success')";

$conn->query($sql);

echo json_encode(["status" => "success"]);
