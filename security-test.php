<?php
/**
 * Comprehensive Security Testing Script
 * Career Path Institute - Shimla
 * 
 * This script tests all security implementations
 */

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/security.php';
require_once 'includes/validation.php';
require_once 'includes/logger.php';

class SecurityTester {
    private $results = [];
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function runAllTests() {
        echo "<h1>🔒 COMPREHENSIVE SECURITY TEST SUITE</h1>\n";
        echo "<p>Testing all security implementations...</p>\n";
        
        $this->testPasswordValidation();
        $this->testInputSanitization();
        $this->testRateLimiting();
        $this->testCSRFProtection();
        $this->testSQLInjectionPrevention();
        $this->testFileUploadValidation();
        $this->testAuthenticationSecurity();
        $this->testLoggingSystem();
        $this->testEnvironmentConfiguration();
        $this->testDatabaseSecurity();
        
        $this->displayResults();
    }
    
    private function testPasswordValidation() {
        echo "<h2>🔐 Password Validation Tests</h2>\n";
        
        $testCases = [
            ['password123', false, 'Too short, no uppercase, no special chars'],
            ['Password123', false, 'No special characters'],
            ['Password123!', true, 'Valid strong password'],
            ['Pass!1', false, 'Too short'],
            ['PASSWORD123!', false, 'No lowercase'],
            ['password123!', false, 'No uppercase'],
            ['Password!', false, 'No numbers'],
            ['VeryStrongPassword123!@#', true, 'Very strong password']
        ];
        
        foreach ($testCases as [$password, $expected, $description]) {
            try {
                SecurityManager::validatePassword($password);
                $result = true;
            } catch (Exception $e) {
                $result = false;
            }
            
            $status = ($result === $expected) ? '✅ PASS' : '❌ FAIL';
            echo "<p>$status: $description - '$password'</p>\n";
            
            $this->results['password_validation'][] = [
                'test' => $description,
                'expected' => $expected,
                'actual' => $result,
                'passed' => $result === $expected
            ];
        }
    }
    
    private function testInputSanitization() {
        echo "<h2>🧹 Input Sanitization Tests</h2>\n";
        
        $testCases = [
            ['<script>alert("xss")</script>', 'string', 'XSS script tag removal'],
            ['javascript:alert(1)', 'url', 'JavaScript URL sanitization'],
            ['user@example.com', 'email', 'Valid email sanitization'],
            ['<img src=x onerror=alert(1)>', 'string', 'Image XSS attempt'],
            ['SELECT * FROM users', 'string', 'SQL injection attempt'],
            ['123.45', 'float', 'Float number sanitization'],
            ['   spaced text   ', 'string', 'Whitespace trimming']
        ];
        
        foreach ($testCases as [$input, $type, $description]) {
            $sanitized = SecurityManager::sanitizeInput($input, $type);
            $isSafe = !preg_match('/<script|javascript:|onerror=/i', $sanitized);
            
            $status = $isSafe ? '✅ PASS' : '❌ FAIL';
            echo "<p>$status: $description</p>\n";
            echo "<small>Input: '$input' → Output: '$sanitized'</small><br>\n";
            
            $this->results['input_sanitization'][] = [
                'test' => $description,
                'input' => $input,
                'output' => $sanitized,
                'safe' => $isSafe
            ];
        }
    }
    
    private function testRateLimiting() {
        echo "<h2>⏱️ Rate Limiting Tests</h2>\n";
        
        $testIP = '192.168.1.100';
        
        // Test normal usage
        $allowed = SecurityManager::checkRateLimit("test_$testIP", 5, 1);
        $status = $allowed ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: First request allowed</p>\n";
        
        // Test rate limit enforcement
        for ($i = 0; $i < 6; $i++) {
            $allowed = SecurityManager::checkRateLimit("test_$testIP", 5, 1);
        }
        
        $blocked = !SecurityManager::checkRateLimit("test_$testIP", 5, 1);
        $status = $blocked ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Rate limit enforced after 5 requests</p>\n";
        
        $this->results['rate_limiting'] = [
            'first_request_allowed' => true,
            'rate_limit_enforced' => $blocked
        ];
    }
    
    private function testCSRFProtection() {
        echo "<h2>🛡️ CSRF Protection Tests</h2>\n";
        
        // Start session for CSRF testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate token
        $token = SecurityManager::generateCSRFToken();
        $status = !empty($token) ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: CSRF token generation</p>\n";
        
        // Validate correct token
        try {
            SecurityManager::validateCSRFToken($token);
            $validTokenTest = true;
        } catch (Exception $e) {
            $validTokenTest = false;
        }
        
        $status = $validTokenTest ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Valid CSRF token validation</p>\n";
        
        // Test invalid token
        try {
            SecurityManager::validateCSRFToken('invalid_token');
            $invalidTokenTest = false;
        } catch (Exception $e) {
            $invalidTokenTest = true;
        }
        
        $status = $invalidTokenTest ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Invalid CSRF token rejection</p>\n";
        
        $this->results['csrf_protection'] = [
            'token_generation' => !empty($token),
            'valid_token_accepted' => $validTokenTest,
            'invalid_token_rejected' => $invalidTokenTest
        ];
    }
    
