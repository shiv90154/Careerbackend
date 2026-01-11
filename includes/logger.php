<?php
require_once 'config.php';

class Logger {
    private static $logLevels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];
    
    private static function log($level, $message, $context = []) {
        $currentLevel = self::$logLevels[LOG_LEVEL] ?? 1;
        $messageLevel = self::$logLevels[$level] ?? 1;
        
        if ($messageLevel < $currentLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'CLI';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'request_uri' => $requestUri,
            'context' => $context
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        
        // Ensure log directory exists
        $logDir = dirname(LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents(LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
        
        // Also log to error_log for critical errors
        if ($level === 'CRITICAL' || $level === 'ERROR') {
            error_log("[$level] $message");
        }
    }
    
    public static function debug($message, $context = []) {
        self::log('DEBUG', $message, $context);
    }
    
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }
    
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    public static function critical($message, $context = []) {
        self::log('CRITICAL', $message, $context);
    }
    
    public static function security($message, $context = []) {
        self::log('CRITICAL', "[SECURITY] $message", $context);
    }
}