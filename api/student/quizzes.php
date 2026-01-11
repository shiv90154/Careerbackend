<?php
// CORS Headers
header("Access-Control-Allow-Origin: " . ($_SERVER["HTTP_ORIGIN"] ?? "http://localhost:5173"));
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { http_response_code(200); exit(); }



require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

$user = Auth::requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db = (new Database())->getConnection();

switch ($method) {
    case 'GET':
        if (isset($_GET['attempt_id'])) {
            // Get quiz attempt results
            $stmt = $db->prepare("
                SELECT 
                    qa.*,
                    q.title as quiz_title,
                    q.passing_score,
                    q.show_results
                FROM quiz_attempts qa
                LEFT JOIN quizzes q ON qa.quiz_id = q.id
                WHERE qa.id = ? AND qa.user_id = ?
            ");
            $stmt->execute([$_GET['attempt_id'], $user['id']]);
            $attempt = $stmt->fetch();
            
            if (!$attempt) {
                http_response_code(404);
                echo json_encode(['message' => 'Attempt not found']);
                exit;
            }
            
            // Get answers if results should be shown
            if ($attempt['show_results']) {
                $answersStmt = $db->prepare("
                    SELECT 
                        qans.*,
                        qq.question,
                        qq.explanation,
                        qo.option_text,
                        qo.is_correct as option_is_correct
                    FROM quiz_answers qans
                    LEFT JOIN quiz_questions qq ON qans.question_id = qq.id
                    LEFT JOIN quiz_options qo ON qans.option_id = qo.id
                    WHERE qans.attempt_id = ?
                    ORDER BY qq.sort_order
                ");
                $answersStmt->execute([$_GET['attempt_id']]);
                $attempt['answers'] = $answersStmt->fetchAll();
            }
            
            echo json_encode(['attempt' => $attempt]);
            
        } elseif (isset($_GET['quiz_id'])) {
            // Get quiz for taking
            $quizId = $_GET['quiz_id'];
            
            // Check if user is enrolled in the course
            $enrollStmt = $db->prepare("
                SELECT e.id 
                FROM enrollments e
                LEFT JOIN quizzes q ON e.course_id = q.course_id
                WHERE e.user_id = ? AND q.id = ? AND e.status = 'active'
            ");
            $enrollStmt->execute([$user['id'], $quizId]);
            
            if (!$enrollStmt->fetch()) {
                http_response_code(403);
                echo json_encode(['message' => 'Not enrolled in this course']);
                exit;
            }
            
            // Get quiz details
            $stmt = $db->prepare("
                SELECT 
                    q.*,
                    c.title as course_title
                FROM quizzes q
                LEFT JOIN courses c ON q.course_id = c.id
                WHERE q.id = ? AND q.is_active = 1
            ");
            $stmt->execute([$quizId]);
            $quiz = $stmt->fetch();
            
            if (!$quiz) {
                http_response_code(404);
                echo json_encode(['message' => 'Quiz not found']);
                exit;
            }
            
            // Check attempt limit
            $attemptStmt = $db->prepare("
                SELECT COUNT(*) as attempt_count 
                FROM quiz_attempts 
                WHERE user_id = ? AND quiz_id = ?
            ");
            $attemptStmt->execute([$user['id'], $quizId]);
            $attemptCount = $attemptStmt->fetch()['attempt_count'];
            
            if ($quiz['max_attempts'] > 0 && $attemptCount >= $quiz['max_attempts']) {
                http_response_code(403);
                echo json_encode(['message' => 'Maximum attempts reached']);
                exit;
            }
            
            // Get questions
            $questionsStmt = $db->prepare("
                SELECT id, question, question_type, points
                FROM quiz_questions 
                WHERE quiz_id = ? 
                ORDER BY " . ($quiz['randomize_questions'] ? 'RAND()' : 'sort_order')
            );
            $questionsStmt->execute([$quizId]);
            $questions = $questionsStmt->fetchAll();
            
            // Get options for each question
            foreach ($questions as &$question) {
                $optionsStmt = $db->prepare("
                    SELECT id, option_text
                    FROM quiz_options 
                    WHERE question_id = ? 
                    ORDER BY " . ($quiz['randomize_questions'] ? 'RAND()' : 'sort_order')
                );
                $optionsStmt->execute([$question['id']]);
                $question['options'] = $optionsStmt->fetchAll();
            }
            
            $quiz['questions'] = $questions;
            $quiz['attempt_number'] = $attemptCount + 1;
            
            echo json_encode(['quiz' => $quiz]);
            
        } else {
            // Get user's quiz attempts
            $courseId = $_GET['course_id'] ?? '';
            $where = $courseId ? "AND q.course_id = ?" : "";
            $params = [$user['id']];
            if ($courseId) $params[] = $courseId;
            
            $stmt = $db->prepare("
                SELECT 
                    qa.*,
                    q.title as quiz_title,
                    q.passing_score,
                    c.title as course_title
                FROM quiz_attempts qa
                LEFT JOIN quizzes q ON qa.quiz_id = q.id
                LEFT JOIN courses c ON q.course_id = c.id
                WHERE qa.user_id = ? $where
                ORDER BY qa.started_at DESC
            ");
            $stmt->execute($params);
            $attempts = $stmt->fetchAll();
            
            echo json_encode(['attempts' => $attempts]);
        }
        break;
        
    case 'POST':
        if (isset($_POST['action']) && $_POST['action'] === 'start') {
            // Start quiz attempt
            $data = json_decode(file_get_contents("php://input"), true);
            $quizId = $data['quiz_id'] ?? '';
            
            if (!$quizId) {
                http_response_code(400);
                echo json_encode(['message' => 'Quiz ID required']);
                exit;
            }
            
            try {
                // Get current attempt number
                $attemptStmt = $db->prepare("
                    SELECT COUNT(*) as attempt_count 
                    FROM quiz_attempts 
                    WHERE user_id = ? AND quiz_id = ?
                ");
                $attemptStmt->execute([$user['id'], $quizId]);
                $attemptNumber = $attemptStmt->fetch()['attempt_count'] + 1;
                
                $stmt = $db->prepare("
                    INSERT INTO quiz_attempts (user_id, quiz_id, attempt_number, status)
                    VALUES (?, ?, ?, 'in_progress')
                ");
                $stmt->execute([$user['id'], $quizId, $attemptNumber]);
                
                $attemptId = $db->lastInsertId();
                
                echo json_encode([
                    'message' => 'Quiz attempt started',
                    'attempt_id' => $attemptId
                ]);
                
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
            
        } else {
            // Submit quiz answers
            $data = json_decode(file_get_contents("php://input"), true);
            
            $required = ['attempt_id', 'answers'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode(['message' => "Field $field is required"]);
                    exit;
                }
            }
            
            try {
                $db->beginTransaction();
                
                // Get attempt details
                $attemptStmt = $db->prepare("
                    SELECT qa.*, q.passing_score
                    FROM quiz_attempts qa
                    LEFT JOIN quizzes q ON qa.quiz_id = q.id
                    WHERE qa.id = ? AND qa.user_id = ? AND qa.status = 'in_progress'
                ");
                $attemptStmt->execute([$data['attempt_id'], $user['id']]);
                $attempt = $attemptStmt->fetch();
                
                if (!$attempt) {
                    http_response_code(404);
                    echo json_encode(['message' => 'Attempt not found or already completed']);
                    exit;
                }
                
                $totalScore = 0;
                $totalPoints = 0;
                
                // Process each answer
                foreach ($data['answers'] as $answer) {
                    // Get question details
                    $questionStmt = $db->prepare("
                        SELECT qq.*, qo.is_correct
                        FROM quiz_questions qq
                        LEFT JOIN quiz_options qo ON qq.id = qo.question_id AND qo.id = ?
                        WHERE qq.id = ?
                    ");
                    $questionStmt->execute([$answer['option_id'] ?? null, $answer['question_id']]);
                    $question = $questionStmt->fetch();
                    
                    if ($question) {
                        $isCorrect = $question['is_correct'] ?? 0;
                        $pointsEarned = $isCorrect ? $question['points'] : 0;
                        
                        $totalScore += $pointsEarned;
                        $totalPoints += $question['points'];
                        
                        // Save answer
                        $answerStmt = $db->prepare("
                            INSERT INTO quiz_answers (
                                attempt_id, question_id, option_id, answer_text, 
                                is_correct, points_earned
                            ) VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $answerStmt->execute([
                            $data['attempt_id'],
                            $answer['question_id'],
                            $answer['option_id'] ?? null,
                            $answer['answer_text'] ?? '',
                            $isCorrect,
                            $pointsEarned
                        ]);
                    }
                }
                
                // Calculate percentage and pass status
                $percentage = $totalPoints > 0 ? ($totalScore / $totalPoints) * 100 : 0;
                $isPassed = $percentage >= $attempt['passing_score'];
                
                // Update attempt
                $updateStmt = $db->prepare("
                    UPDATE quiz_attempts SET
                        score = ?, total_points = ?, percentage = ?, is_passed = ?,
                        completed_at = CURRENT_TIMESTAMP, status = 'completed',
                        time_taken = TIMESTAMPDIFF(SECOND, started_at, CURRENT_TIMESTAMP)
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $totalScore, $totalPoints, $percentage, $isPassed, $data['attempt_id']
                ]);
                
                $db->commit();
                
                echo json_encode([
                    'message' => 'Quiz submitted successfully',
                    'score' => $totalScore,
                    'total_points' => $totalPoints,
                    'percentage' => round($percentage, 2),
                    'is_passed' => $isPassed
                ]);
                
            } catch (PDOException $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
            }
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
}
?>