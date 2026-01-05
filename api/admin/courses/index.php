<?php
require_once '../../../includes/functions.php';
requireAdmin();

require_once '../../../includes/config.php';
require_once '../../../includes/database.php';

$db = (new Database())->getConnection();

$stmt = $db->query("SELECT * FROM courses ORDER BY created_at DESC");
echo json_encode($stmt->fetchAll());
