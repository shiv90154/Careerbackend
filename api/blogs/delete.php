<?php

require_once '../../includes/database.php';
require_once '../../includes/functions.php';
requireAdmin();

$id = $_GET['id'];
$db = (new Database())->getConnection();

$stmt = $db->prepare("DELETE FROM blogs WHERE id=?");
$stmt->execute([$id]);

echo json_encode(["message" => "Blog deleted"]);
