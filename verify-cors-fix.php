<?php
/**
 * CORS Fix Verification Script
 * Career Path Institute - Shimla
 */

echo "🔍 VERIFYING CORS FIX...\n\n";

// Test endpoints to verify
$testEndpoints = [
    'test-simple.php' => 'Simple CORS Test',
    'api/test-cors.php' => 'API CORS Test',
    'api/courses/index.php' => 'Courses API',
    'api/blogs/index.php' => 'Blogs API',
    'api/auth/csrf-token.php' => 'CSRF Token API'
];

$baseUrl = 'http://localhost/career-path-api/';
$origin = 'http://localhost:5173';

$results = [];

foreach ($testEndpoints as $endpoint => $name) {
    $url = $baseUrl . $endpoint;
    
    // Create context with headers
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Origin: $origin\r\n"
        ]
    ]);
    
    echo "Testing: $name ($endpoint)... ";
    
    try {
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            // Check if it's valid JSON
            $json = json_decode($response, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "✅ SUCCESS\n";
                $results[$endpoint] = [
                    'status' => 'success',
                    'response' => $json
                ];
            } else {
                echo "⚠️  WARNING (Not JSON)\n";
                $results[$endpoint] = [
                    'status' => 'warning',
                    'response' => substr($response, 0, 100) . '...'
                ];
            }
        } else {
            echo "❌ FAILED\n";
            $results[$endpoint] = [
                'status' => 'failed',
                'error' => 'No response'
            ];
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        $results[$endpoint] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 VERIFICATION RESULTS\n";
echo str_repeat("=", 60) . "\n";

$successCount = 0;
$totalCount = count($results);

foreach ($results as $endpoint => $result) {
    $status = $result['status'];
    $icon = $status === 'success' ? '✅' : ($status === 'warning' ? '⚠️' : '❌');
    
    echo "$icon $endpoint - " . strtoupper($status) . "\n";
    
    if ($status === 'success') {
        $successCount++;
        if (isset($result['response']['message'])) {
            echo "   Message: " . $result['response']['message'] . "\n";
        }
    } elseif (isset($result['error'])) {
        echo "   Error: " . $result['error'] . "\n";
    }
    
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "📈 SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Successful: $successCount/$totalCount\n";
echo "📊 Success Rate: " . round(($successCount / $totalCount) * 100, 1) . "%\n";

if ($successCount === $totalCount) {
    echo "\n🎉 ALL TESTS PASSED! CORS is working perfectly!\n";
    echo "\n🚀 Next Steps:\n";
    echo "1. Start your React app: npm run dev (in career folder)\n";
    echo "2. Visit: http://localhost:5173\n";
    echo "3. Check that data loads on all pages\n";
    echo "4. Test the CORS debug page: http://localhost:5173/cors-test\n";
} elseif ($successCount > 0) {
    echo "\n⚠️  PARTIAL SUCCESS - Some endpoints working\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check XAMPP Apache is running\n";
    echo "2. Verify database is created and populated\n";
    echo "3. Run: php setup-demo-data.php\n";
} else {
    echo "\n❌ NO TESTS PASSED - Check XAMPP setup\n";
    echo "\n🚨 Critical Issues:\n";
    echo "1. Ensure XAMPP Apache is running on port 80\n";
    echo "2. Verify files are in C:\\xampp\\htdocs\\career-path-api\\\n";
    echo "3. Check if localhost resolves correctly\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
?>