<?php
/**
 * Debug login issue - check database connection and users table
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Login Debug Information</h2>";

try {
    $pdo = require __DIR__ . '/config/db.php';
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Check users table structure
    echo "<h3>Users Table Structure:</h3>";
    $stmt = $pdo->query("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column Name</th><th>Data Type</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['column_name']}</td><td>{$col['data_type']}</td></tr>";
    }
    echo "</table>";
    
    // Check if password_hash column exists
    $hasPasswordHash = false;
    $hasPassword = false;
    foreach ($columns as $col) {
        if ($col['column_name'] === 'password_hash') $hasPasswordHash = true;
        if ($col['column_name'] === 'password') $hasPassword = true;
    }
    
    echo "<h3>Column Check:</h3>";
    echo "<p>Has 'password' column: " . ($hasPassword ? "✅ Yes" : "❌ No") . "</p>";
    echo "<p>Has 'password_hash' column: " . ($hasPasswordHash ? "✅ Yes" : "❌ No") . "</p>";
    
    // Count users
    echo "<h3>Users in Database:</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM public.users");
    $result = $stmt->fetch();
    echo "<p>Total users: <strong>{$result['count']}</strong></p>";
    
    // Show sample users (without passwords)
    if ($result['count'] > 0) {
        echo "<h3>Sample Users (first 5):</h3>";
        $stmt = $pdo->query("SELECT user_id, first_name, last_name, email FROM public.users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>{$user['first_name']}</td>";
            echo "<td>{$user['last_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test query that login_process.php uses
    echo "<h3>Testing Login Query:</h3>";
    $testEmail = $_GET['email'] ?? '';
    if ($testEmail) {
        echo "<p>Testing with email: <strong>$testEmail</strong></p>";
        
        // Try with password_hash first
        if ($hasPasswordHash) {
            $stmt = $pdo->prepare("SELECT * FROM public.users WHERE email = ?");
            $stmt->execute([$testEmail]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                echo "<p style='color: green;'>✅ User found with password_hash column</p>";
                echo "<pre>" . print_r(array_keys($row), true) . "</pre>";
            } else {
                echo "<p style='color: red;'>❌ User not found</p>";
            }
        }
        
        // Try with password
        if ($hasPassword) {
            $stmt = $pdo->prepare("SELECT * FROM public.users WHERE email = ?");
            $stmt->execute([$testEmail]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                echo "<p style='color: green;'>✅ User found with password column</p>";
                echo "<pre>" . print_r(array_keys($row), true) . "</pre>";
            } else {
                echo "<p style='color: red;'>❌ User not found</p>";
            }
        }
    } else {
        echo "<p>Add ?email=your@email.com to the URL to test a specific email</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

