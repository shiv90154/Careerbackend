<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "Inserting demo data...\n";
    
    // Insert demo tests
    $testsData = [
        [
            'title' => 'General Knowledge Mock Test',
            'description' => 'Comprehensive general knowledge test covering various topics including history, geography, science, and current affairs.',
            'category_id' => 1,
            'type' => 'mock',
            'difficulty_level' => 'intermediate',
            'duration_minutes' => 60,
            'total_marks' => 100,
            'passing_marks' => 40,
            'instructions' => 'Read all questions carefully. Each question carries 1 mark. No negative marking.',
            'is_premium' => 0,
            'price' => 0.00,
            'max_attempts' => 3,
            'created_by' => 1
        ],
        [
            'title' => 'Current Affairs Weekly Test',
            'description' => 'Weekly current affairs test covering recent events and important developments.',
            'category_id' => 1,
            'type' => 'practice',
            'difficulty_level' => 'beginner',
            'duration_minutes' => 30,
            'total_marks' => 50,
            'passing_marks' => 20,
            'instructions' => 'Test your knowledge of recent current events.',
            'is_premium' => 1,
            'price' => 99.00,
            'max_attempts' => 2,
            'created_by' => 1
        ],
        [
            'title' => 'HPSC Prelims Mock Test',
            'description' => 'Mock test designed for Himachal Pradesh Public Service Commission preliminary examination.',
            'category_id' => 2,
            'type' => 'mock',
            'difficulty_level' => 'advanced',
            'duration_minutes' => 120,
            'total_marks' => 200,
            'passing_marks' => 100,
            'instructions' => 'This is a comprehensive test for HPSC preparation. Negative marking applicable.',
            'is_premium' => 1,
            'price' => 199.00,
            'max_attempts' => 1,
            'created_by' => 1
        ],
        [
            'title' => 'Banking Awareness Test',
            'description' => 'Test your knowledge of banking terms, RBI policies, and financial awareness.',
            'category_id' => 2,
            'type' => 'practice',
            'difficulty_level' => 'intermediate',
            'duration_minutes' => 45,
            'total_marks' => 75,
            'passing_marks' => 30,
            'instructions' => 'Focus on recent banking developments and RBI guidelines.',
            'is_premium' => 1,
            'price' => 149.00,
            'max_attempts' => 3,
            'created_by' => 1
        ],
        [
            'title' => 'English Grammar Practice',
            'description' => 'Comprehensive English grammar test covering all major topics.',
            'category_id' => 3,
            'type' => 'practice',
            'difficulty_level' => 'beginner',
            'duration_minutes' => 40,
            'total_marks' => 60,
            'passing_marks' => 24,
            'instructions' => 'Test your English grammar skills with various question types.',
            'is_premium' => 0,
            'price' => 0.00,
            'max_attempts' => 5,
            'created_by' => 1
        ]
    ];
    
    $testStmt = $db->prepare("
        INSERT INTO tests (title, description, category_id, type, difficulty_level, duration_minutes, 
                          total_marks, passing_marks, instructions, is_premium, price, max_attempts, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($testsData as $test) {
        $testStmt->execute([
            $test['title'], $test['description'], $test['category_id'], $test['type'],
            $test['difficulty_level'], $test['duration_minutes'], $test['total_marks'],
            $test['passing_marks'], $test['instructions'], $test['is_premium'],
            $test['price'], $test['max_attempts'], $test['created_by']
        ]);
    }
    echo "✓ Inserted " . count($testsData) . " tests\n";
    
    // Insert test questions for the first test
    $questionsData = [
        [
            'test_id' => 1,
            'question_text' => 'What is the capital of Himachal Pradesh?',
            'question_type' => 'mcq',
            'options' => json_encode(['Shimla', 'Dharamshala', 'Manali', 'Kullu']),
            'correct_answer' => json_encode(['Shimla']),
            'explanation' => 'Shimla is the capital city of Himachal Pradesh.',
            'marks' => 1,
            'difficulty' => 'easy',
            'order_index' => 1
        ],
        [
            'test_id' => 1,
            'question_text' => 'Who is known as the Father of the Nation in India?',
            'question_type' => 'mcq',
            'options' => json_encode(['Mahatma Gandhi', 'Jawaharlal Nehru', 'Subhas Chandra Bose', 'Bhagat Singh']),
            'correct_answer' => json_encode(['Mahatma Gandhi']),
            'explanation' => 'Mahatma Gandhi is widely regarded as the Father of the Nation in India.',
            'marks' => 1,
            'difficulty' => 'easy',
            'order_index' => 2
        ],
        [
            'test_id' => 1,
            'question_text' => 'Which river is known as the Ganga of the South?',
            'question_type' => 'mcq',
            'options' => json_encode(['Godavari', 'Krishna', 'Kaveri', 'Tungabhadra']),
            'correct_answer' => json_encode(['Godavari']),
            'explanation' => 'The Godavari river is often called the Ganga of the South.',
            'marks' => 1,
            'difficulty' => 'medium',
            'order_index' => 3
        ]
    ];
    
    $questionStmt = $db->prepare("
        INSERT INTO test_questions (test_id, question_text, question_type, options, correct_answer, 
                                   explanation, marks, difficulty, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($questionsData as $question) {
        $questionStmt->execute([
            $question['test_id'], $question['question_text'], $question['question_type'],
            $question['options'], $question['correct_answer'], $question['explanation'],
            $question['marks'], $question['difficulty'], $question['order_index']
        ]);
    }
    echo "✓ Inserted " . count($questionsData) . " test questions\n";
    
    // Insert current affairs
    $currentAffairsData = [
        [
            'title' => 'Budget 2026 Key Highlights',
            'content' => 'The Union Budget 2026 presented several key announcements including increased allocation for education sector, new tax slabs, and infrastructure development initiatives. The budget focuses on digital transformation and sustainable development goals. Key highlights include 15% increase in education budget, new startup incentives, and green energy initiatives.',
            'date' => date('Y-m-d'),
            'category' => 'Economics',
            'importance_level' => 'critical',
            'source' => 'Ministry of Finance',
            'tags' => json_encode(['budget', 'economics', 'policy', 'taxation']),
            'is_premium' => 1,
            'price' => 49.00
        ],
        [
            'title' => 'Space Mission Success',
            'content' => 'India successfully launched its latest satellite mission, marking another milestone in space technology. The mission aims to enhance communication capabilities and weather forecasting systems. This achievement positions India among the top space-faring nations.',
            'date' => date('Y-m-d', strtotime('-1 day')),
            'category' => 'Science & Technology',
            'importance_level' => 'high',
            'source' => 'ISRO',
            'tags' => json_encode(['space', 'technology', 'satellite', 'isro']),
            'is_premium' => 0,
            'price' => 0.00
        ],
        [
            'title' => 'New Education Policy Updates',
            'content' => 'The Ministry of Education announced significant updates to the National Education Policy, focusing on digital learning, skill development, and multilingual education. These changes will impact competitive exam patterns and syllabus.',
            'date' => date('Y-m-d', strtotime('-2 days')),
            'category' => 'Education',
            'importance_level' => 'high',
            'source' => 'Ministry of Education',
            'tags' => json_encode(['education', 'policy', 'digital learning', 'skills']),
            'is_premium' => 1,
            'price' => 39.00
        ],
        [
            'title' => 'Climate Change Summit Outcomes',
            'content' => 'The recent climate change summit concluded with major commitments from world leaders. India pledged to achieve net-zero emissions by 2070 and announced new renewable energy targets. This has implications for environmental policies and green jobs.',
            'date' => date('Y-m-d', strtotime('-3 days')),
            'category' => 'Environment',
            'importance_level' => 'medium',
            'source' => 'UN Climate Summit',
            'tags' => json_encode(['climate', 'environment', 'renewable energy', 'policy']),
            'is_premium' => 0,
            'price' => 0.00
        ],
        [
            'title' => 'Digital India Progress Report',
            'content' => 'The latest Digital India progress report shows significant achievements in digital infrastructure, online services, and digital literacy. The report highlights the growth in digital payments, e-governance services, and internet connectivity across rural areas.',
            'date' => date('Y-m-d', strtotime('-4 days')),
            'category' => 'Technology',
            'importance_level' => 'medium',
            'source' => 'Ministry of Electronics and IT',
            'tags' => json_encode(['digital india', 'technology', 'governance', 'connectivity']),
            'is_premium' => 1,
            'price' => 29.00
        ],
        [
            'title' => 'Banking Sector Reforms',
            'content' => 'RBI announced new banking sector reforms including changes in lending norms, digital banking guidelines, and financial inclusion measures. These reforms aim to strengthen the banking system and improve customer services.',
            'date' => date('Y-m-d', strtotime('-5 days')),
            'category' => 'Banking & Finance',
            'importance_level' => 'high',
            'source' => 'Reserve Bank of India',
            'tags' => json_encode(['banking', 'rbi', 'reforms', 'finance']),
            'is_premium' => 1,
            'price' => 59.00
        ],
        [
            'title' => 'Healthcare Initiatives Launch',
            'content' => 'The government launched new healthcare initiatives focusing on preventive care, telemedicine expansion, and medical infrastructure development. These initiatives aim to improve healthcare accessibility in rural and remote areas.',
            'date' => date('Y-m-d', strtotime('-6 days')),
            'category' => 'Healthcare',
            'importance_level' => 'medium',
            'source' => 'Ministry of Health',
            'tags' => json_encode(['healthcare', 'telemedicine', 'rural health', 'policy']),
            'is_premium' => 0,
            'price' => 0.00
        ]
    ];
    
    $affairsStmt = $db->prepare("
        INSERT INTO current_affairs (title, content, date, category, importance_level, source, tags, is_premium, price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($currentAffairsData as $affair) {
        $affairsStmt->execute([
            $affair['title'], $affair['content'], $affair['date'], $affair['category'],
            $affair['importance_level'], $affair['source'], $affair['tags'],
            $affair['is_premium'], $affair['price']
        ]);
    }
    echo "✓ Inserted " . count($currentAffairsData) . " current affairs\n";
    
    // Insert materials
    $materialsData = [
        [
            'title' => 'Current Affairs Handbook 2026',
            'description' => 'Comprehensive handbook covering all major events of 2026 including politics, economics, science, and technology updates.',
            'type' => 'pdf',
            'file_path' => '/materials/current-affairs-2026.pdf',
            'category_id' => 1,
            'is_premium' => 1,
            'price' => 149.00,
            'created_by' => 1
        ],
        [
            'title' => 'HPSC Preparation Guide',
            'description' => 'Complete preparation guide for Himachal Pradesh Public Service Commission examinations with syllabus, previous papers, and tips.',
            'type' => 'pdf',
            'file_path' => '/materials/hpsc-guide.pdf',
            'category_id' => 2,
            'is_premium' => 1,
            'price' => 199.00,
            'created_by' => 1
        ],
        [
            'title' => 'English Grammar Workbook',
            'description' => 'Comprehensive English grammar workbook with exercises, examples, and practice questions for competitive exams.',
            'type' => 'pdf',
            'file_path' => '/materials/english-grammar.pdf',
            'category_id' => 3,
            'is_premium' => 0,
            'price' => 0.00,
            'created_by' => 1
        ],
        [
            'title' => 'Banking Awareness Notes',
            'description' => 'Updated banking awareness notes covering RBI policies, banking terms, and financial awareness for banking exams.',
            'type' => 'pdf',
            'file_path' => '/materials/banking-notes.pdf',
            'category_id' => 2,
            'is_premium' => 1,
            'price' => 99.00,
            'created_by' => 1
        ],
        [
            'title' => 'General Science Study Material',
            'description' => 'Complete general science study material covering physics, chemistry, and biology topics for competitive exams.',
            'type' => 'pdf',
            'file_path' => '/materials/general-science.pdf',
            'category_id' => 4,
            'is_premium' => 1,
            'price' => 129.00,
            'created_by' => 1
        ]
    ];
    
    $materialStmt = $db->prepare("
        INSERT INTO materials (title, description, type, file_path, category_id, is_premium, price, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($materialsData as $material) {
        $materialStmt->execute([
            $material['title'], $material['description'], $material['type'],
            $material['file_path'], $material['category_id'], $material['is_premium'],
            $material['price'], $material['created_by']
        ]);
    }
    echo "✓ Inserted " . count($materialsData) . " materials\n";
    
    // Insert live classes
    $liveClassesData = [
        [
            'title' => 'Current Affairs Discussion',
            'description' => 'Weekly discussion on important current events and their relevance for competitive exams.',
            'instructor_id' => 1,
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('+2 days 10:00')),
            'duration_minutes' => 60,
            'meeting_url' => 'https://meet.example.com/current-affairs',
            'status' => 'scheduled',
            'is_premium' => 1,
            'price' => 199.00
        ],
        [
            'title' => 'HPSC Exam Strategy Session',
            'description' => 'Live session on HPSC exam strategy, time management, and preparation tips from experts.',
            'instructor_id' => 1,
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('+5 days 14:00')),
            'duration_minutes' => 90,
            'meeting_url' => 'https://meet.example.com/hpsc-strategy',
            'status' => 'scheduled',
            'is_premium' => 1,
            'price' => 299.00
        ],
        [
            'title' => 'English Grammar Masterclass',
            'description' => 'Comprehensive English grammar session covering all important topics for competitive exams.',
            'instructor_id' => 1,
            'scheduled_at' => date('Y-m-d H:i:s', strtotime('+7 days 16:00')),
            'duration_minutes' => 75,
            'meeting_url' => 'https://meet.example.com/english-grammar',
            'status' => 'scheduled',
            'is_premium' => 0,
            'price' => 0.00
        ]
    ];
    
    $liveClassStmt = $db->prepare("
        INSERT INTO live_classes (title, description, instructor_id, scheduled_at, duration_minutes, 
                                 meeting_url, status, is_premium, price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($liveClassesData as $class) {
        $liveClassStmt->execute([
            $class['title'], $class['description'], $class['instructor_id'],
            $class['scheduled_at'], $class['duration_minutes'], $class['meeting_url'],
            $class['status'], $class['is_premium'], $class['price']
        ]);
    }
    echo "✓ Inserted " . count($liveClassesData) . " live classes\n";
    
    echo "\n✅ All demo data inserted successfully!\n";
    echo "You can now test the APIs and admin panel.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>