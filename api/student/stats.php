<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

$user = Auth::requireAuth();
$db = (new Database())->getConnection();

try {
    // Get enrollment stats
    $enrollmentStmt = $db->prepare("
        SELECT 
            COUNT(*) as enrolled_courses,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_courses
        FROM enrollments 
        WHERE user_id = ?
    ");
    $enrollmentStmt->execute([$user['id']]);
    $enrollmentStats = $enrollmentStmt->fetch();
    
    // Get certificates count
    $certificateStmt = $db->prepare("
        SELECT COUNT(*) as certificates 
        FROM certificates 
        WHERE user_id = ?
    ");
    $certificateStmt->execute([$user['id']]);
    $certificateCount = $certificateStmt->fetch()['certificates'];
    
    // Get total study time (sum of video durations for completed lessons)
    $studyTimeStmt = $db->prepare("
        SELECT COALESCE(SUM(l.video_duration), 0) as total_study_time
        FROM lesson_progress lp
        LEFT JOIN lessons l ON lp.lesson_id = l.id
        WHERE lp.user_id = ? AND lp.is_completed = 1
    ");
    $studyTimeStmt->execute([$user['id']]);
    $totalStudyTime = $studyTimeStmt->fetch()['total_study_time'];
    
    // Get recent activity
    $activityStmt = $db->prepare("
        SELECT 
            'lesson_completed' as type,
            l.title as title,
            c.title as course_title,
            lp.completion_date as date
        FROM lesson_progress lp
        LEFT JOIN lessons l ON lp.lesson_id = l.id
        LEFT JOIN courses c ON l.course_id = c.id
        WHERE lp.user_id = ? AND lp.is_completed = 1
        ORDER BY lp.completion_date DESC
        LIMIT 5
    ");
    $activityStmt->execute([$user['id']]);
    $recentActivity = $activityStmt->fetchAll();
    
    echo json_encode([
        'stats' => [
            'enrolled_courses' => (int)$enrollmentStats['enrolled_courses'],
            'completed_courses' => (int)$enrollmentStats['completed_courses'],
            'certificates' => (int)$certificateCount,
            'total_study_time' => (int)$totalStudyTime
        ],
        'recent_activity' => $recentActivity
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error']);
}
?>