<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo 'Sample courses:' . PHP_EOL;
    $courses = $db->query('SELECT id, title, status FROM courses LIMIT 5')->fetchAll();
    foreach ($courses as $course) {
        echo "- Course {$course['id']}: {$course['title']} (status: {$course['status']})" . PHP_EOL;
    }
    
    echo PHP_EOL . 'Sample tests:' . PHP_EOL;
    $tests = $db->query('SELECT id, title, is_active FROM tests LIMIT 5')->fetchAll();
    foreach ($tests as $test) {
        echo "- Test {$test['id']}: {$test['title']} (active: {$test['is_active']})" . PHP_EOL;
    }
    
    echo PHP_EOL . 'Sample current affairs:' . PHP_EOL;
    $affairs = $db->query('SELECT id, title FROM current_affairs LIMIT 5')->fetchAll();
    foreach ($affairs as $affair) {
        echo "- Current Affair {$affair['id']}: {$affair['title']}" . PHP_EOL;
    }
    
    echo PHP_EOL . 'Sample blogs:' . PHP_EOL;
    $blogs = $db->query('SELECT id, title, slug, is_published FROM blogs LIMIT 5')->fetchAll();
    foreach ($blogs as $blog) {
        echo "- Blog {$blog['id']}: {$blog['title']} (slug: {$blog['slug']}, published: {$blog['is_published']})" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>