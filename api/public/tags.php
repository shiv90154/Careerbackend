<?php
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
