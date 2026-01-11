<?php

require_once "../../config/db.php";

$sql = "SELECT * FROM payments ORDER BY created_at DESC";
$result = $conn->query($sql);

$payments = [];
while ($row = $result->fetch_assoc()) {
  $payments[] = $row;
}

echo json_encode($payments);
