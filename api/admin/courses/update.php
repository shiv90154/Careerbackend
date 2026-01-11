<?php
require_once '../../../includes/functions.php';
requireAdmin();

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data['id']) ||
    empty($data['title']) ||
    empty($data['slug']) ||
    !isset($data['price'])
) {
    http_response_code(400);
    echo json_encode(["message" => "Required fields missing"]);
    exit;
}

$db = (new Database())->getConnection();

$stmt = $db->prepare("
    UPDATE courses SET
        course_code       = :course_code,
        title             = :title,
        slug              = :slug,
        description       = :description,
        short_description = :short_description,
        price             = :price,
        discount_price    = :discount_price,
        duration_days     = :duration_days,
        total_hours       = :total_hours,
        level             = :level,
        is_featured       = :is_featured,
        is_active         = :is_active,
        updated_at        = NOW()
    WHERE id = :id
");

$stmt->execute([
    ':course_code'       => $data['course_code'] ?? null,
    ':title'             => $data['title'],
    ':slug'              => $data['slug'],
    ':description'       => $data['description'] ?? null,
    ':short_description' => $data['short_description'] ?? null,
    ':price'             => $data['price'],
    ':discount_price'    => $data['discount_price'] ?? null,
    ':duration_days'     => $data['duration_days'] ?? null,
    ':total_hours'       => $data['total_hours'] ?? null,
    ':level'             => $data['level'] ?? 'beginner',
    ':is_featured'       => $data['is_featured'] ?? 0,
    ':is_active'         => $data['is_active'] ?? 1,
    ':id'                => $data['id']
]);

echo json_encode([
    "success" => true,
    "message" => "Course updated successfully"
]);
