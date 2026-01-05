<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/jwt.php';

$data = json_decode(file_get_contents("php://input"), true);

$email    = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(["message" => "Email and password required"]);
    exit;
}

$db = (new Database())->getConnection();

$stmt = $db->prepare("SELECT * FROM users WHERE email=? AND is_active=1 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(["message" => "Invalid credentials"]);
    exit;
}

$token = JWT::encode([
    "id"    => $user['id'],
    "role"  => $user['role'],
    "email" => $user['email'],
    "exp"   => time() + (60 * 60 * 24) // 24 hours
], JWT_SECRET);

echo json_encode([
    "token" => $token,
    "user"  => [
        "id"    => $user['id'],
        "name"  => $user['full_name'],
        "email" => $user['email'],
        "role"  => $user['role']
    ]
]);
