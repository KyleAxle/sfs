<?php
/**
 * Check Row Level Security (RLS) policies and permissions on users table
 * This helps diagnose if RLS is blocking INSERT operations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Row Level Security (RLS) Check</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .warning { color: #f57c00; background: #fff3e0; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .info { color: #1976d2; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #2563eb; color: white; }
</style>";

try {
    $pdo = require __DIR__ . '/config/db.php';
    echo "<div class='success'>✅ Database connection loaded</div>";
    
    // Check if RLS is enabled on users table
    echo "<div class='info'><h3>[1] Checking if RLS is enabled on public.users...</h3></div>";
    $stmt = $pdo->query("
        SELECT tablename, rowsecurity 
        FROM pg_tables 
        WHERE schemaname = 'public' 
        AND tablename = 'users'
    ");
    $rlsInfo = $stmt->fetch();
    
    if ($rlsInfo) {
        if ($rlsInfo['rowsecurity'] === 't' || $rlsInfo['rowsecurity'] === true) {
            echo "<div class='warning'>⚠️ RLS (Row Level Security) is ENABLED on public.users table</div>";
            echo "<div class='warning'><strong>This could be blocking INSERT operations!</strong></div>";
            echo "<div class='info'>If RLS is enabled, you need policies that allow INSERT operations.</div>";
        } else {
            echo "<div class='success'>✅ RLS is NOT enabled on public.users (this is fine for basic operations)</div>";
        }
    }
    
    // Check for RLS policies
    echo "<div class='info'><h3>[2] Checking RLS policies on public.users...</h3></div>";
    $stmt = $pdo->query("
        SELECT 
            schemaname,
            tablename,
            policyname,
            permissive,
            roles,
            cmd,
            qual,
            with_check
        FROM pg_policies 
        WHERE schemaname = 'public' 
        AND tablename = 'users'
    ");
    $policies = $stmt->fetchAll();
    
    if (count($policies) > 0) {
        echo "<div class='info'>Found " . count($policies) . " policy/policies:</div>";
        echo "<table>";
        echo "<tr><th>Policy Name</th><th>Command</th><th>Roles</th><th>Permissive</th></tr>";
        foreach ($policies as $policy) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($policy['policyname']) . "</td>";
            echo "<td>" . htmlspecialchars($policy['cmd'] ?? 'ALL') . "</td>";
            echo "<td>" . htmlspecialchars($policy['roles'] ?? 'public') . "</td>";
            echo "<td>" . htmlspecialchars($policy['permissive'] ?? 'PERMISSIVE') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if there's an INSERT policy
        $hasInsertPolicy = false;
        foreach ($policies as $policy) {
            $cmd = strtoupper($policy['cmd'] ?? '');
            if ($cmd === 'INSERT' || $cmd === 'ALL') {
                $hasInsertPolicy = true;
                break;
            }
        }
        
        if (!$hasInsertPolicy) {
            echo "<div class='error'>❌ No INSERT policy found! This will block user registration.</div>";
            echo "<div class='info'><strong>Solution:</strong> You need to create an INSERT policy or disable RLS.</div>";
        } else {
            echo "<div class='success'>✅ INSERT policy exists</div>";
        }
    } else {
        echo "<div class='info'>No RLS policies found on public.users</div>";
        if ($rlsInfo && ($rlsInfo['rowsecurity'] === 't' || $rlsInfo['rowsecurity'] === true)) {
            echo "<div class='error'>❌ RLS is enabled but NO policies exist - this will block ALL operations!</div>";
        }
    }
    
    // Check table permissions
    echo "<div class='info'><h3>[3] Checking table permissions...</h3></div>";
    $stmt = $pdo->query("
        SELECT 
            grantee,
            privilege_type
        FROM information_schema.role_table_grants 
        WHERE table_schema = 'public' 
        AND table_name = 'users'
        AND grantee = 'postgres'
    ");
    $permissions = $stmt->fetchAll();
    
    if (count($permissions) > 0) {
        echo "<div class='success'>Table permissions for postgres user:</div>";
        echo "<ul>";
        foreach ($permissions as $perm) {
            echo "<li>" . htmlspecialchars($perm['privilege_type']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<div class='warning'>⚠️ Could not verify table permissions</div>";
    }
    
    // Test INSERT permission
    echo "<div class='info'><h3>[4] Testing INSERT permission...</h3></div>";
    try {
        $testEmail = 'test_rls_' . time() . '@test.com';
        $testStmt = $pdo->prepare("INSERT INTO public.users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
        $testResult = $testStmt->execute(['Test', 'RLS', $testEmail, password_hash('test', PASSWORD_DEFAULT)]);
        
        if ($testResult) {
            echo "<div class='success'>✅ INSERT test successful!</div>";
            
            // Clean up
            $deleteStmt = $pdo->prepare("DELETE FROM public.users WHERE email = ?");
            $deleteStmt->execute([$testEmail]);
            echo "<div class='info'>Test record deleted</div>";
        } else {
            echo "<div class='error'>❌ INSERT test failed (but no exception thrown)</div>";
        }
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        echo "<div class='error'>❌ INSERT test FAILED with error:</div>";
        echo "<pre>" . htmlspecialchars($errorMsg) . "</pre>";
        
        if (strpos($errorMsg, 'permission denied') !== false || strpos($errorMsg, 'policy') !== false) {
            echo "<div class='error'><strong>This is likely an RLS policy issue!</strong></div>";
            echo "<div class='info'><strong>Solution:</strong> Go to Supabase Dashboard → Authentication → Policies and create an INSERT policy for public.users</div>";
        }
    }
    
    // Summary
    echo "<div class='info'><h3>Summary:</h3></div>";
    echo "<ul>";
    if ($rlsInfo && ($rlsInfo['rowsecurity'] === 't' || $rlsInfo['rowsecurity'] === true)) {
        echo "<li>⚠️ RLS is enabled - make sure you have INSERT policies</li>";
    } else {
        echo "<li>✅ RLS is not enabled - basic operations should work</li>";
    }
    echo "<li>Realtime features are NOT required for INSERT operations</li>";
    echo "<li>Realtime is only for live updates/subscriptions</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> The 'Realtime Users Enabler' in Supabase is for live subscriptions, not for basic INSERT operations. If registration isn't working, check RLS policies instead.</p>";
echo "<p><a href='test_connection.php'>Test Connection</a> | <a href='register.html'>Registration</a></p>";
?>

