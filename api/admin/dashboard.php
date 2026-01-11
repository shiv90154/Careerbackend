<?php

require_once '../../includes/functions.php';
requireAdmin();

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$db = (new Database())->getConnection();

$data = [
  "students" => $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
  "courses"  => $db->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
  "videos"   => $db->query("SELECT COUNT(*) FROM videos")->fetchColumn(),
  "tests"    => $db->query("SELECT COUNT(*) FROM tests")->fetchColumn()
];

echo json_encode($data);
