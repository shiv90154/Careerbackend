<?php
require_once '../../includes/config.php';
require_once '../../includes/jwt.php';

$headers = getallheaders();

if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["message" => "Token missing"]);
    exit;
}

$token = str_replace("Bearer ", "", $headers['Authorization']);

try {
    $decoded = JWT::decode($token, JWT_SECRET);
    echo json_encode([
        "valid" => true,
        "user"  => $decoded
    ]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["valid" => false, "message" => "Invalid token"]);
}
