<?php
/**
 * Test script to diagnose profile upload issues
 * Access this file in your browser to check upload configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Profile Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .test-box { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .success { color: #059669; }
        .error { color: #dc2626; }
        .warning { color: #d97706; }
        .info { color: #2563eb; }
        pre { background: #f3f4f6; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Profile Upload Configuration Test</h1>";

// Test 1: Check upload directory
echo "<div class='test-box'>";
echo "<h2>1. Upload Directory Check</h2>";
$uploadDir = __DIR__ . '/uploads/profile_pictures/';
if (is_dir($uploadDir)) {
    echo "<p class='success'>✓ Upload directory exists: <code>{$uploadDir}</code></p>";
} else {
    echo "<p class='error'>✗ Upload directory does not exist: <code>{$uploadDir}</code></p>";
    echo "<p class='info'>Attempting to create directory...</p>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "<p class='success'>✓ Directory created successfully</p>";
    } else {
        echo "<p class='error'>✗ Failed to create directory</p>";
    }
}

if (is_dir($uploadDir)) {
    if (is_writable($uploadDir)) {
        echo "<p class='success'>✓ Directory is writable</p>";
    } else {
        echo "<p class='error'>✗ Directory is NOT writable</p>";
        echo "<p class='warning'>Try: chmod 755 {$uploadDir}</p>";
    }
}
echo "</div>";

// Test 2: PHP Upload Settings
echo "<div class='test-box'>";
echo "<h2>2. PHP Upload Configuration</h2>";
echo "<pre>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "\n";
echo "</pre>";
echo "</div>";

// Test 3: Fileinfo Extension
echo "<div class='test-box'>";
echo "<h2>3. Fileinfo Extension</h2>";
if (function_exists('finfo_open')) {
    echo "<p class='success'>✓ Fileinfo extension is available</p>";
} else {
    echo "<p class='warning'>⚠ Fileinfo extension is not available (will use fallback method)</p>";
}
echo "</div>";

// Test 4: Database Column Check
echo "<div class='test-box'>";
echo "<h2>4. Database Column Check</h2>";
try {
    $pdo = require __DIR__ . '/config/db.php';
    $stmt = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users'
        AND column_name = 'profile_picture'
    ");
    $column = $stmt->fetch();
    if ($column) {
        echo "<p class='success'>✓ profile_picture column exists in users table</p>";
    } else {
        echo "<p class='error'>✗ profile_picture column does NOT exist in users table</p>";
        echo "<p class='info'>Run: <code>php add_profile_picture_column.php</code> or execute <code>add_profile_picture_column.sql</code> in Supabase</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 5: Test File Write
echo "<div class='test-box'>";
echo "<h2>5. Test File Write</h2>";
if (is_dir($uploadDir) && is_writable($uploadDir)) {
    $testFile = $uploadDir . 'test_' . time() . '.txt';
    if (file_put_contents($testFile, 'test')) {
        echo "<p class='success'>✓ Can write files to upload directory</p>";
        unlink($testFile);
    } else {
        echo "<p class='error'>✗ Cannot write files to upload directory</p>";
    }
} else {
    echo "<p class='error'>✗ Cannot test file write (directory not writable)</p>";
}
echo "</div>";

// Test 6: Session Check
echo "<div class='test-box'>";
echo "<h2>6. Session Check</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✓ User is logged in (User ID: " . $_SESSION['user_id'] . ")</p>";
} else {
    echo "<p class='warning'>⚠ User is not logged in (this is normal if accessed directly)</p>";
}
echo "</div>";

echo "</body></html>";
?>

