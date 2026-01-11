<?php

class SecurityManager {
    
    public static function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
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
    
    public static function checkRateLimit($key, $maxAttempts, $windowMinutes) {
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($key);
        $now = time();
        $windowStart = $now - ($windowMinutes * 60);
        
        $attempts = [];
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data) {
                $attempts = array_filter($data, function($timestamp) use ($windowStart) {
                    return $timestamp > $windowStart;
                });
            }
        }
        
        if (count($attempts) >= $maxAttempts) {
            return false;
        }
        
        $attempts[] = $now;
        file_put_contents($cacheFile, json_encode($attempts));
        return true;
    }
    
    public static function validateCSRFToken($token) {
        if (empty($token)) {
            throw new Exception('CSRF token is required');
        }
        
        if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
            throw new Exception('Invalid CSRF token');
        }
        
        return true;
    }
    
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
    
    public static function checkLoginAttempts($email) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT login_attempts, locked_until FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                throw new Exception('Account is temporarily locked. Please try again later.');
            }
            
            if ($user['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                $lockUntil = date('Y-m-d H:i:s', time() + (LOCKOUT_DURATION_MINUTES * 60));
                $stmt = $db->prepare("UPDATE users SET locked_until = ? WHERE email = ?");
                $stmt->execute([$lockUntil, $email]);
                throw new Exception('Too many failed login attempts. Account locked temporarily.');
            }
        }
        
        return true;
    }
    
    public static function recordFailedLogin($email) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    public static function resetLoginAttempts($email) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_ROUNDS]);
    }
    
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
?>