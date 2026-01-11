<?php
require_once 'includes/config.php';

echo "Testing Key API Endpoints...\n";
echo str_repeat("=", 50) . "\n";

$baseUrl = 'http://localhost/career-path-api/api';
$endpoints = [
    'Tests API' => $baseUrl . '/tests/index.php',
    'Current Affairs API' => $baseUrl . '/current-affairs/index.php',
    'Materials API' => $baseUrl . '/materials/index.php',
    'Blogs API' => $baseUrl . '/blogs/index.php',
    'Live Classes API' => $baseUrl . '/live-classes/index.php',
    'Categories API' => $baseUrl . '/categories/index.php'
];

foreach ($endpoints as $name => $url) {
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
            $count = 0;
            if (isset($data['tests'])) $count = count($data['tests']);
            elseif (isset($data['current_affairs'])) $count = count($data['current_affairs']);
            elseif (isset($data['materials'])) $count = count($data['materials']);
            elseif (isset($data['blogs'])) $count = count($data['blogs']);
            elseif (isset($data['live_classes'])) $count = count($data['live_classes']);
            elseif (isset($data['categories'])) $count = count($data['categories']);
            
            echo "  ✅ Success - $count items returned\n";
        } else {
            echo "  ⚠️  Response received but may have issues\n";
            echo "  Response: " . substr($response, 0, 100) . "...\n";
        }
    } else {
        echo "  ❌ HTTP Error: $httpCode\n";
        echo "  Response: " . substr($response, 0, 100) . "...\n";
    }
    
    echo "\n";
}

echo "API Testing completed!\n";
echo "\nNext Steps:\n";
echo "1. Start your React development server: npm run dev (in career folder)\n";
echo "2. Login with admin credentials to test admin panel\n";
echo "3. Login with student credentials to test student features\n";
echo "4. Test payment functionality with demo data\n";
?>