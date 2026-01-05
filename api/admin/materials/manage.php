<?php
require_once '../../../includes/functions.php';
requireAdmin();

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';

$db = (new Database())->getConnection();

$stmt = $db->query("
  SELECT m.id, m.title, m.file_name, c.title as category
  FROM study_materials m
  JOIN categories c ON m.category_id = c.id
");

echo json_encode($stmt->fetchAll());
