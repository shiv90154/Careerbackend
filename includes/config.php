<?php

// ===== CORS HEADERS =====
header("Access-Control-Allow-Origin: http://localhost:5174");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'career_path_lms');
define('DB_USER', 'root');
define('DB_PASS', '');

// JWT Configuration
define('JWT_SECRET', 'your-secret-key-here-change-in-production');
define('JWT_ALGORITHM', 'HS256');

// Application Configuration
define('BASE_URL', 'http://localhost:5174');
define('API_URL', BASE_URL . '/api');
define('SITE_NAME', 'Career Path Institute - Shimla');

// File Upload Configuration
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Payment Configuration
define('RAZORPAY_KEY_ID', value: '');
define('RAZORPAY_KEY_SECRET', '');

// CORS Headers
header("Access-Control-Allow-Origin: " . BASE_URL);
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>