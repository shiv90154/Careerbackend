<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

try {
    $db = (new Database())->getConnection();

    $sql = "
        SELECT 
            c.id,
            c.title AS name,
            c.slug,
            COUNT(co.id) AS course_count
        FROM categories c
        LEFT JOIN courses co 
            ON co.category_slug = c.slug
        GROUP BY c.id, c.title, c.slug
        ORDER BY c.title ASC
    ";

    $stmt = $db->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
