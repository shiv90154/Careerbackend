<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Only set content type header if not running from CLI
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

try {
    $db = (new Database())->getConnection();
    
    // Read and execute the schema file
    $schemaFile = __DIR__ . '/database/schema.sql';
    
    if (!file_exists($schemaFile)) {
        throw new Exception('Schema file not found');
    }
    
    $sql = file_get_contents($schemaFile);
    
    // Split the SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) && 
                   !preg_match('/^\s*\/\*/', $stmt) &&
                   !preg_match('/^\s*(SET|START|COMMIT|DELIMITER)/', $stmt);
        }
    );
    
    $executed = 0;
    $errors = [];
    
    foreach ($statements as $statement) {
        try {
            $db->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // Skip errors for existing tables/data
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate entry') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    // Verify some key data exists
    $verificationQueries = [
        'users' => "SELECT COUNT(*) as count FROM users WHERE role = 'admin'",
        'categories' => "SELECT COUNT(*) as count FROM categories",
        'tests' => "SELECT COUNT(*) as count FROM tests",
        'current_affairs' => "SELECT COUNT(*) as count FROM current_affairs",
        'materials' => "SELECT COUNT(*) as count FROM materials"
    ];
    
    $verification = [];
    foreach ($verificationQueries as $table => $query) {
        try {
            $stmt = $db->query($query);
            $result = $stmt->fetch();
            $verification[$table] = $result['count'];
        } catch (PDOException $e) {
            $verification[$table] = 0;
        }
    }
    
    $response = [
        'success' => true,
        'message' => 'Demo data setup completed successfully',
        'executed_statements' => $executed,
        'errors' => $errors,
        'verification' => $verification,
        'notes' => [
            'Default admin login: admin@careerpath.com / password',
            'Database includes sample tests, current affairs, and materials',
            'Payment system is configured for demo mode',
            'All premium content has sample pricing'
        ]
    ];
    
    if (php_sapi_name() === 'cli') {
        echo "Setup completed successfully!\n";
        echo "Executed statements: " . $executed . "\n";
        echo "Verification: " . json_encode($verification, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo json_encode($response);
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode($response);
    }
}
?>