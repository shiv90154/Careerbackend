<?php
require_once 'config.php';
require_once 'database.php';
require_once 'jwt.php';
require_once 'response.php';
require_once 'logger.php';

class Auth {
    /**
     * Authenticate user from JWT token
     */
    public static function authenticate($requiredRole = null) {
        $token = self::getBearerToken();
        
        if (!$token) {
            ApiResponse::unauthorized('Authentication token required');
        }
        
        try {
            $decoded = JWT::decode($token, JWT_SECRET, [JWT_ALGORITHM]);
            $user = self::getUserFromToken($decoded);
            
            if (!$user) {
                ApiResponse::unauthorized('Invalid token');
            }
            
            if (!$user['is_active']) {
                ApiResponse::forbidden('Account is deactivated');
            }
            
            if ($requiredRole && $user['role'] !== $requiredRole) {
                ApiResponse::forbidden('Insufficient permissions');
            }
            
            return $user;
            
        } catch (Exception $e) {
            Logger::warning("Authentication failed: " . $e->getMessage(), [
                'token' => substr($token, 0, 20) . '...',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            ApiResponse::unauthorized('Invalid or expired token');
        }
    }
    
    /**
     * Get user data from decoded token
     */
    private static function getUserFromToken($decoded) {
        $db = (new Database())->getConnection();
        
        $query = "
            SELECT id, full_name, email, role, is_active, email_verified, last_login
            FROM users 
            WHERE id = ? AND is_active = 1
            LIMIT 1
        ";
        $stmt = $db->prepare($query);
        $stmt->execute([$decoded->id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Extract Bearer token from Authorization header
     */
    private static function getBearerToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } else {
            return null;
        }
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Check if user has specific permission
     */
    public static function hasPermission($user, $permission) {
        $rolePermissions = [
            'admin' => ['*'], // Admin has all permissions
            'instructor' => [
                'courses.create',
                'courses.update',
                'courses.delete',
                'lessons.create',
                'lessons.update',
                'lessons.delete',
                'quizzes.create',
                'quizzes.update',
                'quizzes.delete',
                'students.view'
            ],
            'student' => [
                'courses.view',
                'lessons.view',
                'quizzes.take',
                'profile.update'
            ]
        ];
        
        $userPermissions = $rolePermissions[$user['role']] ?? [];
        
        return in_array('*', $userPermissions) || in_array($permission, $userPermissions);
    }
    
    /**
     * Require specific permission
     */
    public static function requirePermission($user, $permission) {
        if (!self::hasPermission($user, $permission)) {
            Logger::warning("Permission denied", [
                'user_id' => $user['id'],
                'required_permission' => $permission,
                'user_role' => $user['role']
            ]);
            
            ApiResponse::forbidden('You do not have permission to perform this action');
        }
    }
    
    /**
     * Generate password reset token
     */
    public static function generatePasswordResetToken($userId) {
        $token = SecurityManager::generateSecureToken(32);
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        $db = (new Database())->getConnection();
        
        $query = "
            UPDATE users 
            SET password_reset_token = ?, 
                password_reset_expires = ?
            WHERE id = ?
        ";
        $stmt = $db->prepare($query);
        $stmt->execute([$token, $expires, $userId]);
        
        return $token;
    }
    
    /**
     * Verify password reset token
     */
    public static function verifyPasswordResetToken($token) {
        $db = (new Database())->getConnection();
        
        $query = "
            SELECT id, email, full_name
            FROM users 
            WHERE password_reset_token = ? 
            AND password_reset_expires > NOW()
            AND is_active = 1
            LIMIT 1
        ";
        $stmt = $db->prepare($query);
        $stmt->execute([$token]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Clear password reset token
     */
    public static function clearPasswordResetToken($userId) {
        $db = (new Database())->getConnection();
        
        $query = "
            UPDATE users 
            SET password_reset_token = NULL, 
                password_reset_expires = NULL
            WHERE id = ?
        ";
        $stmt = $db->prepare($query);
        $stmt->execute([$userId]);
    }
}