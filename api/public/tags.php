<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }


require_once '../../includes/config.php';
require_once '../../includes/database.php';

$db = (new Database())->getConnection();

$sql = "
SELECT 
  LOWER(SUBSTRING_INDEX(title, ' ', 1)) AS tag
FROM courses
GROUP BY tag
ORDER BY COUNT(*) DESC
LIMIT 10
";

$stmt = $db->prepare($sql);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
