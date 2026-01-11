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
  - supports featured filter and limit
*/

// Get query parameters
$featured = isset($_GET['featured']) ? filter_var($_GET['featured'], FILTER_VALIDATE_BOOLEAN) : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;

// Build the query
$sql = "
    SELECT 
        c.id,
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
        c.rating_avg AS avg_rating

    FROM courses c
    WHERE c.status = 'published'";

// Add featured filter if specified
if ($featured !== null) {
    $sql .= " AND c.is_featured = " . ($featured ? '1' : '0');
}

$sql .= " ORDER BY c.is_featured DESC, c.created_at DESC";

// Add limit if specified
if ($limit !== null && $limit > 0) {
    $sql .= " LIMIT " . $limit;
}

$stmt = $db->query($sql);

$courses = $stmt->fetchAll();

echo json_encode($courses);
