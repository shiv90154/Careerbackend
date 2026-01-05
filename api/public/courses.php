<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';

$db = (new Database())->getConnection();

/*
  Public courses listing:
  - sirf active courses
  - price + discount
  - average rating
  - enrolled students count
*/
$stmt = $db->query("
    SELECT 
        c.id,
        c.course_code,
        c.title,
        c.slug,
        c.short_description,
        c.price,
        c.discount_price,
        c.thumbnail,
        c.level,
        c.is_featured,

        -- total enrollments
        (SELECT COUNT(*) 
         FROM enrollments e 
         WHERE e.course_id = c.id) AS total_students,

        -- average rating
        (SELECT ROUND(AVG(r.rating),1)
         FROM course_reviews r
         WHERE r.course_id = c.id AND r.is_approved = 1) AS avg_rating

    FROM courses c
    WHERE c.is_active = 1
    ORDER BY c.is_featured DESC, c.created_at DESC
");

$courses = $stmt->fetchAll();

echo json_encode($courses);
