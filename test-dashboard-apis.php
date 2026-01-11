<?php
require_once 'includes/config.php';

echo "Testing Dashboard API Endpoints...\n";
echo str_repeat("=", 50) . "\n";

$baseUrl = 'http://localhost/career-path-api/api';

// Test endpoints that don't require authentication first
$publicEndpoints = [
    'Categories API' => $baseUrl . '/categories/index.php',
    'Courses API' => $baseUrl . '/courses/index.php',
    'Tests API' => $baseUrl . '/tests/index.php',
    'Current Affairs API' => $baseUrl . '/current-affairs/index.php',
    'Materials API' => $baseUrl . '/materials/index.php',
    'Blogs API' => $baseUrl . '/blogs/index.php',
    'Live Classes API' => $baseUrl . '/live-classes/index.php'
];

foreach ($publicEndpoints as $name => $url) {
    echo "Testing $name...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "  ❌ CURL Error: $error\n";
    } elseif ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "  ✅ Success\n";
        } else {
            echo "  ⚠️  Response received but may have issues\n";
        }
    } else {
        echo "  ❌ HTTP Error: $httpCode\n";
    }
    
    echo "\n";
}

echo "Dashboard API Testing completed!\n";
echo "\nNext Steps:\n";
echo "1. Open http://localhost:5175 in your browser\n";
echo "2. Login with admin credentials: admin@careerpath.com / password\n";
echo "3. Login with student credentials: student@example.com / student123\n";
echo "4. Test all dashboard features and navigation\n";
echo "5. Test payment functionality with demo data\n";
?>