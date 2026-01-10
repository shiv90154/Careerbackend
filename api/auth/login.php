<?php

// ===== CORS HEADERS =====
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// ===== HANDLE PREFLIGHT =====
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===== YOUR EXISTING CODE =====
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
    "exp"   => time() + (60 * 60 * 24)
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
