<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "Creating missing tables..." . PHP_EOL;
    
    // Create blog_tag_relations table
    $sql = "
    CREATE TABLE IF NOT EXISTS `blog_tag_relations` (
      `blog_id` int(11) NOT NULL,
      `tag_id` int(11) NOT NULL,
      PRIMARY KEY (`blog_id`, `tag_id`),
      FOREIGN KEY (`blog_id`) REFERENCES `blogs`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`tag_id`) REFERENCES `blog_tags`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($sql);
    echo "✅ Created blog_tag_relations table" . PHP_EOL;
    
    // Create live_class_enrollments table
    $sql = "
    CREATE TABLE IF NOT EXISTS `live_class_enrollments` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `live_class_id` int(11) NOT NULL,
      `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `attended` tinyint(1) DEFAULT 0,
      `attendance_duration` int(11) DEFAULT 0,
      `payment_id` int(11) DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_enrollment` (`user_id`, `live_class_id`),
      KEY `idx_live_enrollment_user` (`user_id`),
      KEY `idx_live_enrollment_class` (`live_class_id`),
      FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`live_class_id`) REFERENCES `live_classes`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->exec($sql);
    echo "✅ Created live_class_enrollments table" . PHP_EOL;
    
    // Fix live_classes table - add missing columns
    $columns = $db->query('DESCRIBE live_classes')->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('course_id', $columns)) {
        $db->exec("ALTER TABLE live_classes ADD COLUMN course_id int(11) DEFAULT NULL AFTER instructor_id");
        $db->exec("ALTER TABLE live_classes ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL");
        echo "✅ Added course_id column to live_classes" . PHP_EOL;
    }
    
    if (!in_array('meeting_id', $columns)) {
        $db->exec("ALTER TABLE live_classes ADD COLUMN meeting_id varchar(100) DEFAULT NULL AFTER meeting_url");
        echo "✅ Added meeting_id column to live_classes" . PHP_EOL;
    }
    
    if (!in_array('meeting_password', $columns)) {
        $db->exec("ALTER TABLE live_classes ADD COLUMN meeting_password varchar(100) DEFAULT NULL AFTER meeting_id");
        echo "✅ Added meeting_password column to live_classes" . PHP_EOL;
    }
    
    if (!in_array('max_participants', $columns)) {
        $db->exec("ALTER TABLE live_classes ADD COLUMN max_participants int(11) DEFAULT 100 AFTER meeting_password");
        echo "✅ Added max_participants column to live_classes" . PHP_EOL;
    }
    
    if (!in_array('recording_url', $columns)) {
        $db->exec("ALTER TABLE live_classes ADD COLUMN recording_url varchar(500) DEFAULT NULL AFTER status");
        echo "✅ Added recording_url column to live_classes" . PHP_EOL;
    }
    
    echo PHP_EOL . "All missing tables and columns created successfully!" . PHP_EOL;
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>