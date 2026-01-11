<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../includes/database.php';
require_once '../../includes/functions.php';
requireAdmin();

$data = json_decode(file_get_contents("php://input"), true);
$db = (new Database())->getConnection();

$stmt = $db->prepare("
  UPDATE blogs SET
    title=?,
    slug=?,
    excerpt=?,
    content=?,
    status=?,
    seo_title=?,
    seo_description=?,
    seo_keywords=?,
    updated_at=NOW()
  WHERE id=?
");

$stmt->execute([
  $data['title'],
  $data['slug'],
  $data['excerpt'],
  $data['content'],
  $data['status'],
  $data['seo_title'],
  $data['seo_description'],
  $data['seo_keywords'],
  $data['id']
]);

echo json_encode(["message" => "Blog updated"]);
