<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once "../../config/db.php";

$sql = "SELECT * FROM payments ORDER BY created_at DESC";
$result = $conn->query($sql);

$payments = [];
while ($row = $result->fetch_assoc()) {
  $payments[] = $row;
}

echo json_encode($payments);
