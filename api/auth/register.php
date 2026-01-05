<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data['full_name']) ||
    empty($data['email']) ||
    empty($data['password'])
) {
    http_response_code(400);
    echo json_encode(["message" => "Required fields missing"]);
    exit;
}

$db = (new Database())->getConnection();

// Check email exists
$check = $db->prepare("SELECT id FROM users WHERE email=?");
$check->execute([$data['email']]);

if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(["message" => "Email already registered"]);
    exit;
}

$passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

$stmt = $db->prepare("
    INSERT INTO users (role, full_name, email, password, phone, is_active)
    VALUES ('student', ?, ?, ?, ?, 1)
");

$stmt->execute([
    $data['full_name'],
    $data['email'],
    $passwordHash,
    $data['phone'] ?? null
]);

echo json_encode(["message" => "Registration successful"]);
