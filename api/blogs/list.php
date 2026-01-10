<?php
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
requireAdmin();

$db = (new Database())->getConnection();

$stmt = $db->query("
  SELECT id, title, slug, status, created_at 
  FROM blogs ORDER BY created_at DESC
");

echo json_encode($stmt->fetchAll());
