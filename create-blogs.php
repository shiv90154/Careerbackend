<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Create blogs table
    $sql = "CREATE TABLE IF NOT EXISTS `blogs` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `slug` varchar(255) NOT NULL,
      `content` longtext NOT NULL,
      `excerpt` text DEFAULT NULL,
      `featured_image` varchar(500) DEFAULT NULL,
      `author_id` int(11) NOT NULL,
      `category_id` int(11) DEFAULT NULL,
      `is_published` tinyint(1) DEFAULT 1,
      `is_featured` tinyint(1) DEFAULT 0,
      `views_count` int(11) DEFAULT 0,
      `meta_title` varchar(255) DEFAULT NULL,
      `meta_description` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    echo "✓ Blogs table created\n";
    
    // Insert demo blog posts
    $blogsData = [
        [
            'title' => 'Top 10 Current Affairs Topics for 2026',
            'slug' => 'top-10-current-affairs-2026',
            'content' => 'Here are the most important current affairs topics you should focus on for competitive exams in 2026. These topics cover politics, economics, science, technology, and social issues that are frequently asked in various government job examinations.\n\n1. Budget 2026 and Economic Policies\n2. Digital India Initiatives\n3. Climate Change and Environmental Policies\n4. Space Technology Advancements\n5. Banking Sector Reforms\n6. Education Policy Updates\n7. Healthcare Initiatives\n8. International Relations\n9. Sports and Awards\n10. Science and Technology Developments\n\nEach of these topics requires thorough understanding and regular updates. Make sure to follow reliable news sources and government publications for accurate information.',
            'excerpt' => 'Essential current affairs topics for competitive exam preparation in 2026',
            'author_id' => 1,
            'category_id' => 1,
            'is_published' => 1,
            'is_featured' => 1,
            'views_count' => 245
        ],
        [
            'title' => 'How to Prepare for HPSC Examinations',
            'slug' => 'hpsc-exam-preparation-guide',
            'content' => 'Himachal Pradesh Public Service Commission (HPSC) conducts various examinations for recruitment to state government positions. Here is a comprehensive guide to help you prepare effectively.\n\n**Understanding the Exam Pattern:**\nHPSC exams typically consist of preliminary and main examinations followed by an interview. The preliminary exam is objective type while the main exam includes descriptive papers.\n\n**Syllabus Coverage:**\n- General Studies (Indian History, Geography, Polity)\n- Current Affairs (National and International)\n- Himachal Pradesh Specific Topics\n- Quantitative Aptitude and Reasoning\n- English Language\n\n**Preparation Strategy:**\n1. Create a study schedule\n2. Focus on NCERT books for basics\n3. Read newspapers daily\n4. Practice previous year papers\n5. Take mock tests regularly\n\n**Recommended Books:**\n- Indian Polity by M. Laxmikanth\n- Indian Economy by Ramesh Singh\n- Geography by G.C. Leong\n- Current Affairs magazines\n\nConsistent preparation and regular practice are key to success in HPSC examinations.',
            'excerpt' => 'Complete preparation guide for Himachal Pradesh Public Service Commission examinations',
            'author_id' => 1,
            'category_id' => 2,
            'is_published' => 1,
            'is_featured' => 1,
            'views_count' => 189
        ],
        [
            'title' => 'Banking Awareness for Competitive Exams',
            'slug' => 'banking-awareness-competitive-exams',
            'content' => 'Banking awareness is a crucial section in most banking and financial sector examinations. This guide covers all important topics you need to know.\n\n**Reserve Bank of India (RBI):**\n- Functions and roles of RBI\n- Monetary policy tools\n- Recent policy changes\n- Governors and their tenure\n\n**Banking Terms:**\n- Types of accounts\n- Interest rates (Repo, Reverse Repo, CRR, SLR)\n- Banking products and services\n- Digital banking initiatives\n\n**Financial Inclusion:**\n- Jan Dhan Yojana\n- Mudra Scheme\n- Stand Up India\n- Digital payment systems\n\n**Recent Developments:**\n- Banking sector reforms\n- Merger of public sector banks\n- New banking licenses\n- Fintech innovations\n\n**Important Committees:**\n- Banking sector committees\n- Their recommendations\n- Implementation status\n\nStay updated with RBI notifications, banking news, and government schemes related to financial inclusion.',
            'excerpt' => 'Comprehensive banking awareness guide for banking exam aspirants',
            'author_id' => 1,
            'category_id' => 2,
            'is_published' => 1,
            'is_featured' => 0,
            'views_count' => 156
        ],
        [
            'title' => 'English Grammar Tips for Competitive Exams',
            'slug' => 'english-grammar-tips-competitive-exams',
            'content' => 'English language is an important section in most competitive examinations. Here are essential grammar tips to improve your scores.\n\n**Parts of Speech:**\nUnderstand the eight parts of speech - noun, pronoun, verb, adjective, adverb, preposition, conjunction, and interjection. Practice identifying them in sentences.\n\n**Tenses:**\n- Present, Past, and Future tenses\n- Simple, Continuous, Perfect, and Perfect Continuous forms\n- Common errors and corrections\n\n**Subject-Verb Agreement:**\n- Rules for singular and plural subjects\n- Special cases and exceptions\n- Practice with complex sentences\n\n**Common Errors:**\n- Preposition usage\n- Article usage (a, an, the)\n- Pronoun reference\n- Modifier placement\n\n**Vocabulary Building:**\n- Learn root words, prefixes, and suffixes\n- Practice synonyms and antonyms\n- Read quality newspapers and magazines\n- Use vocabulary in context\n\n**Practice Strategy:**\n1. Solve grammar exercises daily\n2. Read English newspapers\n3. Practice error detection questions\n4. Take online quizzes\n5. Learn from mistakes\n\nConsistent practice and understanding of basic rules will help you excel in English sections.',
            'excerpt' => 'Essential English grammar tips and strategies for competitive exam success',
            'author_id' => 1,
            'category_id' => 3,
            'is_published' => 1,
            'is_featured' => 0,
            'views_count' => 134
        ],
        [
            'title' => 'Study Tips for Government Job Aspirants',
            'slug' => 'study-tips-government-job-aspirants',
            'content' => 'Preparing for government job examinations requires dedication, strategy, and consistent effort. Here are proven study tips to help you succeed.\n\n**Create a Study Plan:**\n- Analyze the syllabus thoroughly\n- Allocate time for each subject\n- Set daily, weekly, and monthly targets\n- Include revision time in your schedule\n\n**Choose Right Study Materials:**\n- Standard textbooks for concepts\n- Current affairs magazines\n- Previous year question papers\n- Online test series\n\n**Effective Study Techniques:**\n- Active reading and note-making\n- Mind maps for complex topics\n- Flashcards for quick revision\n- Group study for discussions\n\n**Time Management:**\n- Follow a fixed study routine\n- Take regular breaks\n- Avoid distractions\n- Use time-blocking technique\n\n**Mock Tests and Practice:**\n- Take regular mock tests\n- Analyze your performance\n- Identify weak areas\n- Work on speed and accuracy\n\n**Stay Motivated:**\n- Set realistic goals\n- Celebrate small achievements\n- Stay positive during failures\n- Connect with fellow aspirants\n\n**Health and Wellness:**\n- Maintain proper sleep schedule\n- Exercise regularly\n- Eat healthy food\n- Practice meditation or yoga\n\nRemember, success in government exams requires patience, persistence, and smart preparation.',
            'excerpt' => 'Proven study strategies and tips for government job examination preparation',
            'author_id' => 1,
            'category_id' => 1,
            'is_published' => 1,
            'is_featured' => 0,
            'views_count' => 198
        ]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO blogs (title, slug, content, excerpt, author_id, category_id, is_published, is_featured, views_count)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($blogsData as $blog) {
        $stmt->execute([
            $blog['title'], $blog['slug'], $blog['content'], $blog['excerpt'],
            $blog['author_id'], $blog['category_id'], $blog['is_published'],
            $blog['is_featured'], $blog['views_count']
        ]);
    }
    
    echo "✓ Inserted " . count($blogsData) . " blog posts\n";
    echo "\n✅ Blogs setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>