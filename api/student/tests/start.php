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

$test_id = $_GET['test_id'];
$student_id = $_GET['student_id'];

$db = (new Database())->getConnection();

$stmt = $db->prepare("
  INSERT INTO test_attempts (student_id, test_id, start_time)
  VALUES (?, ?, NOW())
");
$stmt->execute([$student_id, $test_id]);

echo json_encode([
  "attempt_id" => $db->lastInsertId(),
  "message" => "Test started"
]);
