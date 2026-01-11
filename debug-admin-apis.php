<?php
require_once 'includes/config.php';
require_once 'includes/jwt.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Get admin user and create token
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@careerpath.com']);
    $admin = $stmt->fetch();
    
    $payload = [
        'id' => $admin['id'],
        'email' => $admin['email'],
        'role' => $admin['role'],
        'exp' => time() + 3600
    ];
    
    $token = JWT::encode($payload, JWT_SECRET);
    
    // Test specific admin endpoints with detailed output
    $endpoints = [
        'Admin Courses' => 'http://localhost/career-path-api/api/admin/courses.php',
        'Admin Users' => 'http://localhost/career-path-api/api/admin/users.php',
        'Admin Enrollments' => 'http://localhost/career-path-api/api/admin/enrollments.php'
    ];
    
    foreach ($endpoints as $name => $url) {
        echo "\n" . str_repeat("-", 40) . "\n";
        echo "Testing: $name\n";
        echo "URL: $url\n";
        echo str_repeat("-", 40) . "\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "HTTP Code: $httpCode\n";
        
        if ($error) {
            echo "CURL Error: $error\n";
        } else {
            echo "Raw Response:\n";
            echo $response . "\n";
            
            $data = json_decode($response, true);
            if ($data) {
                echo "JSON Decoded Successfully\n";
                echo "Keys: " . implode(', ', array_keys($data)) . "\n";
            } else {
                echo "JSON Decode Failed\n";
                echo "JSON Error: " . json_last_error_msg() . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>