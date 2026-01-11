<?php
/**
 * Diagnostic script to check PostgreSQL driver status
 * Run: php check_driver.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "========================================\n";
echo "PostgreSQL Driver Diagnostic Tool\n";
echo "========================================\n\n";

// Check 1: PHP Version
echo "[1] PHP Version: " . phpversion() . "\n\n";

// Check 2: PDO Extension
echo "[2] Checking PDO extension...\n";
if (extension_loaded('PDO')) {
    echo "    ✅ PDO is loaded\n";
    echo "    Available PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
} else {
    echo "    ❌ PDO is NOT loaded!\n";
}
echo "\n";

// Check 3: PostgreSQL Extensions
echo "[3] Checking PostgreSQL extensions...\n";
if (extension_loaded('pdo_pgsql')) {
    echo "    ✅ pdo_pgsql is loaded\n";
} else {
    echo "    ❌ pdo_pgsql is NOT loaded!\n";
    echo "    This is the problem! Enable it in php.ini\n";
}

if (extension_loaded('pgsql')) {
    echo "    ✅ pgsql is loaded\n";
} else {
    echo "    ⚠️  pgsql is NOT loaded (optional but recommended)\n";
}
echo "\n";

// Check 4: .env file
echo "[4] Checking .env file...\n";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "    ✅ .env file exists\n";
    $envContent = file_get_contents($envPath);
    if (strpos($envContent, 'YOUR_SUPABASE') !== false) {
        echo "    ⚠️  .env file contains placeholder values!\n";
        echo "    You need to update it with your actual Supabase credentials.\n";
    } else {
        echo "    ✅ .env file appears to be configured\n";
    }
} else {
    echo "    ❌ .env file NOT found!\n";
    echo "    Run: php create_env.php to create it.\n";
}
echo "\n";

// Check 5: Try to load environment
echo "[5] Testing environment loading...\n";
if (file_exists(__DIR__ . '/config/env.php')) {
    require_once __DIR__ . '/config/env.php';
    loadEnv($envPath);
    echo "    ✅ Environment loader works\n";
} else {
    echo "    ❌ config/env.php not found!\n";
}
echo "\n";

// Check 6: Test connection (if possible)
echo "[6] Testing database connection...\n";
if (!extension_loaded('pdo_pgsql')) {
    echo "    ❌ Cannot test connection - pdo_pgsql extension not loaded!\n";
    echo "\n";
    echo "========================================\n";
    echo "SOLUTION:\n";
    echo "========================================\n";
    echo "1. Find your php.ini file:\n";
    echo "   Run: php --ini\n";
    echo "\n";
    echo "2. Edit php.ini and uncomment these lines:\n";
    echo "   extension=pdo_pgsql\n";
    echo "   extension=pgsql\n";
    echo "\n";
    echo "3. Restart your web server or PHP CLI\n";
    echo "\n";
    exit(1);
}

if (!file_exists($envPath)) {
    echo "    ❌ Cannot test connection - .env file missing!\n";
    echo "    Run: php create_env.php\n";
    exit(1);
}

try {
    require_once __DIR__ . '/config/db.php';
    $pdo = require __DIR__ . '/config/db.php';
    echo "    ✅ Database connection successful!\n";
    echo "    PostgreSQL driver is working correctly.\n";
} catch (PDOException $e) {
    echo "    ❌ Connection failed!\n";
    echo "    Error: " . $e->getMessage() . "\n";
    echo "\n";
    if (strpos($e->getMessage(), 'could not find driver') !== false) {
        echo "    This error means pdo_pgsql is not enabled!\n";
        echo "    Even though php -m shows it, it might not be loaded in this context.\n";
    }
}

echo "\n";
echo "========================================\n";
echo "Diagnostic Complete\n";
echo "========================================\n";

