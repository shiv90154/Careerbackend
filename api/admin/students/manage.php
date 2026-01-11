<?php

require_once '../../../includes/functions.php';
requireAdmin();

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';

$db = (new Database())->getConnection();

$stmt = $db->query("
  SELECT id, full_name, email, phone, is_active 
  FROM users WHERE role='student'
");

echo json_encode($stmt->fetchAll());
