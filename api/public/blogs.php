<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../includes/config.php';
require_once '../../includes/database.php';

$db = (new Database())->getConnection();

$stmt = $db->query("
  SELECT id, title, slug, excerpt, featured_image, published_at
  FROM blogs
  WHERE status='published'
  ORDER BY published_at DESC
");

echo json_encode($stmt->fetchAll());
