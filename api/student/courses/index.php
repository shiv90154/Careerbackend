<?php
require_once '../../../includes/functions.php';
requireStudent();

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$student_id = $_GET['student_id']; // JWT se aayega later

$db = (new Database())->getConnection();

$stmt = $db->prepare("
  SELECT c.id, c.title, c.slug, e.progress_percentage
  FROM enrollments e
  JOIN courses c ON c.id = e.course_id
  WHERE e.student_id = ?
");
$stmt->execute([$student_id]);

echo json_encode($stmt->fetchAll());
