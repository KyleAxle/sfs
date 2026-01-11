<?php
/**
 * Test actual registration flow to see what's happening
 * This simulates what happens when a user registers
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Actual Registration Flow Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .info { color: #1976d2; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

// Simulate registration data
$first_name = 'Test';
$last_name = 'Registration';
$email = 'test_reg_' . time() . '@example.com';
$password = 'test123456';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

echo "<div class='info'><h3>Test Registration Data:</h3></div>";
echo "<pre>";
echo "First Name: {$first_name}\n";
echo "Last Name: {$last_name}\n";
echo "Email: {$email}\n";
echo "Password: [hashed]\n";
echo "</pre>";

try {
    echo "<div class='info'>[1] Loading database connection...</div>";
    $pdo = require __DIR__ . '/config/db.php';
    echo "<div class='success'>✅ Database connection loaded</div>";
    
    // Step 1: Check if email exists (same as register_process.php)
    echo "<div class='info'>[2] Checking if email already exists...</div>";
    $stmt = $pdo->prepare("SELECT user_id FROM public.users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo "<div class='error'>❌ Email already exists (this shouldn't happen with unique timestamp)</div>";
        exit;
    }
    echo "<div class='success'>✅ Email is available</div>";
    
    // Step 2: Count users before
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM public.users");
    $beforeCount = (int)$stmt->fetch()['count'];
    echo "<div class='info'>Users before: <strong>{$beforeCount}</strong></div>";
    
    // Step 3: Try INSERT (same query as register_process.php)
    echo "<div class='info'>[3] Attempting INSERT using the EXACT query from register_process.php...</div>";
    echo "<pre>INSERT INTO public.users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)</pre>";
    
    $stmt = $pdo->prepare("INSERT INTO public.users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$first_name, $last_name, $email, $hashed_password]);
    
    echo "<div class='info'>Execute returned: " . ($result ? 'true' : 'false') . "</div>";
    echo "<div class='info'>Row count: " . $stmt->rowCount() . "</div>";
    
    if ($result && $stmt->rowCount() > 0) {
        echo "<div class='success'>✅ INSERT appeared successful!</div>";
        
        // Step 4: Verify user was saved
        echo "<div class='info'>[4] Verifying user was actually saved...</div>";
        $verifyStmt = $pdo->prepare("SELECT user_id, first_name, last_name, email FROM public.users WHERE email = ?");
        $verifyStmt->execute([$email]);
        $insertedUser = $verifyStmt->fetch();
        
        if ($insertedUser) {
            echo "<div class='success'>✅✅✅ User found in database!</div>";
            echo "<pre>";
            echo "User ID: {$insertedUser['user_id']}\n";
            echo "Name: {$insertedUser['first_name']} {$insertedUser['last_name']}\n";
            echo "Email: {$insertedUser['email']}\n";
            echo "</pre>";
            
            // Count after
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM public.users");
            $afterCount = (int)$stmt->fetch()['count'];
            echo "<div class='info'>Users after: <strong>{$afterCount}</strong> (was {$beforeCount})</div>";
            
            if ($afterCount > $beforeCount) {
                echo "<div class='success'>✅✅✅ SUCCESS! User count increased!</div>";
                echo "<div class='info'>Registration flow works correctly!</div>";
                
                // Keep the test user so you can verify in Supabase
                echo "<div class='info'>Test user kept in database for verification. Check Supabase Dashboard.</div>";
            } else {
                echo "<div class='error'>❌ WARNING: User found but count didn't increase!</div>";
            }
        } else {
            echo "<div class='error'>❌ ERROR: INSERT returned success but user not found!</div>";
            echo "<div class='error'>This suggests a transaction rollback or connection issue.</div>";
        }
    } else {
        echo "<div class='error'>❌ INSERT failed or returned 0 rows</div>";
    }
    
    // Test with just "users" (no schema prefix)
    echo "<div class='info'><h3>[5] Testing with just 'users' (no schema prefix)...</h3></div>";
    $testEmail2 = 'test_no_schema_' . time() . '@example.com';
    try {
        $stmt2 = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
        $result2 = $stmt2->execute([$first_name, $last_name, $testEmail2, $hashed_password]);
        
        if ($result2 && $stmt2->rowCount() > 0) {
            echo "<div class='success'>✅ INSERT with 'users' (no schema) also works!</div>";
            
            // Verify
            $verifyStmt2 = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $verifyStmt2->execute([$testEmail2]);
            if ($verifyStmt2->fetch()) {
                echo "<div class='success'>✅ User found with 'users' query too</div>";
            }
            
            // Clean up
            $deleteStmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
            $deleteStmt->execute([$testEmail2]);
            echo "<div class='info'>Test user 2 deleted</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>INSERT with 'users' failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error:</div>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<div class='error'>Error Code: " . $e->getCode() . "</div>";
    
    if (strpos($e->getMessage(), 'permission denied') !== false) {
        echo "<div class='error'><strong>Permission denied!</strong> Check RLS policies or user permissions.</div>";
    } elseif (strpos($e->getMessage(), 'column') !== false) {
        echo "<div class='error'><strong>Column error!</strong> Check table structure.</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ General Error:</div>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}

echo "<hr>";
echo "<p><strong>Conclusion:</strong> If this test works but registration doesn't, the issue is in the registration form or error handling, not the database query.</p>";
echo "<p><a href='register.html'>Try Registration</a> | <a href='check_rls_policies.php'>Check RLS</a></p>";
?>

