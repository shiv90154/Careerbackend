<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Check all tables
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $blogTables = array_filter($tables, function($table) {
        return strpos($table, 'blog') !== false;
    });
    
    echo 'Blog-related tables: ' . implode(', ', $blogTables) . PHP_EOL;
    
    // Check if blog_tag_relations exists
    if (in_array('blog_tag_relations', $tables)) {
        echo 'blog_tag_relations table exists' . PHP_EOL;
    } else {
        echo 'blog_tag_relations table does NOT exist' . PHP_EOL;
    }
    
    // Check live_class_enrollments
    if (in_array('live_class_enrollments', $tables)) {
        echo 'live_class_enrollments table exists' . PHP_EOL;
    } else {
        echo 'live_class_enrollments table does NOT exist' . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>