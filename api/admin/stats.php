<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

$user = Auth::requireRole('admin');
$db = (new Database())->getConnection();

try {
    // Get total students
    $studentsStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $studentsStmt->execute();
    $totalStudents = $studentsStmt->fetch()['count'];

    // Get total courses
    $coursesStmt = $db->prepare("SELECT COUNT(*) as count FROM courses");
    $coursesStmt->execute();
    $totalCourses = $coursesStmt->fetch()['count'];

    // Get active courses
    $activeCoursesStmt = $db->prepare("SELECT COUNT(*) as count FROM courses WHERE status = 'published'");
    $activeCoursesStmt->execute();
    $activeCourses = $activeCoursesStmt->fetch()['count'];

    // Get pending courses
    $pendingCoursesStmt = $db->prepare("SELECT COUNT(*) as count FROM courses WHERE status = 'draft'");
    $pendingCoursesStmt->execute();
    $pendingCourses = $pendingCoursesStmt->fetch()['count'];

    // Get total enrollments
    $enrollmentsStmt = $db->prepare("SELECT COUNT(*) as count FROM enrollments");
    $enrollmentsStmt->execute();
    $totalEnrollments = $enrollmentsStmt->fetch()['count'];

    // Get total revenue
    $revenueStmt = $db->prepare("
        SELECT COALESCE(SUM(final_amount), 0) as total 
        FROM payments 
        WHERE status = 'completed'
    ");
    $revenueStmt->execute();
    $totalRevenue = $revenueStmt->fetch()['total'];

    // Get monthly stats
    $monthlyStmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN u.id END) as new_students_month,
            COUNT(DISTINCT CASE WHEN e.enrollment_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN e.id END) as new_enrollments_month,
            COALESCE(SUM(CASE WHEN p.payment_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN p.final_amount END), 0) as revenue_month
        FROM users u
        LEFT JOIN enrollments e ON 1=1
        LEFT JOIN payments p ON 1=1
        WHERE u.role = 'student'
    ");
    $monthlyStmt->execute();
    $monthlyStats = $monthlyStmt->fetch();

    echo json_encode([
        'stats' => [
            'total_students' => (int)$totalStudents,
            'total_courses' => (int)$totalCourses,
            'active_courses' => (int)$activeCourses,
            'pending_courses' => (int)$pendingCourses,
            'total_enrollments' => (int)$totalEnrollments,
            'total_revenue' => (float)$totalRevenue,
            'new_students_month' => (int)$monthlyStats['new_students_month'],
            'new_enrollments_month' => (int)$monthlyStats['new_enrollments_month'],
            'revenue_month' => (float)$monthlyStats['revenue_month']
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
?>