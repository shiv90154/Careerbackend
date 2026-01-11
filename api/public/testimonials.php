<?php
// =======================
// =======================
// Preflight request handle
// =======================
// DB CONNECTION
// =======================
require_once '../../includes/config.php';
require_once '../../includes/database.php';

try {
    $db = (new Database())->getConnection();

    $stmt = $db->prepare("
        SELECT 
            id,
            student_name,
            student_initials,
            course_name,
            message,
            rating
        FROM testimonials
        WHERE status = 'published'
        ORDER BY created_at DESC
        LIMIT 10
    ");

    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($testimonials);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => "Failed to load testimonials"
    ]);
}
