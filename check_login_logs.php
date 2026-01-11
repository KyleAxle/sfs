<?php
/**
 * Check login error logs to see what's happening
 * This will show the last few login attempts
 */
echo "<h2>Login Debug Logs</h2>";
echo "<p>Check your PHP error log file to see detailed login attempts.</p>";
echo "<p>Common locations:</p>";
echo "<ul>";
echo "<li>Windows XAMPP: C:\\xampp\\apache\\logs\\error.log</li>";
echo "<li>Windows WAMP: C:\\wamp64\\logs\\php_error.log</li>";
echo "<li>Linux: /var/log/apache2/error.log or /var/log/php/error.log</li>";
echo "</ul>";

echo "<h3>To enable error logging, add this to your login_process.php temporarily:</h3>";
echo "<pre>";
echo "ini_set('log_errors', 1);\n";
echo "ini_set('error_log', __DIR__ . '/login_errors.log');\n";
echo "</pre>";

echo "<h3>Or check PHP error log location:</h3>";
echo "<pre>";
echo "<?php phpinfo(); ?>";
echo "</pre>";
echo "<p>Look for 'error_log' in the output.</p>";

// Try to read a local log file if it exists
$logFile = __DIR__ . '/login_errors.log';
if (file_exists($logFile)) {
    echo "<h3>Local login_errors.log file:</h3>";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20); // Last 20 lines
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars(implode('', $lastLines));
    echo "</pre>";
} else {
    echo "<p>No local login_errors.log file found.</p>";
}

