<?php

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
