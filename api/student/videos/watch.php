<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../../includes/functions.php';
requireStudent();

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$video_id = $_GET['video_id'];

$db = (new Database())->getConnection();

$stmt = $db->prepare("
  SELECT v.id, v.title, v.youtube_id, v.description
  FROM videos v
  WHERE v.id=? AND v.is_active=1
");
$stmt->execute([$video_id]);

echo json_encode($stmt->fetch());
