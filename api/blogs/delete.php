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

$id = $_GET['id'];
$db = (new Database())->getConnection();

$stmt = $db->prepare("DELETE FROM blogs WHERE id=?");
$stmt->execute([$id]);

echo json_encode(["message" => "Blog deleted"]);
