<?php

require_once '../../../includes/functions.php';
requireStudent();

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$video_id = $_GET['video_id'];

$db = (new Database())->getConnection();

$stmt = $db->prepare("
  SELECT v.id, v.title, v.youtube_id, v.description
  FROM videos v
  WHERE v.id=? AND v.is_active=1
");
$stmt->execute([$video_id]);

echo json_encode($stmt->fetch());
