<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$courseId = $_GET['id'] ?? '';

if (!$courseId) {
    http_response_code(400);
    echo json_encode(['message' => 'Course ID required']);
    exit;
}

$db = (new Database())->getConnection();

// Get course details
$stmt = $db->prepare("
    SELECT 
        c.*,
        cat.name as category_name,
        u.full_name as instructor_name,
        u.bio as instructor_bio,
        u.profile_image as instructor_image
    FROM courses c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN users u ON c.instructor_id = u.id
    WHERE c.id = ? AND c.status = 'published'
");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    echo json_encode(['message' => 'Course not found']);
    exit;
}

// Get course sections and lessons
$sectionsStmt = $db->prepare("
    SELECT 
        cs.*,
        COUNT(l.id) as lesson_count,
        SUM(l.video_duration) as total_duration
    FROM course_sections cs
    LEFT JOIN lessons l ON cs.id = l.section_id
    WHERE cs.course_id = ?
    GROUP BY cs.id
    ORDER BY cs.sort_order
");
$sectionsStmt->execute([$courseId]);
$sections = $sectionsStmt->fetchAll();

foreach ($sections as &$section) {
    $lessonsStmt = $db->prepare("
        SELECT id, title, lesson_type, video_duration, is_preview, sort_order
        FROM lessons
        WHERE section_id = ?
        ORDER BY sort_order
    ");
    $lessonsStmt->execute([$section['id']]);
    $section['lessons'] = $lessonsStmt->fetchAll();
}

// Get course reviews
$reviewsStmt = $db->prepare("
    SELECT 
        cr.*,
        u.full_name as user_name,
        u.profile_image
    FROM course_reviews cr
    LEFT JOIN users u ON cr.user_id = u.id
    WHERE cr.course_id = ? AND cr.is_approved = 1
    ORDER BY cr.created_at DESC
    LIMIT 10
");
$reviewsStmt->execute([$courseId]);
$reviews = $reviewsStmt->fetchAll();

// Get related courses
$relatedStmt = $db->prepare("
    SELECT id, title, thumbnail, price, discount_price, rating_avg, enrollment_count
    FROM courses
    WHERE category_id = ? AND id != ? AND status = 'published'
    ORDER BY enrollment_count DESC
    LIMIT 4
");
$relatedStmt->execute([$course['category_id'], $courseId]);
$relatedCourses = $relatedStmt->fetchAll();

echo json_encode([
    'course' => $course,
    'sections' => $sections,
    'reviews' => $reviews,
    'related_courses' => $relatedCourses
]);
?>