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

$user = Auth::requireRole('admin');
$method = $_SERVER['REQUEST_METHOD'];
$db = (new Database())->getConnection();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single quiz with questions
            $stmt = $db->prepare("
                SELECT 
                    q.*,
                    c.title as course_title,
                    l.title as lesson_title
                FROM quizzes q
                LEFT JOIN courses c ON q.course_id = c.id
                LEFT JOIN lessons l ON q.lesson_id = l.id
                WHERE q.id = ?
            ");
            $stmt->execute([$_GET['id']]);
            $quiz = $stmt->fetch();
            
            if (!$quiz) {
                http_response_code(404);
                echo json_encode(['message' => 'Quiz not found']);
                exit;
            }
            
            // Get questions
            $questionsStmt = $db->prepare("
                SELECT * FROM quiz_questions 
                WHERE quiz_id = ? 
                ORDER BY sort_order
            ");
            $questionsStmt->execute([$_GET['id']]);
            $questions = $questionsStmt->fetchAll();
            
            // Get options for each question
            foreach ($questions as &$question) {
                $optionsStmt = $db->prepare("
                    SELECT * FROM quiz_options 
                    WHERE question_id = ? 
                    ORDER BY sort_order
                ");
                $optionsStmt->execute([$question['id']]);
                $question['options'] = $optionsStmt->fetchAll();
            }
            
            $quiz['questions'] = $questions;
            
            echo json_encode(['quiz' => $quiz]);
        } else {
            // Get all quizzes
            $courseId = $_GET['course_id'] ?? '';
            $where = $courseId ? "WHERE q.course_id = ?" : "";
            $params = $courseId ? [$courseId] : [];
            
            $stmt = $db->prepare("
                SELECT 
                    q.*,
                    c.title as course_title,
                    l.title as lesson_title,
                    COUNT(DISTINCT qq.id) as question_count,
                    COUNT(DISTINCT qa.id) as attempt_count
                FROM quizzes q
                LEFT JOIN courses c ON q.course_id = c.id
                LEFT JOIN lessons l ON q.lesson_id = l.id
                LEFT JOIN quiz_questions qq ON q.id = qq.quiz_id
                LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id
                $where
                GROUP BY q.id
                ORDER BY q.created_at DESC
            ");
            $stmt->execute($params);
            $quizzes = $stmt->fetchAll();
            
            echo json_encode(['quizzes' => $quizzes]);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        $required = ['course_id', 'title'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['message' => "Field $field is required"]);
                exit;
            }
        }
        
        try {
            $db->beginTransaction();
            
            // Create quiz
            $stmt = $db->prepare("
                INSERT INTO quizzes (
                    course_id, lesson_id, title, description, instructions,
                    time_limit, max_attempts, passing_score, randomize_questions,
                    show_results, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['course_id'],
                $data['lesson_id'] ?? null,
                $data['title'],
                $data['description'] ?? '',
                $data['instructions'] ?? '',
                $data['time_limit'] ?? null,
                $data['max_attempts'] ?? 1,
                $data['passing_score'] ?? 70.00,
                $data['randomize_questions'] ?? 0,
                $data['show_results'] ?? 1,
                $data['is_active'] ?? 1
            ]);
            
            $quizId = $db->lastInsertId();
            
            // Add questions if provided
            if (!empty($data['questions'])) {
                foreach ($data['questions'] as $index => $questionData) {
                    $questionStmt = $db->prepare("
                        INSERT INTO quiz_questions (
                            quiz_id, question, question_type, points, explanation, sort_order
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    $questionStmt->execute([
                        $quizId,
                        $questionData['question'],
                        $questionData['question_type'] ?? 'multiple_choice',
                        $questionData['points'] ?? 1.00,
                        $questionData['explanation'] ?? '',
                        $index + 1
                    ]);
                    
                    $questionId = $db->lastInsertId();
                    
                    // Add options if provided
                    if (!empty($questionData['options'])) {
                        foreach ($questionData['options'] as $optionIndex => $optionData) {
                            $optionStmt = $db->prepare("
                                INSERT INTO quiz_options (
                                    question_id, option_text, is_correct, sort_order
                                ) VALUES (?, ?, ?, ?)
                            ");
                            
                            $optionStmt->execute([
                                $questionId,
                                $optionData['option_text'],
                                $optionData['is_correct'] ?? 0,
                                $optionIndex + 1
                            ]);
                        }
                    }
                }
            }
            
            $db->commit();
            
            echo json_encode([
                'message' => 'Quiz created successfully',
                'quiz_id' => $quizId
            ]);
            
        } catch (PDOException $e) {
            $db->rollBack();
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);
        $quizId = $data['id'] ?? '';
        
        if (!$quizId) {
            http_response_code(400);
            echo json_encode(['message' => 'Quiz ID required']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("
                UPDATE quizzes SET
                    lesson_id = ?, title = ?, description = ?, instructions = ?,
                    time_limit = ?, max_attempts = ?, passing_score = ?,
                    randomize_questions = ?, show_results = ?, is_active = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['lesson_id'] ?? null,
                $data['title'],
                $data['description'] ?? '',
                $data['instructions'] ?? '',
                $data['time_limit'] ?? null,
                $data['max_attempts'] ?? 1,
                $data['passing_score'] ?? 70.00,
                $data['randomize_questions'] ?? 0,
                $data['show_results'] ?? 1,
                $data['is_active'] ?? 1,
                $quizId
            ]);
            
            echo json_encode(['message' => 'Quiz updated successfully']);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        $quizId = $_GET['id'] ?? '';
        
        if (!$quizId) {
            http_response_code(400);
            echo json_encode(['message' => 'Quiz ID required']);
            exit;
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM quizzes WHERE id = ?");
            $stmt->execute([$quizId]);
            
            echo json_encode(['message' => 'Quiz deleted successfully']);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
}
?>