<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

try {
    $db = (new Database())->getConnection();

    $sql = "
        SELECT 
            bc.id,
            bc.name,
            bc.slug,
            COUNT(b.id) AS blog_count
        FROM blog_categories bc
        LEFT JOIN blogs b 
            ON b.category_id = bc.id 
            AND b.status = 'published'
        GROUP BY bc.id, bc.name, bc.slug
        ORDER BY bc.name ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
