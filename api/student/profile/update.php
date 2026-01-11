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

$data = json_decode(file_get_contents("php://input"), true);
$student_id = $data['student_id'];

$db = (new Database())->getConnection();

$stmt = $db->prepare("
  UPDATE users 
  SET full_name=?, phone=?, address=?, city=?, state=?, pincode=?
  WHERE id=? AND role='student'
");

$stmt->execute([
  $data['full_name'],
  $data['phone'],
  $data['address'],
  $data['city'],
  $data['state'],
  $data['pincode'],
  $student_id
]);

echo json_encode(["message" => "Profile updated"]);
