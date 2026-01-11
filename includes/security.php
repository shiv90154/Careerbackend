<?php
require_once 'config.php';
require_once 'logger.php';

class SecurityManager {
    private static $rateLimitStore = [];
    private static $loginAttempts = [];
    
    /**
     * Rate limiting implementation
     */
    public static function checkRateLimit($identifier, $maxRequests = null, $windowMinutes = null) {
        $maxRequests = $maxRequests ?? RATE_LIMIT_REQUESTS;
        $windowMinutes = $windowMinutes ?? RATE_LIMIT_WINDOW_MINUTES;
        
        $now = time();
        $windowStart = $now - ($windowMinutes * 60);
        
        // Clean old entries
        if (isset(self::$rateLimitStore[$identifier])) {
            self::$rateLimitStore[$identifier] = array_filter(
                self::$rateLimitStore[$identifier],
                function($timestamp) use ($windowStart) {
                    return $timestamp > $windowStart;
                }
            );
        } else {
            self::$rateLimitStore[$identifier] = [];
        }
        
        // Check if limit exceeded
        if (count(self::$rateLimitStore[$identifier]) >= $maxRequests) {
            Logger::warning("Rate limit exceeded for identifier: $identifier");
            return false;
        }
        
        // Add current request
        self::$rateLimitStore[$identifier][] = $now;
        return true;
    }
    
    /**
     * Login attempt tracking
     */
    public static function checkLoginAttempts($identifier) {
        $now = time();
        $lockoutDuration = LOCKOUT_DURATION_MINUTES * 60;
        
        if (isset(self::$loginAttempts[$identifier])) {
            $attempts = self::$loginAttempts[$identifier];
            
            // Check if still locked out
            if ($attempts['locked_until'] && $now < $attempts['locked_until']) {
                $remainingTime = $attempts['locked_until'] - $now;
                Logger::warning("Login attempt during lockout for identifier: $identifier");
                throw new Exception("Account locked. Try again in " . ceil($remainingTime / 60) . " minutes.");
            }
            
            // Reset if lockout period has passed
            if ($attempts['locked_until'] && $now >= $attempts['locked_until']) {
                self::$loginAttempts[$identifier] = ['count' => 0, 'locked_until' => null];
            }
        } else {
            self::$loginAttempts[$identifier] = ['count' => 0, 'locked_until' => null];
        }
        
        return true;
    }
    
    /**
     * Record failed login attempt
     */
    public static function recordFailedLogin($identifier) {
        $now = time();
        
        if (!isset(self::$loginAttempts[$identifier])) {
            self::$loginAttempts[$identifier] = ['count' => 0, 'locked_until' => null];
        }
        
        self::$loginAttempts[$identifier]['count']++;
        
        if (self::$loginAttempts[$identifier]['count'] >= MAX_LOGIN_ATTEMPTS) {
            self::$loginAttempts[$identifier]['locked_until'] = $now + (LOCKOUT_DURATION_MINUTES * 60);
            Logger::warning("Account locked due to failed login attempts: $identifier");
        }
        
        Logger::info("Failed login attempt recorded for: $identifier (Count: " . self::$loginAttempts[$identifier]['count'] . ")");
    }
    
    /**
     * Reset login attempts on successful login
     */
    public static function resetLoginAttempts($identifier) {
        if (isset(self::$loginAttempts[$identifier])) {
            unset(self::$loginAttempts[$identifier]);
        }
    }
    
    /**
     * Validate and sanitize input
     */
    public static function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        $minLength = PASSWORD_MIN_LENGTH;
        
        if (strlen($password) < $minLength) {
            throw new Exception("Password must be at least $minLength characters long");
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception("Password must contain at least one uppercase letter");
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            throw new Exception("Password must contain at least one lowercase letter");
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception("Password must contain at least one number");
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new Exception("Password must contain at least one special character");
        }
        
        return true;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            Logger::warning("CSRF token validation failed");
            throw new Exception("Invalid CSRF token");
        }
        
        return true;
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $type = 'image') {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception("Invalid file upload");
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception("File size exceeds maximum allowed size");
        }
        
        $allowedTypes = $type === 'image' ? 
            explode(',', ALLOWED_IMAGE_TYPES) : 
            explode(',', ALLOWED_DOCUMENT_TYPES);
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes)) {
            throw new Exception("File type not allowed");
        }
        
        // Additional security checks for images
        if ($type === 'image') {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception("Invalid image file");
            }
        }
        
        return true;
    }
    
    /**
     * Generate secure random string
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_ROUNDS]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}