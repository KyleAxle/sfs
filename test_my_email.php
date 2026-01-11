<?php
/**
 * Test if a specific email exists in the database
 * Usage: test_my_email.php?email=your@email.com
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Email Lookup Test</h2>";

$testEmail = $_GET['email'] ?? $_POST['email'] ?? '';

if (empty($testEmail)) {
    echo "<form method='GET' style='margin: 20px 0;'>";
    echo "<label>Enter your email to test: <input type='email' name='email' required style='padding: 8px; width: 300px;'></label>";
    echo "<button type='submit' style='padding: 8px 16px; margin-left: 10px;'>Test Email</button>";
    echo "</form>";
    echo "<p>Or add ?email=your@email.com to the URL</p>";
    exit;
}

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    echo "<h3>Testing email: <strong>" . htmlspecialchars($testEmail) . "</strong></h3>";
    echo "<p>Email length: " . strlen($testEmail) . " characters</p>";
    echo "<p>Email after trim: <strong>'" . htmlspecialchars(trim($testEmail)) . "'</strong></p>";
    
    // Test the exact query from login_process.php
    $trimmedEmail = trim($testEmail);
    $stmt = $pdo->prepare("SELECT * FROM public.users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$trimmedEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #155724; margin-top: 0;'>✅ Email FOUND in database!</h3>";
        echo "<p><strong>User ID:</strong> {$row['user_id']}</p>";
        echo "<p><strong>Name:</strong> {$row['first_name']} {$row['last_name']}</p>";
        echo "<p><strong>Email in DB:</strong> {$row['email']}</p>";
        echo "<p><strong>Has password_hash:</strong> " . (isset($row['password_hash']) && !empty($row['password_hash']) ? "✅ Yes" : "❌ No") . "</p>";
        echo "<p style='color: green;'><strong>This email should work for login!</strong></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='color: #721c24; margin-top: 0;'>❌ Email NOT FOUND in database</h3>";
        echo "<p>This email does not exist in the database. This is why you're getting 'User not found' error.</p>";
        echo "</div>";
        
        // Show all emails in database
        echo "<h3>All Emails in Database:</h3>";
        $stmt = $pdo->query("SELECT email, first_name, last_name FROM public.users ORDER BY email");
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>Name</th></tr>";
        foreach ($allUsers as $user) {
            $highlight = (strtolower($user['email']) === strtolower($testEmail)) ? "style='background: yellow;'" : "";
            echo "<tr $highlight>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Tip:</strong> Make sure you're using the exact email address you registered with (check for typos, case differences, etc.)</p>";
    }
    
    // Test case variations
    echo "<h3>Testing Case Variations:</h3>";
    $variations = [
        strtolower($testEmail),
        strtoupper($testEmail),
        ucfirst($testEmail),
        $testEmail
    ];
    
    foreach ($variations as $variation) {
        $stmt = $pdo->prepare("SELECT email FROM public.users WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$variation]);
        $found = $stmt->fetch();
        $status = $found ? "✅ Found" : "❌ Not found";
        echo "<p><strong>'$variation'</strong>: $status</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='login.html'>Back to Login</a> | <a href='test_my_email.php'>Test Another Email</a></p>";

