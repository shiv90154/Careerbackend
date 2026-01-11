<?php

$data = json_decode(file_get_contents("php://input"), true);
$paymentId = $data['payment_id'];

// Razorpay refund API call here

echo json_encode(["status" => "refunded"]);
