<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }


require_once '../../../includes/functions.php';
requireAdmin();

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data['course_code']) ||
    empty($data['title']) ||
    empty($data['slug'])
) {
    http_response_code(422);
    echo json_encode(["message" => "Required fields missing"]);
    exit;
}

$db = (new Database())->getConnection();

$stmt = $db->prepare("
  INSERT INTO courses 
  (course_code, title, slug, price, short_description) 
  VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
  $data['course_code'],
  $data['title'],
  $data['slug'],
  $data['price'] ?? 0,
  $data['short_description'] ?? ''
]);

echo json_encode([
    "success" => true,
    "message" => "Course created"
]);

