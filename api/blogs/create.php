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
  INSERT INTO blogs 
  (title, slug, excerpt, content, author_id, status, seo_title, seo_description, seo_keywords, published_at)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");

$stmt->execute([
  $data['title'],
  $data['slug'],
  $data['excerpt'] ?? null,
  $data['content'],
  $_SESSION['user_id'],
  $data['status'] ?? 'draft',
  $data['seo_title'] ?? null,
  $data['seo_description'] ?? null,
  $data['seo_keywords'] ?? null
]);

echo json_encode(["message" => "Blog created"]);
