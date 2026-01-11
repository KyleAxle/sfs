<?php
/**
 * Test script to verify user registration INSERT works
 * This will help diagnose why registrations aren't saving to Supabase
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Registration Insert Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .info { color: #1976d2; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

try {
    echo "<div class='info'>[1] Loading database connection...</div>";
    $pdo = require __DIR__ . '/config/db.php';
    echo "<div class='success'>✅ Database connection loaded successfully</div>";
    
    echo "<div class='info'>[2] Checking users table structure...</div>";
    $stmt = $pdo->query("SELECT column_name, data_type, is_nullable 
                         FROM information_schema.columns 
                         WHERE table_name = 'users' 
                         ORDER BY ordinal_position");
    $columns = $stmt->fetchAll();
    echo "<div class='info'>Users table columns:</div>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo "  - {$col['column_name']} ({$col['data_type']}) " . 
             ($col['is_nullable'] === 'YES' ? '[nullable]' : '[required]') . "\n";
    }
    echo "</pre>";
    
    echo "<div class='info'>[3] Counting current users...</div>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    $beforeCount = (int)$result['count'];
    echo "<div class='info'>Current user count: <strong>{$beforeCount}</strong></div>";
    
    echo "<div class='info'>[4] Attempting to insert test user...</div>";
    $testEmail = 'test_' . time() . '@test.com';
    $testFirstName = 'Test';
    $testLastName = 'User';
    $testPassword = password_hash('test123', PASSWORD_DEFAULT);
    
    echo "<div class='info'>Test data:</div>";
    echo "<pre>";
    echo "  Email: {$testEmail}\n";
    echo "  First Name: {$testFirstName}\n";
    echo "  Last Name: {$testLastName}\n";
    echo "  Password: [hashed]\n";
    echo "</pre>";
    
    // Try the insert
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$testFirstName, $testLastName, $testEmail, $testPassword]);
    
    echo "<div class='info'>[5] Insert execute() returned: " . ($result ? 'true' : 'false') . "</div>";
    echo "<div class='info'>Row count: " . $stmt->rowCount() . "</div>";
    
    if ($result && $stmt->rowCount() > 0) {
        echo "<div class='success'>✅ INSERT appeared successful!</div>";
        
        echo "<div class='info'>[6] Verifying user was actually saved...</div>";
        $verifyStmt = $pdo->prepare("SELECT user_id, first_name, last_name, email FROM users WHERE email = ?");
        $verifyStmt->execute([$testEmail]);
        $insertedUser = $verifyStmt->fetch();
        
        if ($insertedUser) {
            echo "<div class='success'>✅ User found in database!</div>";
            echo "<pre>";
            echo "  User ID: {$insertedUser['user_id']}\n";
            echo "  Name: {$insertedUser['first_name']} {$insertedUser['last_name']}\n";
            echo "  Email: {$insertedUser['email']}\n";
            echo "</pre>";
            
            // Count again
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            $afterCount = (int)$result['count'];
            echo "<div class='info'>User count after insert: <strong>{$afterCount}</strong> (was {$beforeCount})</div>";
            
            if ($afterCount > $beforeCount) {
                echo "<div class='success'>✅✅✅ SUCCESS! User count increased from {$beforeCount} to {$afterCount}</div>";
                
                // Clean up test user
                echo "<div class='info'>[7] Cleaning up test user...</div>";
                $deleteStmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
                $deleteStmt->execute([$testEmail]);
                echo "<div class='success'>✅ Test user deleted</div>";
            } else {
                echo "<div class='error'>❌ WARNING: User was found but count didn't increase!</div>";
            }
        } else {
            echo "<div class='error'>❌ ERROR: Insert returned success but user not found in database!</div>";
            echo "<div class='error'>This suggests the transaction was rolled back or there's a connection issue.</div>";
        }
    } else {
        echo "<div class='error'>❌ INSERT failed or returned 0 rows</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error:</div>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<div class='error'>Error Code: " . $e->getCode() . "</div>";
    
    // Check for specific error types
    if (strpos($e->getMessage(), 'duplicate key') !== false) {
        echo "<div class='error'>This is a duplicate key error - the email already exists.</div>";
    } elseif (strpos($e->getMessage(), 'column') !== false && strpos($e->getMessage(), 'does not exist') !== false) {
        echo "<div class='error'>Column mismatch - the database schema doesn't match the code.</div>";
    } elseif (strpos($e->getMessage(), 'permission denied') !== false) {
        echo "<div class='error'>Permission denied - check your Supabase user permissions.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ General Error:</div>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

echo "<hr>";
echo "<p><a href='register.html'>← Back to Registration</a> | <a href='test_connection.php'>Test Connection</a></p>";
?>

