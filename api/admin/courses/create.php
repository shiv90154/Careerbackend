<?php
require_once '../../../includes/functions.php';
requireAdmin();

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';

$data = json_decode(file_get_contents("php://input"), true);

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
  $data['price'],
  $data['short_description']
]);

echo json_encode(["message" => "Course created"]);
