<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "Adding sample courses...\n";
    
    // Sample courses data
    $courses = [
        [
            'title' => 'HPSC Prelims Complete Course',
            'slug' => 'hpsc-prelims-complete-course',
            'description' => 'Complete preparation course for HPSC Preliminary examination covering all subjects including History, Geography, Polity, Economics, Current Affairs, and General Science.',
            'short_description' => 'Comprehensive HPSC Prelims preparation with expert guidance',
            'category_id' => 1, // Technology category (we'll use existing categories)
            'instructor_id' => 1, // Admin user
            'level' => 'intermediate',
            'price' => 2999.00,
            'discount_price' => 1999.00,
            'duration_hours' => 120,
            'status' => 'published',
            'is_featured' => 1
        ],
        [
            'title' => 'Banking Awareness Masterclass',
            'slug' => 'banking-awareness-masterclass',
            'description' => 'Master banking concepts, financial awareness, and current banking trends. Perfect for banking exams like IBPS, SBI, and other financial sector examinations.',
            'short_description' => 'Complete banking awareness for competitive exams',
            'category_id' => 2, // Business category
            'instructor_id' => 1,
            'level' => 'beginner',
            'price' => 1499.00,
            'discount_price' => 999.00,
            'duration_hours' => 60,
            'status' => 'published',
            'is_featured' => 1
        ],
        [
            'title' => 'English Grammar for Competitive Exams',
            'slug' => 'english-grammar-competitive-exams',
            'description' => 'Comprehensive English grammar course designed specifically for competitive examinations. Covers all important topics with practice exercises and mock tests.',
            'short_description' => 'Master English grammar for competitive success',
            'category_id' => 4, // Personal Development category
            'instructor_id' => 1,
            'level' => 'beginner',
            'price' => 799.00,
            'discount_price' => 499.00,
            'duration_hours' => 40,
            'status' => 'published',
            'is_featured' => 0
        ],
        [
            'title' => 'Current Affairs 2026 - Complete Package',
            'slug' => 'current-affairs-2026-complete',
            'description' => 'Stay updated with the latest current affairs for 2026. Daily updates, monthly compilations, and topic-wise coverage of national and international events.',
            'short_description' => 'Complete current affairs coverage for 2026',
            'category_id' => 1,
            'instructor_id' => 1,
            'level' => 'intermediate',
            'price' => 1999.00,
            'discount_price' => 1299.00,
            'duration_hours' => 80,
            'status' => 'published',
            'is_featured' => 1
        ],
        [
            'title' => 'General Studies Foundation Course',
            'slug' => 'general-studies-foundation',
            'description' => 'Build a strong foundation in General Studies covering History, Geography, Polity, Economics, Science & Technology, and Environment.',
            'short_description' => 'Strong foundation in General Studies for all competitive exams',
            'category_id' => 4,
            'instructor_id' => 1,
            'level' => 'beginner',
            'price' => 0.00, // Free course
            'discount_price' => null,
            'duration_hours' => 100,
            'status' => 'published',
            'is_featured' => 1
        ]
    ];
    
    $insertQuery = "
        INSERT INTO courses (
            title, slug, description, short_description, category_id, instructor_id, 
            level, price, discount_price, duration_hours, status, is_featured,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ";
    
    $stmt = $db->prepare($insertQuery);
    
    foreach ($courses as $course) {
        $stmt->execute([
            $course['title'],
            $course['slug'],
            $course['description'],
            $course['short_description'],
            $course['category_id'],
            $course['instructor_id'],
            $course['level'],
            $course['price'],
            $course['discount_price'],
            $course['duration_hours'],
            $course['status'],
            $course['is_featured']
        ]);
        
        echo "✅ Added course: {$course['title']}\n";
    }
    
    echo "\n" . count($courses) . " sample courses added successfully!\n";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>