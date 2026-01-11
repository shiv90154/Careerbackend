<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

header('Content-Type: application/json');

try {
    $db = (new Database())->getConnection();
    
    $tests = [];
    
    // Test 1: Check if tests table has data
    $testsQuery = "SELECT COUNT(*) as count FROM tests WHERE is_active = 1";
    $testsStmt = $db->query($testsQuery);
    $testsResult = $testsStmt->fetch();
    $tests['tests_count'] = $testsResult['count'];
    
    // Test 2: Check if current_affairs table has data
    $affairsQuery = "SELECT COUNT(*) as count FROM current_affairs";
    $affairsStmt = $db->query($affairsQuery);
    $affairsResult = $affairsStmt->fetch();
    $tests['current_affairs_count'] = $affairsResult['count'];
    
    // Test 3: Check if categories exist
    $categoriesQuery = "SELECT COUNT(*) as count FROM categories WHERE is_active = 1";
    $categoriesStmt = $db->query($categoriesQuery);
    $categoriesResult = $categoriesStmt->fetch();
    $tests['categories_count'] = $categoriesResult['count'];
    
    // Test 4: Get sample test data
    $sampleTestQuery = "SELECT id, title, type, is_premium, price FROM tests WHERE is_active = 1 LIMIT 3";
    $sampleTestStmt = $db->query($sampleTestQuery);
    $tests['sample_tests'] = $sampleTestStmt->fetchAll();
    
    // Test 5: Get sample current affairs data
    $sampleAffairsQuery = "SELECT id, title, category, is_premium, price FROM current_affairs LIMIT 3";
    $sampleAffairsStmt = $db->query($sampleAffairsQuery);
    $tests['sample_current_affairs'] = $sampleAffairsStmt->fetchAll();
    
    // Test 6: Check API endpoints exist
    $apiEndpoints = [
        'tests/index.php' => file_exists(__DIR__ . '/api/tests/index.php'),
        'tests/detail.php' => file_exists(__DIR__ . '/api/tests/detail.php'),
        'current-affairs/index.php' => file_exists(__DIR__ . '/api/current-affairs/index.php'),
        'current-affairs/detail.php' => file_exists(__DIR__ . '/api/current-affairs/detail.php'),
        'payments/purchase.php' => file_exists(__DIR__ . '/api/payments/purchase.php'),
        'payments/verify.php' => file_exists(__DIR__ . '/api/payments/verify.php')
    ];
    $tests['api_endpoints'] = $apiEndpoints;
    
    echo json_encode([
        'success' => true,
        'message' => 'API tests completed successfully',
        'tests' => $tests,
        'status' => [
            'database_connected' => true,
            'demo_data_loaded' => $tests['tests_count'] > 0 && $tests['current_affairs_count'] > 0,
            'api_endpoints_exist' => !in_array(false, $apiEndpoints),
            'ready_for_testing' => true
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'status' => [
            'database_connected' => false,
            'demo_data_loaded' => false,
            'api_endpoints_exist' => false,
            'ready_for_testing' => false
        ]
    ]);
}
?>