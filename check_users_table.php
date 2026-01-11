<?php
/**
 * Check which users table is being used and verify schema
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Users Table Schema Check</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .info { color: #1976d2; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #2563eb; color: white; }
</style>";

try {
    $pdo = require __DIR__ . '/config/db.php';
    echo "<div class='success'>✅ Database connection loaded</div>";
    
    // Check what schema is being used by default
    echo "<div class='info'><h3>Current Search Path:</h3></div>";
    $stmt = $pdo->query("SHOW search_path");
    $searchPath = $stmt->fetch();
    echo "<pre>" . htmlspecialchars($searchPath['search_path'] ?? 'Not set') . "</pre>";
    
    // Check if public.users exists
    echo "<div class='info'><h3>Checking public.users table...</h3></div>";
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        ORDER BY ordinal_position
    ");
    $publicColumns = $stmt->fetchAll();
    
    if (count($publicColumns) > 0) {
        echo "<div class='success'>✅ public.users table exists with " . count($publicColumns) . " columns</div>";
        echo "<table>";
        echo "<tr><th>Column Name</th><th>Data Type</th><th>Nullable</th></tr>";
        foreach ($publicColumns as $col) {
            echo "<tr><td>{$col['column_name']}</td><td>{$col['data_type']}</td><td>{$col['is_nullable']}</td></tr>";
        }
        echo "</table>";
        
        // Check if password_hash column exists
        $hasPasswordHash = false;
        $hasPassword = false;
        foreach ($publicColumns as $col) {
            if ($col['column_name'] === 'password_hash') $hasPasswordHash = true;
            if ($col['column_name'] === 'password') $hasPassword = true;
        }
        
        echo "<div class='info'>";
        if ($hasPasswordHash) {
            echo "✅ Column 'password_hash' exists";
        } else {
            echo "❌ Column 'password_hash' does NOT exist";
        }
        echo "<br>";
        if ($hasPassword) {
            echo "✅ Column 'password' exists";
        } else {
            echo "❌ Column 'password' does NOT exist";
        }
        echo "</div>";
        
        // Count users in public.users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM public.users");
        $result = $stmt->fetch();
        echo "<div class='info'>Users in public.users: <strong>{$result['count']}</strong></div>";
    } else {
        echo "<div class='error'>❌ public.users table does NOT exist!</div>";
    }
    
    // Check if auth.users exists (Supabase Auth table)
    echo "<div class='info'><h3>Checking auth.users table (Supabase Auth)...</h3></div>";
    try {
        $stmt = $pdo->query("
            SELECT column_name, data_type, is_nullable 
            FROM information_schema.columns 
            WHERE table_schema = 'auth' 
            AND table_name = 'users' 
            ORDER BY ordinal_position
            LIMIT 10
        ");
        $authColumns = $stmt->fetchAll();
        
        if (count($authColumns) > 0) {
            echo "<div class='info'>⚠️ auth.users table exists (Supabase Auth table)</div>";
            echo "<div class='info'>This is NOT the table your app should use!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='info'>auth.users table not accessible (this is normal)</div>";
    }
    
    // Test query without schema prefix
    echo "<div class='info'><h3>Testing query without schema prefix...</h3></div>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        echo "<div class='info'>Query 'SELECT FROM users' (no schema) returned: <strong>{$result['count']}</strong> rows</div>";
        echo "<div class='info'>This shows which table is being used by default</div>";
    } catch (Exception $e) {
        echo "<div class='error'>Query failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<p><a href='test_connection.php'>Test Connection</a> | <a href='register.html'>Registration</a></p>";
?>