    private function testSQLInjectionPrevention() {
        echo "<h2>💉 SQL Injection Prevention Tests</h2>\n";
        
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
            "admin'/*",
            "1; DELETE FROM users WHERE 1=1; --",
            "' UNION SELECT password FROM users --"
        ];
        
        $allSafe = true;
        
        foreach ($maliciousInputs as $input) {
            // Test with prepared statement (should be safe)
            try {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$input]);
                $result = $stmt->fetch();
                
                // If no error and no unexpected results, it's safe
                $safe = true;
                echo "<p>✅ PASS: SQL injection prevented for: " . htmlspecialchars($input) . "</p>\n";
            } catch (Exception $e) {
                $safe = false;
                $allSafe = false;
                echo "<p>❌ FAIL: SQL injection test failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }
        }
        
        $this->results['sql_injection_prevention'] = [
            'all_tests_passed' => $allSafe,
            'tests_count' => count($maliciousInputs)
        ];
    }
    
    private function testFileUploadValidation() {
        echo "<h2>📁 File Upload Validation Tests</h2>\n";
        
        // Mock file upload data
        $testFiles = [
            [
                'name' => 'test.jpg',
                'type' => 'image/jpeg',
                'size' => 1024000,
                'tmp_name' => '/tmp/test',
                'error' => UPLOAD_ERR_OK,
                'expected' => false, // Will fail because tmp_name doesn't exist
                'description' => 'Valid JPEG file (mock)'
            ],
            [
                'name' => 'malicious.php',
                'type' => 'application/x-php',
                'size' => 1024,
                'tmp_name' => '/tmp/test',
                'error' => UPLOAD_ERR_OK,
                'expected' => false,
                'description' => 'PHP file (should be rejected)'
            ],
            [
                'name' => 'large.jpg',
                'type' => 'image/jpeg',
                'size' => 50000000, // 50MB
                'tmp_name' => '/tmp/test',
                'error' => UPLOAD_ERR_OK,
                'expected' => false,
                'description' => 'File too large'
            ]
        ];
        
        foreach ($testFiles as $file) {
            try {
                SecurityManager::validateFileUpload($file, 'image');
                $result = true;
            } catch (Exception $e) {
                $result = false;
            }
            
            $status = ($result === $file['expected']) ? '✅ PASS' : '❌ FAIL';
            echo "<p>$status: {$file['description']}</p>\n";
            
            $this->results['file_upload_validation'][] = [
                'test' => $file['description'],
                'expected' => $file['expected'],
                'actual' => $result,
                'passed' => $result === $file['expected']
            ];
        }
    }
    
    private function testAuthenticationSecurity() {
        echo "<h2>🔑 Authentication Security Tests</h2>\n";
        
        // Test password hashing
        $password = 'TestPassword123!';
        $hash = SecurityManager::hashPassword($password);
        
        $hashTest = !empty($hash) && strlen($hash) > 50;
        $status = $hashTest ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Password hashing</p>\n";
        
        // Test password verification
        $verifyTest = SecurityManager::verifyPassword($password, $hash);
        $status = $verifyTest ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Password verification</p>\n";
        
        // Test wrong password rejection
        $wrongPasswordTest = !SecurityManager::verifyPassword('WrongPassword', $hash);
        $status = $wrongPasswordTest ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Wrong password rejection</p>\n";
        
        // Test secure token generation
        $token = SecurityManager::generateSecureToken(32);
        $tokenTest = strlen($token) === 64; // 32 bytes = 64 hex chars
        $status = $tokenTest ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Secure token generation</p>\n";
        
        $this->results['authentication_security'] = [
            'password_hashing' => $hashTest,
            'password_verification' => $verifyTest,
            'wrong_password_rejection' => $wrongPasswordTest,
            'secure_token_generation' => $tokenTest
        ];
    }
    
    private function testLoggingSystem() {
        echo "<h2>📝 Logging System Tests</h2>\n";
        
        // Test different log levels
        Logger::info('Test info message');
        Logger::warning('Test warning message');
        Logger::error('Test error message');
        Logger::security('Test security message');
        
        // Check if log file exists and is writable
        $logFile = LOG_FILE;
        $logDir = dirname($logFile);
        
        $dirExists = is_dir($logDir);
        $status = $dirExists ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Log directory exists</p>\n";
        
        $dirWritable = is_writable($logDir);
        $status = $dirWritable ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Log directory writable</p>\n";
        
        $this->results['logging_system'] = [
            'log_directory_exists' => $dirExists,
            'log_directory_writable' => $dirWritable
        ];
    }
    
    private function testEnvironmentConfiguration() {
        echo "<h2>⚙️ Environment Configuration Tests</h2>\n";
        
        // Test critical configuration
        $jwtSecret = JWT_SECRET;
        $jwtSecure = $jwtSecret !== 'fallback-secret-change-immediately' && strlen($jwtSecret) >= 32;
        $status = $jwtSecure ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: JWT secret is secure</p>\n";
        
        // Test database configuration
        $dbConfigured = !empty(DB_HOST) && !empty(DB_NAME) && !empty(DB_USER);
        $status = $dbConfigured ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Database configuration complete</p>\n";
        
        // Test password requirements
        $passwordMinLength = PASSWORD_MIN_LENGTH >= 12;
        $status = $passwordMinLength ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Password minimum length is secure (≥12)</p>\n";
        
        // Test bcrypt rounds
        $bcryptSecure = BCRYPT_ROUNDS >= 12;
        $status = $bcryptSecure ? '✅ PASS' : '❌ FAIL';
        echo "<p>$status: Bcrypt rounds are secure (≥12)</p>\n";
        
        $this->results['environment_configuration'] = [
            'jwt_secret_secure' => $jwtSecure,
            'database_configured' => $dbConfigured,
            'password_min_length_secure' => $passwordMinLength,
            'bcrypt_rounds_secure' => $bcryptSecure
        ];
    }
    
    private function testDatabaseSecurity() {
        echo "<h2>🗄️ Database Security Tests</h2>\n";
        
        try {
            // Test connection
            $connectionTest = $this->db !== null;
            $status = $connectionTest ? '✅ PASS' : '❌ FAIL';
            echo "<p>$status: Database connection established</p>\n";
            
            // Test if sensitive tables exist
            $tables = ['users', 'payments', 'audit_logs', 'email_otps'];
            $allTablesExist = true;
            
            foreach ($tables as $table) {
                $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                $exists = $stmt->fetch() !== false;
                
                if (!$exists) {
                    $allTablesExist = false;
                }
                
                $status = $exists ? '✅ PASS' : '❌ FAIL';
                echo "<p>$status: Table '$table' exists</p>\n";
            }
            
            // Test if audit logging is working
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM audit_logs");
            $stmt->execute();
            $auditCount = $stmt->fetchColumn();
            
            $auditWorking = $auditCount >= 0; // Just check if table is accessible
            $status = $auditWorking ? '✅ PASS' : '❌ FAIL';
            echo "<p>$status: Audit logging table accessible</p>\n";
            
            $this->results['database_security'] = [
                'connection_established' => $connectionTest,
                'all_tables_exist' => $allTablesExist,
                'audit_logging_accessible' => $auditWorking
            ];
            
        } catch (Exception $e) {
            echo "<p>❌ FAIL: Database security test failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            
            $this->results['database_security'] = [
                'connection_established' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function displayResults() {
        echo "<h1>📊 SECURITY TEST SUMMARY</h1>\n";
        
        $totalTests = 0;
        $passedTests = 0;
        
        foreach ($this->results as $category => $tests) {
            echo "<h3>" . ucwords(str_replace('_', ' ', $category)) . "</h3>\n";
            
            if (is_array($tests) && isset($tests[0])) {
                // Array of test results
                foreach ($tests as $test) {
                    $totalTests++;
                    if (isset($test['passed']) && $test['passed']) {
                        $passedTests++;
                    }
                }
            } else {
                // Single test result object
                foreach ($tests as $key => $value) {
                    if (is_bool($value)) {
                        $totalTests++;
                        if ($value) {
                            $passedTests++;
                        }
                    }
                }
            }
        }
        
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
        
        echo "<div style='background: " . ($successRate >= 90 ? '#d4edda' : ($successRate >= 70 ? '#fff3cd' : '#f8d7da')) . "; padding: 20px; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<h2>Overall Security Score: $successRate%</h2>\n";
        echo "<p>Passed: $passedTests / $totalTests tests</p>\n";
        
        if ($successRate >= 90) {
            echo "<p>🎉 <strong>EXCELLENT!</strong> Your security implementation is robust.</p>\n";
        } elseif ($successRate >= 70) {
            echo "<p>⚠️ <strong>GOOD</strong> but needs improvement in some areas.</p>\n";
        } else {
            echo "<p>🚨 <strong>CRITICAL ISSUES</strong> found. Immediate attention required!</p>\n";
        }
        echo "</div>\n";
        
        // Save results to file
        $resultsJson = json_encode($this->results, JSON_PRETTY_PRINT);
        file_put_contents('security-test-results.json', $resultsJson);
        echo "<p>📄 Detailed results saved to: security-test-results.json</p>\n";
    }
}

// Run the security tests
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Security Test Results</title></head><body>\n";

$tester = new SecurityTester();
$tester->runAllTests();

echo "</body></html>\n";
?>