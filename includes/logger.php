<?php

class Logger {
    private static $logFile;
    
    public static function init() {
        self::$logFile = __DIR__ . '/../logs/app.log';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
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
    
    public static function debug($message, $context = []) {
        if (defined('APP_DEBUG') && APP_DEBUG === 'true') {
            self::log('DEBUG', $message, $context);
        }
    }
    
    private static function log($level, $message, $context = []) {
        if (!self::$logFile) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also log to error_log in development
        if (defined('APP_DEBUG') && APP_DEBUG === 'true') {
            error_log("[{$level}] {$message}");
        }
    }
    
    public static function logRequest() {
        $data = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'ip' => SecurityManager::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ];
        
        self::info('API Request', $data);
    }
    
    public static function logResponse($statusCode, $message = '') {
        $data = [
            'status_code' => $statusCode,
            'message' => $message,
            'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
        ];
        
        self::info('API Response', $data);
    }
}

// Initialize logger
Logger::init();
?>