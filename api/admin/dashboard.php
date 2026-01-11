<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../includes/functions.php';
requireAdmin();

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$db = (new Database())->getConnection();

$data = [
  "students" => $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
  "courses"  => $db->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
  "videos"   => $db->query("SELECT COUNT(*) FROM videos")->fetchColumn(),
  "tests"    => $db->query("SELECT COUNT(*) FROM tests")->fetchColumn()
];

echo json_encode($data);
