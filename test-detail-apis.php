<?php
require_once 'includes/config.php';

echo "Testing Detail API Endpoints...\n";
echo str_repeat("=", 50) . "\n";

$baseUrl = 'http://localhost/career-path-api/api';
$endpoints = [
    'Course Detail API' => $baseUrl . '/courses/detail.php?id=1',
    'Test Detail API' => $baseUrl . '/tests/detail.php?id=1',
    'Current Affairs Detail API' => $baseUrl . '/current-affairs/detail.php?id=1',
    'Blog Detail API' => $baseUrl . '/blogs/detail.php?id=1',
    'Courses List API' => $baseUrl . '/courses/index.php',
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
            echo "  ✅ Success\n";
        } else {
            echo "  ⚠️  Response: " . substr($response, 0, 200) . "...\n";
        }
    } else {
        echo "  ❌ HTTP Error: $httpCode\n";
        echo "  Response: " . substr($response, 0, 200) . "...\n";
    }
    
    echo "\n";
}

echo "Detail API Testing completed!\n";
?>