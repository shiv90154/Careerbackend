<?php
// IMPORTANT: Prevent any unwanted output BEFORE headers
ob_start();

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Handle CORS before ANY other output
require_once __DIR__ . '/cors.php';
CORSHandler::handleCORS();

// Debug CORS in development (after APP_DEBUG is defined)
if (defined('APP_DEBUG') && APP_DEBUG === 'true') {
    error_log("CORS Debug - Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? 'No origin'));
    error_log("CORS Debug - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("CORS Debug - Headers sent: " . (headers_sent() ? 'Yes' : 'No'));
}

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? 'career_path_lms');
if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? 'root');
if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// JWT Configuration
if (!defined('JWT_SECRET')) define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'fallback-secret-change-immediately');
if (!defined('JWT_ALGORITHM')) define('JWT_ALGORITHM', $_ENV['JWT_ALGORITHM'] ?? 'HS256');
if (!defined('JWT_EXPIRY')) define('JWT_EXPIRY', $_ENV['JWT_EXPIRY'] ?? 86400);

// Application Configuration
if (!defined('APP_ENV')) define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
if (!defined('APP_DEBUG')) define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? 'true');
if (!defined('BASE_URL')) define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost:5173');
if (!defined('API_URL')) define('API_URL', $_ENV['API_URL'] ?? 'http://localhost/career-path-api/api');
if (!defined('SITE_NAME')) define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Career Path Institute - Shimla');

// Security Configuration
if (!defined('BCRYPT_ROUNDS')) define('BCRYPT_ROUNDS', $_ENV['BCRYPT_ROUNDS'] ?? 12);
if (!defined('PASSWORD_MIN_LENGTH')) define('PASSWORD_MIN_LENGTH', $_ENV['PASSWORD_MIN_LENGTH'] ?? 12);
if (!defined('OTP_EXPIRY_MINUTES')) define('OTP_EXPIRY_MINUTES', $_ENV['OTP_EXPIRY_MINUTES'] ?? 10);
if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', $_ENV['MAX_LOGIN_ATTEMPTS'] ?? 5);
if (!defined('LOCKOUT_DURATION_MINUTES')) define('LOCKOUT_DURATION_MINUTES', $_ENV['LOCKOUT_DURATION_MINUTES'] ?? 30);

// Rate Limiting
if (!defined('RATE_LIMIT_REQUESTS')) define('RATE_LIMIT_REQUESTS', $_ENV['RATE_LIMIT_REQUESTS'] ?? 100);
if (!defined('RATE_LIMIT_WINDOW_MINUTES')) define('RATE_LIMIT_WINDOW_MINUTES', $_ENV['RATE_LIMIT_WINDOW_MINUTES'] ?? 15);

// Email Configuration
if (!defined('SMTP_HOST')) define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
if (!defined('SMTP_PORT')) define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? '');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? SITE_NAME);

// Razorpay Configuration
if (!defined('RAZORPAY_KEY_ID')) define('RAZORPAY_KEY_ID', $_ENV['RAZORPAY_KEY_ID'] ?? '');
if (!defined('RAZORPAY_KEY_SECRET')) define('RAZORPAY_KEY_SECRET', $_ENV['RAZORPAY_KEY_SECRET'] ?? '');
if (!defined('RAZORPAY_WEBHOOK_SECRET')) define('RAZORPAY_WEBHOOK_SECRET', $_ENV['RAZORPAY_WEBHOOK_SECRET'] ?? '');

// File Upload Configuration
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? 10485760);
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', $_ENV['ALLOWED_IMAGE_TYPES'] ?? 'jpg,jpeg,png,gif,webp');
if (!defined('ALLOWED_DOCUMENT_TYPES')) define('ALLOWED_DOCUMENT_TYPES', $_ENV['ALLOWED_DOCUMENT_TYPES'] ?? 'pdf,doc,docx,ppt,pptx');

// Logging Configuration
if (!defined('LOG_LEVEL')) define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'INFO');
if (!defined('LOG_FILE')) define('LOG_FILE', $_ENV['LOG_FILE'] ?? 'logs/app.log');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting
if (APP_DEBUG === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Flush everything at the end
ob_end_flush();
