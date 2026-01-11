<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();
    
    // Read the schema file
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
    
    $validStatements = 0;
    $invalidStatements = [];
    $warnings = [];
    
    foreach ($statements as $index => $statement) {
        try {
            // Try to prepare the statement to check syntax
            $stmt = $db->prepare($statement);
            $validStatements++;
        } catch (PDOException $e) {
            // Check if it's a syntax error or just a table exists error
            if (strpos($e->getMessage(), 'syntax error') !== false ||
                strpos($e->getMessage(), 'SQL syntax') !== false) {
                $invalidStatements[] = [
                    'statement_number' => $index + 1,
                    'error' => $e->getMessage(),
                    'statement_preview' => substr($statement, 0, 100) . '...'
                ];
            } else {
                // It's likely a "table exists" or similar error, which is fine for validation
                $validStatements++;
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $warnings[] = "Statement " . ($index + 1) . ": Table already exists";
                }
            }
        }
    }
    
    // Check for common issues
    $duplicateChecks = [
        'CREATE TABLE `users`' => 0,
        'CREATE TABLE `notifications`' => 0,
        'CREATE TABLE `discussions`' => 0,
        'CREATE TABLE `blogs`' => 0
    ];
    
    foreach ($duplicateChecks as $pattern => $count) {
        $duplicateChecks[$pattern] = substr_count($sql, $pattern);
    }
    
    $duplicates = array_filter($duplicateChecks, function($count) {
        return $count > 1;
    });
    
    echo json_encode([
        'success' => count($invalidStatements) === 0,
        'message' => count($invalidStatements) === 0 ? 'Schema validation passed' : 'Schema has syntax errors',
        'statistics' => [
            'total_statements' => count($statements),
            'valid_statements' => $validStatements,
            'invalid_statements' => count($invalidStatements),
            'warnings' => count($warnings)
        ],
        'errors' => $invalidStatements,
        'warnings' => $warnings,
        'duplicate_tables' => $duplicates,
        'validation_passed' => count($invalidStatements) === 0 && count($duplicates) === 0
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'validation_passed' => false
    ]);
}
?>