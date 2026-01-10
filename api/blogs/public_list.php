<?php
require_once '../../includes/database.php';

$db = (new Database())->getConnection();

$stmt = $db->query("
  SELECT title, slug, excerpt, published_at 
  FROM blogs 
  WHERE status='published'
  ORDER BY published_at DESC
");

echo json_encode($stmt->fetchAll());
