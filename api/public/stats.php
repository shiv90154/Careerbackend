<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';

try {
    $db = (new Database())->getConnection();

    // 🔹 Total students
    $students = $db->query("
        SELECT COUNT(*) AS total 
        FROM users 
        WHERE role = 'student'
    ")->fetch(PDO::FETCH_ASSOC);

    // 🔹 Total courses
    $courses = $db->query("
        SELECT COUNT(*) AS total 
        FROM courses 
        WHERE status = 'published'
    ")->fetch(PDO::FETCH_ASSOC);

    // 🔹 Total faculty
    $faculty = $db->query("
        SELECT COUNT(*) AS total 
        FROM users 
        WHERE role = 'faculty'
    ")->fetch(PDO::FETCH_ASSOC);

    // 🔹 Total enrollments
    $enrollments = $db->query("
        SELECT COUNT(*) AS total 
        FROM enrollments
    ")->fetch(PDO::FETCH_ASSOC);

    // 🔹 Success rate (example logic)
    $success_rate = $enrollments['total'] > 0 ? "85%" : "0%";

    echo json_encode([
        "total_students"   => $students['total'] . "+",
        "total_courses"    => $courses['total'] . "+",
        "faculty_count"    => $faculty['total'] . "+",
        "years_experience" => "8+",
        "success_rate"     => $success_rate,
        "placement_rate"   => "92%"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => "Failed to load stats"
    ]);
}
