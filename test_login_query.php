<?php
/**
 * Test the exact login query to see what's happening
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Login Query Test</h2>";

try {
    $pdo = require __DIR__ . '/config/db.php';
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Get a real email from the database
    $stmt = $pdo->query("SELECT email FROM public.users LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "<p style='color: red;'>❌ No users found in database</p>";
        exit;
    }
    
    $testEmail = $testUser['email'];
    echo "<h3>Testing with email from database: <strong>$testEmail</strong></h3>";
    
    // Test the exact query from login_process.php
    echo "<h3>Test 1: Query with public.users and LOWER()</h3>";
    $stmt = $pdo->prepare("SELECT * FROM public.users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$testEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "<p style='color: green;'>✅ User found!</p>";
        echo "<p>User ID: {$row['user_id']}</p>";
        echo "<p>Name: {$row['first_name']} {$row['last_name']}</p>";
        echo "<p>Email: {$row['email']}</p>";
        echo "<p>Has password_hash: " . (isset($row['password_hash']) && !empty($row['password_hash']) ? "✅ Yes" : "❌ No") . "</p>";
    } else {
        echo "<p style='color: red;'>❌ User NOT found with this query</p>";
    }
    
    // Test with exact email match (case sensitive)
    echo "<h3>Test 2: Query with exact email match (case sensitive)</h3>";
    $stmt = $pdo->prepare("SELECT * FROM public.users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "<p style='color: green;'>✅ User found with exact match!</p>";
    } else {
        echo "<p style='color: red;'>❌ User NOT found with exact match</p>";
    }
    
    // Test without schema prefix
    echo "<h3>Test 3: Query without public schema prefix</h3>";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$testEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "<p style='color: green;'>✅ User found without schema prefix!</p>";
    } else {
        echo "<p style='color: red;'>❌ User NOT found without schema prefix</p>";
    }
    
    // Show all emails in database for comparison
    echo "<h3>All Emails in Database:</h3>";
    $stmt = $pdo->query("SELECT email FROM public.users ORDER BY email");
    $allEmails = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($allEmails as $email) {
        echo "<li>$email</li>";
    }
    echo "</ul>";
    
    // Test with a sample POST simulation
    echo "<h3>Test 4: Simulating POST data</h3>";
    $_POST['username_email'] = $testEmail;
    $_POST['password'] = 'test123';
    
    $username_email = trim($_POST['username_email'] ?? '');
    echo "<p>POST username_email (trimmed): <strong>'$username_email'</strong></p>";
    echo "<p>Length: " . strlen($username_email) . " characters</p>";
    
    $stmt = $pdo->prepare("SELECT * FROM public.users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$username_email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "<p style='color: green;'>✅ Query works with simulated POST data!</p>";
    } else {
        echo "<p style='color: red;'>❌ Query failed with simulated POST data</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

