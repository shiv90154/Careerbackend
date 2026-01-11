<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../../includes/functions.php';
requireAdmin();

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';

$db = (new Database())->getConnection();

/*
  Ye query:
  - tests table se data laati hai
  - categories se category title
  - courses se course title
  - sab kuch dynamic
*/
$stmt = $db->query("
    SELECT 
        t.id,
        t.title,
        t.duration_minutes,
        t.total_questions,
        t.passing_score,
        t.max_attempts,
        t.is_active,
        t.start_date,
        t.end_date,
        c.title   AS category_title,
        co.title  AS course_title
    FROM tests t
    JOIN categories c ON c.id = t.category_id
    JOIN courses co   ON co.id = c.course_id
    ORDER BY t.created_at DESC
");

$tests = $stmt->fetchAll();

echo json_encode($tests);
