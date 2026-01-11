<?php
require_once 'includes/config.php';

echo "Testing Frontend API Endpoints...\n";
echo str_repeat("=", 50) . "\n";

$baseUrl = 'http://localhost/career-path-api/api';
$endpoints = [
    'Courses List' => $baseUrl . '/courses/index.php',
    'Tests List' => $baseUrl . '/tests/index.php',
    'Current Affairs List' => $baseUrl . '/current-affairs/index.php',
    'Blogs List' => $baseUrl . '/blogs/index.php',
    'Course Detail' => $baseUrl . '/courses/detail.php?id=1',
    'Test Detail' => $baseUrl . '/tests/detail.php?id=1',
    'Current Affairs Detail' => $baseUrl . '/current-affairs/detail.php?id=1',
    'Blog Detail' => $baseUrl . '/blogs/detail.php?id=1',
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
        if ($data) {
            if (isset($data['success']) && $data['success']) {
                // List endpoints
                $count = 0;
                if (isset($data['courses'])) $count = count($data['courses']);
                elseif (isset($data['tests'])) $count = count($data['tests']);
                elseif (isset($data['current_affairs'])) $count = count($data['current_affairs']);
                elseif (isset($data['blogs'])) $count = count($data['blogs']);
                
                echo "  ✅ Success - $count items returned\n";
            } elseif (isset($data['course']) || isset($data['test']) || isset($data['current_affair']) || isset($data['blog'])) {
                // Detail endpoints
                echo "  ✅ Success - Detail data returned\n";
            } else {
                echo "  ⚠️  Response structure: " . implode(', ', array_keys($data)) . "\n";
            }
        } else {
            echo "  ❌ Invalid JSON response\n";
        }
    } else {
        echo "  ❌ HTTP Error: $httpCode\n";
        echo "  Response: " . substr($response, 0, 100) . "...\n";
    }
    
    echo "\n";
}

echo "Frontend API Testing completed!\n";
echo "\nNow testing the React app at http://localhost:5173\n";
echo "Check these pages:\n";
echo "- Courses: http://localhost:5173/courses\n";
echo "- Tests: http://localhost:5173/tests\n";
echo "- Current Affairs: http://localhost:5173/current-affairs\n";
echo "- Blogs: http://localhost:5173/blogs\n";
?>