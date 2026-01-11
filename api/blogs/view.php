<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../includes/database.php';

$slug = $_GET['slug'];
$db = (new Database())->getConnection();

$stmt = $db->prepare("
  SELECT * FROM blogs 
  WHERE slug=? AND status='published'
  LIMIT 1
");

$stmt->execute([$slug]);
$blog = $stmt->fetch();

if (!$blog) {
  http_response_code(404);
  echo json_encode(["message" => "Blog not found"]);
  exit;
}

$db->prepare("UPDATE blogs SET view_count=view_count+1 WHERE id=?")
   ->execute([$blog['id']]);

echo json_encode($blog);
