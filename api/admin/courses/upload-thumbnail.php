<?php
// CORS
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

require_once '../../../includes/functions.php';
requireAdmin();

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';

// 检查文件上传
if (!isset($_FILES['thumbnail']) || $_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["message" => "No file uploaded"]);
    exit;
}

// 验证文件类型
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($_FILES['thumbnail']['type'], $allowedTypes)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid file type. Only JPG, PNG, and WebP are allowed"]);
    exit;
}

// 验证文件大小（最大5MB）
if ($_FILES['thumbnail']['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(["message" => "File too large. Maximum size is 5MB"]);
    exit;
}

// 生成唯一文件名
$extension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
$filename = uniqid('course_') . '.' . $extension;
$uploadPath = '../../../uploads/courses/' . $filename;

// 创建上传目录（如果不存在）
if (!is_dir('../../../uploads/courses')) {
    mkdir('../../../uploads/courses', 0777, true);
}

// 移动上传的文件
if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $uploadPath)) {
    // 更新数据库中的缩略图路径
    $db = (new Database())->getConnection();
    $courseId = $_POST['course_id'];
    
    if ($courseId !== 'new') {
        $stmt = $db->prepare("UPDATE courses SET thumbnail = ? WHERE id = ?");
        $stmt->execute([$filename, $courseId]);
    }
    
    echo json_encode([
        "success" => true,
        "message" => "Thumbnail uploaded successfully",
        "filename" => $filename
    ]);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Failed to upload file"]);
}