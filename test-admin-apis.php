<?php
require_once 'includes/config.php';

echo "Testing Admin API Endpoints...\n";
echo str_repeat("=", 50) . "\n";

// First, let's create a test JWT token for admin user
require_once 'includes/jwt.php';
require_once 'includes/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Get admin user
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@careerpath.com']);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "❌ Admin user not found!\n";
        exit(1);
    }
    
    // Create JWT token
    $payload = [
        'id' => $admin['id'],
        'email' => $admin['email'],
        'role' => $admin['role'],
        'exp' => time() + 3600
    ];
    
    $token = JWT::encode($payload, JWT_SECRET);
    echo "✅ Admin token created\n\n";
    
} catch (Exception $e) {
    echo "❌ Error creating admin token: " . $e->getMessage() . "\n";
    exit(1);
}

$baseUrl = 'http://localhost/career-path-api/api';
$adminEndpoints = [
    'Admin Stats' => $baseUrl . '/admin/stats.php',
    'Admin Courses' => $baseUrl . '/admin/courses.php',
    'Admin Users' => $baseUrl . '/admin/users.php',
    'Admin Enrollments' => $baseUrl . '/admin/enrollments.php',
    'Admin Categories' => $baseUrl . '/admin/categories.php',
    'Admin Payments' => $baseUrl . '/admin/payments.php'
];

foreach ($adminEndpoints as $name => $url) {
    echo "Testing $name...\n";
    
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
    
    if ($error) {
        echo "  ❌ CURL Error: $error\n";
    } elseif ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data) {
            if (isset($data['stats'])) {
                echo "  ✅ Success - Stats data returned\n";
            } elseif (isset($data['courses'])) {
                echo "  ✅ Success - " . count($data['courses']) . " courses returned\n";
            } elseif (isset($data['users'])) {
                echo "  ✅ Success - " . count($data['users']) . " users returned\n";
            } elseif (isset($data['enrollments'])) {
                echo "  ✅ Success - " . count($data['enrollments']) . " enrollments returned\n";
            } elseif (isset($data['categories'])) {
                echo "  ✅ Success - " . count($data['categories']) . " categories returned\n";
            } elseif (isset($data['payments'])) {
                echo "  ✅ Success - " . count($data['payments']) . " payments returned\n";
            } else {
                echo "  ⚠️  Response structure: " . implode(', ', array_keys($data)) . "\n";
            }
        } else {
            echo "  ❌ Invalid JSON response\n";
        }
    } else {
        echo "  ❌ HTTP Error: $httpCode\n";
        echo "  Response: " . substr($response, 0, 200) . "...\n";
    }
    
    echo "\n";
}

echo "Admin API Testing completed!\n";
?>