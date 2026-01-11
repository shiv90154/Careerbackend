<?php
/**
 * Remove Duplicate CORS Headers Script
 * This will clean all API files of duplicate CORS headers
 */

echo "🧹 REMOVING DUPLICATE CORS HEADERS...\n\n";

function getAllPhpFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

$apiDir = __DIR__ . '/api';
$files = getAllPhpFiles($apiDir);

$corsPatterns = [
    '/header\s*\(\s*["\']Access-Control-Allow-Origin:.*?["\'].*?\)\s*;?\s*\n?/i',
    '/header\s*\(\s*["\']Access-Control-Allow-Methods:.*?["\'].*?\)\s*;?\s*\n?/i',
    '/header\s*\(\s*["\']Access-Control-Allow-Headers:.*?["\'].*?\)\s*;?\s*\n?/i',
    '/header\s*\(\s*["\']Access-Control-Allow-Credentials:.*?["\'].*?\)\s*;?\s*\n?/i',
    '/header\s*\(\s*["\']Access-Control-Max-Age:.*?["\'].*?\)\s*;?\s*\n?/i',
    '/header\s*\(\s*["\']Content-Type:\s*application\/json.*?["\'].*?\)\s*;?\s*\n?/i',
    '/\/\/ CORS Headers.*?\n/i',
    '/\/\/ Handle preflight.*?\n/i',
    '/if\s*\(\s*\$_SERVER\s*\[\s*["\']REQUEST_METHOD["\']\s*\]\s*===\s*["\']OPTIONS["\']\s*\)\s*\{.*?\}\s*\n?/s'
];

$cleanedCount = 0;
$skippedCount = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Remove all CORS-related headers and code
    foreach ($corsPatterns as $pattern) {
        $content = preg_replace($pattern, '', $content);
    }
    
    // Remove any remaining duplicate CORS comments
    $content = preg_replace('/\/\*.*?CORS.*?\*\/\s*\n?/s', '', $content);
    $content = preg_replace('/\/\/.*?CORS.*?\n/i', '', $content);
    
    // Clean up multiple empty lines
    $content = preg_replace('/\n\s*\n\s*\n/', "\n\n", $content);
    
    if ($content !== $originalContent) {
        if (file_put_contents($file, $content)) {
            echo "✅ Cleaned: " . basename($file) . "\n";
            $cleanedCount++;
        } else {
            echo "❌ Failed to clean: " . basename($file) . "\n";
        }
    } else {
        echo "⏭️  No CORS found: " . basename($file) . "\n";
        $skippedCount++;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 CORS CLEANUP COMPLETED!\n";
echo "✅ Cleaned files: $cleanedCount\n";
echo "⏭️  Skipped files: $skippedCount\n";
echo "📁 Total files processed: " . count($files) . "\n";
echo "\n🔍 Result: ALL CORS is now handled by includes/cors.php ONLY\n";
echo str_repeat("=", 50) . "\n";
?>