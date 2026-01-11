<?php

class CORSHandler {
    public static function handleCORS() {
        // Get the origin from the request
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Allowed origins - update for production
        $allowedOrigins = [];
        
        if (APP_ENV === 'development') {
            $allowedOrigins = [
                'http://localhost:5173',  // Vite dev server
                'http://localhost:5174',  // Alternative Vite port
                'http://localhost:3000',  // Alternative React dev server
                'http://localhost',       // Direct access
                'http://127.0.0.1:5173',  // Alternative localhost
                'http://127.0.0.1:5174',  // Alternative localhost
                'http://127.0.0.1:3000',
                'http://127.0.0.1'
            ];
        } else {
            // Production origins - update with your actual domain
            $allowedOrigins = [
                BASE_URL,  // Your production domain
                CORS_ORIGIN  // From .env file
            ];
        }

        // Check if the origin is allowed
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else if (APP_ENV === 'development') {
            // Fallback for development only
            header("Access-Control-Allow-Origin: http://localhost:5174");
        }

        // Essential CORS headers
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization, Accept, Origin");
        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 86400"); // Cache preflight for 24 hours
        
        // Set content type for JSON responses
        header("Content-Type: application/json; charset=UTF-8");
        
        // IMPORTANT: Handle preflight OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
