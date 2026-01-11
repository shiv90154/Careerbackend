<?php
require_once 'logger.php';

class ApiResponse {

    private static function sendHeaders($statusCode = 200) {
        http_response_code($statusCode);
        
        // VERY IMPORTANT: JSON content type
        header("Content-Type: application/json; charset=UTF-8");

        // Prevent caching issues
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: 0");
    }

    /**
     * Success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        self::sendHeaders($statusCode);

        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c'),
            'data' => $data
        ];

        Logger::info("API Success Response", [
            'status_code' => $statusCode,
            'message' => $message,
            'data_type' => gettype($data)
        ]);

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Error response
     */
    public static function error($message = 'An error occurred', $statusCode = 400, $errors = null) {
        self::sendHeaders($statusCode);

        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
            'errors' => $errors
        ];

        if (APP_ENV !== 'development' && $statusCode >= 500) {
            $response['message'] = 'Internal server error';
        }

        Logger::error("API Error Response", [
            'status_code' => $statusCode,
            'message' => $message,
            'errors' => $errors
        ]);

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function validationError($errors, $message = 'Validation failed') {
        self::error($message, 422, $errors);
    }

    public static function unauthorized($message = 'Unauthorized') {
        Logger::warning("Unauthorized access attempt", [
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        self::error($message, 401);
    }

    public static function forbidden($message = 'Forbidden') {
        Logger::warning("Forbidden access attempt", [
            'message' => $message,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        self::error($message, 403);
    }

    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    public static function methodNotAllowed($message = 'Method not allowed') {
        self::error($message, 405);
    }

    public static function rateLimitExceeded($message = 'Rate limit exceeded') {
        Logger::warning("Rate limit exceeded", [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        self::error($message, 429);
    }

    public static function serverError($message = 'Internal server error') {
        Logger::critical("Server error", [
            'message' => $message,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ]);
        self::error($message, 500);
    }
}
