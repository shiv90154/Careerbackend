<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/jwt.php';

/*
|--------------------------------------------------------------------------
| JSON Response Helper
|--------------------------------------------------------------------------
*/
function jsonResponse($data = [], int $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Bearer Token from Header
|--------------------------------------------------------------------------
*/
function getBearerToken()
{
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return null;
    }
    return str_replace('Bearer ', '', $headers['Authorization']);
}

/*
|--------------------------------------------------------------------------
| Verify JWT Token
|--------------------------------------------------------------------------
*/
function verifyToken()
{
    $token = getBearerToken();
    if (!$token) {
        jsonResponse(["message" => "Authorization token missing"], 401);
    }

    try {
        return JWT::decode($token, JWT_SECRET);
    } catch (Exception $e) {
        jsonResponse(["message" => "Invalid or expired token"], 401);
    }
}

/*
|--------------------------------------------------------------------------
| Require Logged-in User
|--------------------------------------------------------------------------
*/
function requireAuth()
{
    return verifyToken();
}

/*
|--------------------------------------------------------------------------
| Require Admin Role
|--------------------------------------------------------------------------
*/
function requireAdmin()
{
    $user = verifyToken();
    if ($user->role !== 'admin') {
        jsonResponse(["message" => "Admin access required"], 403);
    }
    return $user;
}

/*
|--------------------------------------------------------------------------
| Require Student Role
|--------------------------------------------------------------------------
*/
function requireStudent()
{
    $user = verifyToken();
    if ($user->role !== 'student') {
        jsonResponse(["message" => "Student access required"], 403);
    }
    return $user;
}

/*
|--------------------------------------------------------------------------
| Create URL-friendly Slug
|--------------------------------------------------------------------------
*/
function makeSlug(string $text)
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/*
|--------------------------------------------------------------------------
| Pagination Helper
|--------------------------------------------------------------------------
*/
function paginate($page = 1, $limit = 10)
{
    $page = max(1, (int)$page);
    $limit = max(1, (int)$limit);
    $offset = ($page - 1) * $limit;
    return [$limit, $offset];
}

/*
|--------------------------------------------------------------------------
| Check Enrollment
|--------------------------------------------------------------------------
*/
function isEnrolled($student_id, $course_id)
{
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("
        SELECT id FROM enrollments
        WHERE student_id = ? AND course_id = ? AND enrollment_status = 'active'
    ");
    $stmt->execute([$student_id, $course_id]);
    return $stmt->fetch() ? true : false;
}

/*
|--------------------------------------------------------------------------
| Update Course Progress
|--------------------------------------------------------------------------
*/
function updateCourseProgress($enrollment_id)
{
    $db = (new Database())->getConnection();

    $totalVideos = $db->prepare("
        SELECT COUNT(*) FROM videos v
        JOIN categories c ON c.id = v.category_id
        WHERE c.course_id = (
            SELECT course_id FROM enrollments WHERE id = ?
        )
    ");
    $totalVideos->execute([$enrollment_id]);
    $total = (int)$totalVideos->fetchColumn();

    if ($total === 0) return;

    $completedVideos = $db->prepare("
        SELECT COUNT(*) FROM video_progress
        WHERE enrollment_id = ? AND completed = 1
    ");
    $completedVideos->execute([$enrollment_id]);
    $completed = (int)$completedVideos->fetchColumn();

    $progress = round(($completed / $total) * 100);

    $update = $db->prepare("
        UPDATE enrollments SET progress_percentage = ?
        WHERE id = ?
    ");
    $update->execute([$progress, $enrollment_id]);
}

/*
|--------------------------------------------------------------------------
| Safe File Upload Helper
|--------------------------------------------------------------------------
*/
function uploadFile($file, $folder)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        jsonResponse(["message" => "File size too large"], 400);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        jsonResponse(["message" => "Invalid file type"], 400);
    }

    $filename = uniqid() . '.' . $ext;
    $path = "uploads/$folder/" . $filename;

    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/../' . $path)) {
        jsonResponse(["message" => "File upload failed"], 500);
    }

    return $path;
}
?>
