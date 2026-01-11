<?php

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
