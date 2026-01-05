<?php
require_once 'config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    private static $secret_key = JWT_SECRET;
    private static $algorithm = JWT_ALGORITHM;

    public static function generateToken($user_id, $role, $email) {
        $issued_at = time();
        $expiration_time = $issued_at + (60 * 60 * 24); // 24 hours

        $payload = array(
            "iat" => $issued_at,
            "exp" => $expiration_time,
            "iss" => BASE_URL,
            "data" => array(
                "user_id" => $user_id,
                "role" => $role,
                "email" => $email
            )
        );

        return JWT::encode($payload, self::$secret_key, self::$algorithm);
    }

    public static function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(self::$secret_key, self::$algorithm));
            return (array) $decoded->data;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getAuthorizationHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }

    public static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
?>