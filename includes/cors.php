<?php
/**
 * CENTRALIZED CORS Handler - SINGLE POINT OF CONTROL
 * Career Path Institute - Shimla
 */

class CORSHandler {
    private static $allowedOrigins = [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
        'http://127.0.0.1:3000'
    ];
    
    public static function handleCORS() {
        // Don't set CORS headers for CLI requests
        if (php_sapi_name() === 'cli') {
            return;
        }
        
        // Get the origin from the request
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // CRITICAL: Always set a specific origin, never use *
        if (in_array($origin, self::$allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            // Default to localhost:5174 for development
            header("Access-Control-Allow-Origin: http://localhost:5174");
        }
        
        // Set CORS headers - EXACT order matters for browser compatibility
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token");
        header("Access-Control-Max-Age: 86400");
        
        // CRITICAL: Set content type AFTER CORS headers
        header("Content-Type: application/json; charset=UTF-8");
        
        // Handle preflight OPTIONS request - MUST exit immediately
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit(0);
        }
        
        // Additional security headers (non-CORS)
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}