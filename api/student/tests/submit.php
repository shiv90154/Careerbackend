<?php

require_once '../../../includes/functions.php';
requireStudent();

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$db = (new Database())->getConnection();

$total = 0;

foreach ($data['answers'] as $ans) {
  $q = $db->prepare("SELECT correct_answer, marks FROM questions WHERE id=?");
  $q->execute([$ans['question_id']]);
  $row = $q->fetch();

  $is_correct = $row['correct_answer'] === $ans['selected'];
  $marks = $is_correct ? $row['marks'] : 0;
  $total += $marks;

  $db->prepare("
    INSERT INTO test_attempt_answers
    (attempt_id, question_id, selected_answer, is_correct, marks_obtained)
    VALUES (?, ?, ?, ?, ?)
  ")->execute([
    $data['attempt_id'],
    $ans['question_id'],
    $ans['selected'],
    $is_correct,
    $marks
  ]);
}

$db->prepare("
  UPDATE test_attempts
  SET end_time=NOW(), score=?, status='completed'
  WHERE id=?
")->execute([$total, $data['attempt_id']]);

echo json_encode(["score" => $total]);
