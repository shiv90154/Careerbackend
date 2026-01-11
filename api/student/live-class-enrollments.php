<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Check authentication
$user = Auth::getCurrentUser();
if (!$user || $user['role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Get live class enrollments for the student
    $stmt = $db->prepare("
        SELECT 
            lc.id,
            lc.title,
            lc.description,
            lc.scheduled_at,
            lc.duration_minutes,
            lc.meeting_link,
            lc.status,
            lc.created_at,
            lce.enrolled_at,
            lce.attendance_status,
            c.title as course_title,
            c.id as course_id
            
        FROM live_class_enrollments lce
        JOIN live_classes lc ON lc.id = lce.live_class_id
        LEFT JOIN courses c ON c.id = lc.course_id
        WHERE lce.user_id = ?
        ORDER BY lc.scheduled_at DESC
    ");
    
    $stmt->execute([$user['id']]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'enrollments' => $enrollments
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Failed to fetch live class enrollments: ' . $e->getMessage()
    ]);
}