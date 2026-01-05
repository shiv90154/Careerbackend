<?php
require_once '../includes/config.php';

echo json_encode([
  "order_id" => "ORDER_" . time(),
  "amount" => 999,
  "currency" => "INR"
]);
