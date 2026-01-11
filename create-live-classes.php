<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    $sql = "CREATE TABLE IF NOT EXISTS `live_classes` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `description` text DEFAULT NULL,
      `instructor_id` int(11) NOT NULL,
      `scheduled_at` timestamp NOT NULL,
      `duration_minutes` int(11) NOT NULL DEFAULT 60,
      `meeting_url` varchar(500) DEFAULT NULL,
      `status` enum('scheduled','live','completed','cancelled') NOT NULL DEFAULT 'scheduled',
      `is_premium` tinyint(1) NOT NULL DEFAULT 0,
      `price` decimal(10,2) DEFAULT 0.00,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    echo "Live classes table created successfully!\n";
    
    // Now insert the live classes data
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
    
    $stmt = $db->prepare("
        INSERT INTO live_classes (title, description, instructor_id, scheduled_at, duration_minutes, 
                                 meeting_url, status, is_premium, price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($liveClassesData as $class) {
        $stmt->execute([
            $class['title'], $class['description'], $class['instructor_id'],
            $class['scheduled_at'], $class['duration_minutes'], $class['meeting_url'],
            $class['status'], $class['is_premium'], $class['price']
        ]);
    }
    
    echo "✓ Inserted " . count($liveClassesData) . " live classes\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>