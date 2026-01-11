<?php
// Database setup script
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS career_path_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Connect to the database
    $pdo = new PDO("mysql:host=localhost;dbname=career_path_lms;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute schema
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (file_exists($schemaFile)) {
        $schema = file_get_contents($schemaFile);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Create default admin user
        $adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        
        if ($adminExists == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
            $pdo->prepare("
                INSERT INTO users (role, full_name, email, password, is_active, email_verified, created_at)
                VALUES ('admin', 'System Administrator', 'admin@careerpath.com', ?, 1, 1, NOW())
            ")->execute([$adminPassword]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Database setup completed successfully',
            'admin_credentials' => [
                'email' => 'admin@careerpath.com',
                'password' => 'admin123'
            ]
        ]);
        
    } else {
        throw new Exception('Schema file not found');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Setup failed: ' . $e->getMessage()
    ]);
}
?>