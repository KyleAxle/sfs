<?php
/**
 * Web-accessible diagnostic page
 * Open in browser: http://localhost:8000/check_driver_web.php
 * This will show what PHP sees when accessed via web server
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>PostgreSQL Driver Diagnostic - Web</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .info { background: #e3f2fd; padding: 10px; border-left: 4px solid #2196F3; margin: 10px 0; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        ul { line-height: 1.8; }
        .section { margin: 20px 0; padding: 15px; background: #fafafa; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 PostgreSQL Driver Diagnostic (Web Server)</h1>
        <p>This page shows what PHP sees when accessed via your web server.</p>
        
        <div class="section">
            <h2>[1] PHP Version & Configuration</h2>
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
            <p><strong>PHP.ini Location:</strong> <?php echo php_ini_loaded_file(); ?></p>
            <p><strong>Additional .ini files:</strong> <?php echo implode(', ', php_ini_scanned_files() ?: ['(none)']); ?></p>
        </div>

        <div class="section">
            <h2>[2] PDO Extension</h2>
            <?php if (extension_loaded('PDO')): ?>
                <p class="success">✅ PDO is loaded</p>
                <p><strong>Available PDO drivers:</strong> <?php echo implode(', ', PDO::getAvailableDrivers()); ?></p>
                <?php if (in_array('pgsql', PDO::getAvailableDrivers())): ?>
                    <p class="success">✅ pgsql driver is available in PDO</p>
                <?php else: ?>
                    <p class="error">❌ pgsql driver is NOT available in PDO</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="error">❌ PDO is NOT loaded!</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>[3] PostgreSQL Extensions</h2>
            <?php
            $pdo_pgsql = extension_loaded('pdo_pgsql');
            $pgsql = extension_loaded('pgsql');
            ?>
            <?php if ($pdo_pgsql): ?>
                <p class="success">✅ pdo_pgsql extension is loaded</p>
            <?php else: ?>
                <p class="error">❌ pdo_pgsql extension is NOT loaded</p>
                <p class="warning">This is likely the cause of your "could not find driver" error!</p>
            <?php endif; ?>
            
            <?php if ($pgsql): ?>
                <p class="success">✅ pgsql extension is loaded</p>
            <?php else: ?>
                <p class="warning">⚠️ pgsql extension is NOT loaded (optional but recommended)</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>[4] .env File Check</h2>
            <?php
            $envPath = __DIR__ . '/.env';
            if (file_exists($envPath)):
            ?>
                <p class="success">✅ .env file exists</p>
                <?php
                $envContent = file_get_contents($envPath);
                if (strpos($envContent, 'YOUR_SUPABASE') !== false):
                ?>
                    <p class="warning">⚠️ .env file contains placeholder values</p>
                <?php else: ?>
                    <p class="success">✅ .env file appears to be configured</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="error">❌ .env file NOT found!</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>[5] Database Connection Test</h2>
            <?php
            if (!$pdo_pgsql):
            ?>
                <p class="error">❌ Cannot test connection - pdo_pgsql extension not loaded!</p>
                <div class="info">
                    <strong>SOLUTION:</strong>
                    <ol>
                        <li>Edit: <code><?php echo php_ini_loaded_file(); ?></code></li>
                        <li>Find: <code>;extension=pdo_pgsql</code></li>
                        <li>Remove the semicolon: <code>extension=pdo_pgsql</code></li>
                        <li>Also uncomment: <code>extension=pgsql</code></li>
                        <li>Save the file</li>
                        <li>Restart your web server (Apache/XAMPP)</li>
                        <li>Refresh this page</li>
                    </ol>
                </div>
            <?php
            elseif (!file_exists($envPath)):
            ?>
                <p class="error">❌ Cannot test connection - .env file missing!</p>
                <p>Run: <code>php create_env.php</code></p>
            <?php
            else:
                try {
                    require_once __DIR__ . '/config/env.php';
                    loadEnv($envPath);
                    require_once __DIR__ . '/config/db.php';
                    $pdo = require __DIR__ . '/config/db.php';
                    ?>
                    <p class="success">✅ Database connection successful!</p>
                    <p class="success">PostgreSQL driver is working correctly in web server context.</p>
                    <?php
                    // Test a simple query
                    $stmt = $pdo->query("SELECT version()");
                    $version = $stmt->fetch();
                    ?>
                    <p><strong>PostgreSQL Version:</strong> <?php echo htmlspecialchars(substr($version['version'], 0, 50)); ?>...</p>
                    <?php
                } catch (PDOException $e) {
                    ?>
                    <p class="error">❌ Connection failed!</p>
                    <p><strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?></p>
                    <?php
                    if (strpos($e->getMessage(), 'could not find driver') !== false):
                    ?>
                        <div class="info">
                            <strong>This error means pdo_pgsql is not enabled in your web server's PHP!</strong>
                            <p>Even though CLI PHP might have it enabled, your web server (Apache/XAMPP) might be using a different php.ini file or the extension isn't loaded.</p>
                            <p><strong>Fix:</strong> Edit <?php echo php_ini_loaded_file(); ?> and uncomment the PostgreSQL extensions, then restart Apache.</p>
                        </div>
                    <?php
                    endif;
                } catch (Exception $e) {
                    ?>
                    <p class="error">❌ Error: <?php echo htmlspecialchars($e->getMessage()); ?></p>
                    <?php
                }
            endif;
            ?>
        </div>

        <div class="section">
            <h2>📋 Summary</h2>
            <?php if ($pdo_pgsql && file_exists($envPath)): ?>
                <p class="success">✅ Everything looks good! Your PostgreSQL driver should be working.</p>
                <p>If you're still getting errors, try:</p>
                <ul>
                    <li>Clear your browser cache</li>
                    <li>Restart your web server</li>
                    <li>Check the specific page that's giving you the error</li>
                </ul>
            <?php else: ?>
                <p class="error">❌ Issues found. Please fix them using the instructions above.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>🔧 Quick Actions</h2>
            <ul>
                <li><a href="test_connection.php">Test Connection Page</a></li>
                <li><a href="check_driver.php">CLI Diagnostic (download)</a></li>
                <li><a href="run_project_fixed.bat" download>Download Run Script</a></li>
            </ul>
        </div>
    </div>
</body>
</html>

