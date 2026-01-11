<?php

require_once '../../../includes/functions.php';
requireAdmin();

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';

$db = (new Database())->getConnection();

$stmt = $db->query("
  SELECT c.*, p.title as parent_title 
  FROM categories c 
  LEFT JOIN categories p ON c.parent_id = p.id
  ORDER BY c.course_id, c.parent_id
");

echo json_encode($stmt->fetchAll());
