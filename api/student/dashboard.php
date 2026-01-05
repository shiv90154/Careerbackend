<?php
require_once '../../includes/functions.php';
requireStudent();

require_once '../includes/config.php';
require_once '../includes/database.php';

// ⚠️ Abhi student_id frontend / JWT se aayega
$student_id = $_GET['student_id'] ?? 0;

if (!$student_id) {
    http_response_code(400);
    echo json_encode(["message" => "Student ID required"]);
    exit;
}

$db = (new Database())->getConnection();

/*
|--------------------------------------------------------------------------
| 1. BASIC STATS
|--------------------------------------------------------------------------
*/
$stats = [];

$stats['enrolled_courses'] = $db->prepare("
    SELECT COUNT(*) FROM enrollments 
    WHERE student_id = ?
");
$stats['enrolled_courses']->execute([$student_id]);
$stats['enrolled_courses'] = $stats['enrolled_courses']->fetchColumn();

$stats['completed_tests'] = $db->prepare("
    SELECT COUNT(*) FROM test_attempts 
    WHERE student_id = ? AND status = 'completed'
");
$stats['completed_tests']->execute([$student_id]);
$stats['completed_tests'] = $stats['completed_tests']->fetchColumn();

$stats['videos_watched'] = $db->prepare("
    SELECT COUNT(*) FROM video_progress 
    WHERE student_id = ? AND completed = 1
");
$stats['videos_watched']->execute([$student_id]);
$stats['videos_watched'] = $stats['videos_watched']->fetchColumn();

/*
|--------------------------------------------------------------------------
| 2. ENROLLED COURSES + PROGRESS
|--------------------------------------------------------------------------
*/
$coursesStmt = $db->prepare("
    SELECT 
        c.id,
        c.title,
        c.slug,
        c.thumbnail,
        e.progress_percentage,
        e.last_accessed_at
    FROM enrollments e
    JOIN courses c ON c.id = e.course_id
    WHERE e.student_id = ?
    ORDER BY e.last_accessed_at DESC
");
$coursesStmt->execute([$student_id]);
$courses = $coursesStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| 3. RECENT TEST ATTEMPTS
|--------------------------------------------------------------------------
*/
$testsStmt = $db->prepare("
    SELECT 
        t.title,
        ta.score,
        ta.percentage,
        ta.status,
        ta.created_at
    FROM test_attempts ta
    JOIN tests t ON t.id = ta.test_id
    WHERE ta.student_id = ?
    ORDER BY ta.created_at DESC
    LIMIT 5
");
$testsStmt->execute([$student_id]);
$recent_tests = $testsStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| FINAL RESPONSE
|--------------------------------------------------------------------------
*/
echo json_encode([
    "stats" => $stats,
    "courses" => $courses,
    "recent_tests" => $recent_tests
]);
