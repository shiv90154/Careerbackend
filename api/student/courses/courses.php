<?php

require_once '../../../includes/functions.php';
requireStudent();

require_once '../../includes/config.php';
require_once '../../includes/database.php';

/**
 * ✅ Get logged-in student id securely
 * (adjust key if your session name is different)
 */
$student_id = $_SESSION['student_id'] ?? null;

if (!$student_id) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$db = (new Database())->getConnection();

$stmt = $db->prepare("
    SELECT 
        c.id,
        c.title,
        c.slug,
        c.thumbnail,
        c.duration_days,
        c.level,
        e.progress_percentage,
        e.enrolled_at
    FROM enrollments e
    INNER JOIN courses c ON c.id = e.course_id
    WHERE e.student_id = ?
    ORDER BY e.enrolled_at DESC
");

$stmt->execute([$student_id]);

$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * 🛡️ Always return array
 */
echo json_encode($courses ?: []);
exit;
