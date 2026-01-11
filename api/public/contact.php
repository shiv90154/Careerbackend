<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../includes/config.php';
require_once '../../includes/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data['name'] || !$data['email'] || !$data['message']) {
  http_response_code(400);
  echo json_encode(["message" => "All fields required"]);
  exit;
}

$db = (new Database())->getConnection();

$stmt = $db->prepare("
  INSERT INTO notifications (user_id, title, message, type)
  VALUES (0, 'Contact Enquiry', ?, 'system')
");

$stmt->execute([
  $data['name']." | ".$data['email']." | ".$data['message']
]);

echo json_encode(["message" => "Message sent"]);
