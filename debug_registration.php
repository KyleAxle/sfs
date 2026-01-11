<?php
/**
 * Debug registration - shows what happens when you submit the form
 * Use this to see if the form is actually submitting and what errors occur
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Registration Debug Tool</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .info { color: #1976d2; background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    .form-box { background: white; padding: 20px; border-radius: 8px; max-width: 500px; margin: 20px 0; }
    input { width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
    button { background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
</style>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='info'><h3>Form Submitted!</h3></div>";
    echo "<pre>";
    echo "POST Data:\n";
    print_r($_POST);
    echo "</pre>";
    
    // Try the registration
    echo "<div class='info'><h3>Attempting Registration...</h3></div>";
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        echo "<div class='error'>❌ Missing required fields</div>";
    } else {
        try {
            $pdo = require __DIR__ . '/config/db.php';
            
            // Check if email exists
            $stmt = $pdo->prepare("SELECT user_id FROM public.users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo "<div class='error'>❌ Email already exists</div>";
            } else {
                // Try insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO public.users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
                $result = $stmt->execute([$first_name, $last_name, $email, $hashed_password]);
                
                if ($result && $stmt->rowCount() > 0) {
                    // Verify
                    $verifyStmt = $pdo->prepare("SELECT user_id FROM public.users WHERE email = ?");
                    $verifyStmt->execute([$email]);
                    $user = $verifyStmt->fetch();
                    
                    if ($user) {
                        echo "<div class='success'>✅✅✅ Registration SUCCESSFUL!</div>";
                        echo "<div class='success'>User ID: {$user['user_id']}</div>";
                        echo "<div class='info'>Check your Supabase Dashboard → Table Editor → users table</div>";
                    } else {
                        echo "<div class='error'>❌ INSERT succeeded but user not found (transaction rollback?)</div>";
                    }
                } else {
                    echo "<div class='error'>❌ INSERT failed (result: " . ($result ? 'true' : 'false') . ", rows: " . $stmt->rowCount() . ")</div>";
                }
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    echo "<hr>";
}

// Show form
echo "<div class='form-box'>";
echo "<h3>Test Registration Form</h3>";
echo "<form method='POST'>";
echo "<input type='text' name='first_name' placeholder='First Name' required><br>";
echo "<input type='text' name='last_name' placeholder='Last Name' required><br>";
echo "<input type='email' name='email' placeholder='Email' required><br>";
echo "<input type='password' name='password' placeholder='Password' required><br>";
echo "<button type='submit'>Test Register</button>";
echo "</form>";
echo "</div>";

// Show current users
echo "<div class='info'><h3>Current Users in Database:</h3></div>";
try {
    $pdo = require __DIR__ . '/config/db.php';
    $stmt = $pdo->query("SELECT user_id, first_name, last_name, email FROM public.users ORDER BY user_id DESC LIMIT 10");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['user_id']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>No users found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>Error loading users: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>Fill out the form above and submit</li>";
echo "<li>Check if the user appears in the 'Current Users' table</li>";
echo "<li>Check your Supabase Dashboard → Table Editor → users table</li>";
echo "<li>If it works here but not in register.html, the issue is in the form submission</li>";
echo "</ol>";
echo "<p><a href='register.html'>Go to Actual Registration Form</a></p>";
?>

