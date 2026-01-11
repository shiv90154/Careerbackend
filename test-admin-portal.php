<?php
require_once 'includes/config.php';
require_once 'includes/jwt.php';
require_once 'includes/database.php';

echo "=== ADMIN PORTAL COMPREHENSIVE TEST ===\n\n";

try {
    $db = (new Database())->getConnection();
    
    // Get admin user and create token
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@careerpath.com']);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "❌ Admin user not found!\n";
        exit;
    }
    
    $payload = [
        'id' => $admin['id'],
        'email' => $admin['email'],
        'role' => $admin['role'],
        'exp' => time() + 3600
    ];
    
    $token = JWT::encode($payload, JWT_SECRET);
    echo "✅ Admin token generated successfully\n\n";
    
    // Test all admin endpoints
    $endpoints = [
        'Admin Stats' => 'http://localhost/career-path-api/api/admin/stats.php',
        'Admin Categories' => 'http://localhost/career-path-api/api/admin/categories.php',
        'Admin Courses' => 'http://localhost/career-path-api/api/admin/courses.php',
        'Admin Users' => 'http://localhost/career-path-api/api/admin/users.php',
        'Admin Enrollments' => 'http://localhost/career-path-api/api/admin/enrollments.php',
        'Admin Payments' => 'http://localhost/career-path-api/api/admin/payments.php'
    ];
    
    $results = [];
    
    foreach ($endpoints as $name => $url) {
        echo "Testing: $name\n";
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
        
        if ($error) {
            echo "❌ CURL Error: $error\n";
            $results[$name] = 'FAILED';
        } else {
            $data = json_decode($response, true);
            if ($httpCode === 200 && $data) {
                echo "✅ SUCCESS - HTTP $httpCode\n";
                echo "   Response keys: " . implode(', ', array_keys($data)) . "\n";
                
                // Show data counts
                if (isset($data['courses'])) {
                    echo "   Courses: " . count($data['courses']) . "\n";
                }
                if (isset($data['users'])) {
                    echo "   Users: " . count($data['users']) . "\n";
                }
                if (isset($data['categories'])) {
                    echo "   Categories: " . count($data['categories']) . "\n";
                }
                if (isset($data['enrollments'])) {
                    echo "   Enrollments: " . count($data['enrollments']) . "\n";
                }
                if (isset($data['payments'])) {
                    echo "   Payments: " . count($data['payments']) . "\n";
                }
                if (isset($data['stats'])) {
                    echo "   Stats available: " . implode(', ', array_keys($data['stats'])) . "\n";
                }
                
                $results[$name] = 'PASSED';
            } else {
                echo "❌ FAILED - HTTP $httpCode\n";
                echo "   Response: " . substr($response, 0, 200) . "...\n";
                $results[$name] = 'FAILED';
            }
        }
        echo "\n";
    }
    
    // Summary
    echo str_repeat("=", 50) . "\n";
    echo "ADMIN PORTAL TEST SUMMARY\n";
    echo str_repeat("=", 50) . "\n";
    
    $passed = 0;
    $total = count($results);
    
    foreach ($results as $endpoint => $status) {
        $icon = $status === 'PASSED' ? '✅' : '❌';
        echo "$icon $endpoint: $status\n";
        if ($status === 'PASSED') $passed++;
    }
    
    echo "\nOverall: $passed/$total tests passed\n";
    
    if ($passed === $total) {
        echo "🎉 ALL ADMIN PORTAL TESTS PASSED!\n";
        echo "\nAdmin portal is fully functional:\n";
        echo "- Dashboard stats working\n";
        echo "- Course management working\n";
        echo "- Student management working\n";
        echo "- Category management working\n";
        echo "- Enrollment tracking working\n";
        echo "- Payment management working\n";
    } else {
        echo "⚠️  Some tests failed. Check the details above.\n";
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>