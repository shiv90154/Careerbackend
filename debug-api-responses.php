<?php
require_once 'includes/config.php';

echo "Debugging API Responses...\n";
echo str_repeat("=", 50) . "\n";

$baseUrl = 'http://localhost/career-path-api/api';

// Test each endpoint and show detailed response
$endpoints = [
    'Courses' => $baseUrl . '/courses/index.php',
    'Tests' => $baseUrl . '/tests/index.php',
    'Current Affairs' => $baseUrl . '/current-affairs/index.php',
    'Blogs' => $baseUrl . '/blogs/index.php'
];

foreach ($endpoints as $name => $url) {
    echo "\n" . str_repeat("-", 30) . "\n";
    echo "Testing $name API\n";
    echo "URL: $url\n";
    echo str_repeat("-", 30) . "\n";
    
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
    
    echo "HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "CURL Error: $error\n";
    } else {
        $data = json_decode($response, true);
        if ($data) {
            echo "Response Structure:\n";
            echo "- success: " . (isset($data['success']) ? ($data['success'] ? 'true' : 'false') : 'not set') . "\n";
            
            // Check for data arrays
            $dataKeys = ['courses', 'tests', 'current_affairs', 'blogs'];
            foreach ($dataKeys as $key) {
                if (isset($data[$key])) {
                    echo "- $key: " . count($data[$key]) . " items\n";
                    if (count($data[$key]) > 0) {
                        echo "  First item keys: " . implode(', ', array_keys($data[$key][0])) . "\n";
                    }
                }
            }
            
            if (isset($data['pagination'])) {
                echo "- pagination: present\n";
                echo "  total_records: " . ($data['pagination']['total_records'] ?? 'not set') . "\n";
            }
            
            if (isset($data['filters'])) {
                echo "- filters: present\n";
            }
            
        } else {
            echo "Invalid JSON Response\n";
            echo "Raw Response: " . substr($response, 0, 500) . "\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Debug completed!\n";
?>