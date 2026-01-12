<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head><title>Vercel PHP Test</title></head>
<body>
<h1>Vercel PHP Test</h1>
<p><strong>PHP is working!</strong></p>
<p>Current directory: <?php echo __DIR__; ?></p>
<p>Config exists: <?php echo file_exists(__DIR__ . '/config/db.php') ? 'YES' : 'NO'; ?></p>
<p>Config/env.php exists: <?php echo file_exists(__DIR__ . '/config/env.php') ? 'YES' : 'NO'; ?></p>
<h2>Environment Variables:</h2>
<ul>
<li>SUPABASE_DB_HOST: <?php echo getenv('SUPABASE_DB_HOST') ?: 'NOT SET'; ?></li>
<li>SUPABASE_DB_USER: <?php echo getenv('SUPABASE_DB_USER') ?: 'NOT SET'; ?></li>
<li>SUPABASE_DB_NAME: <?php echo getenv('SUPABASE_DB_NAME') ?: 'NOT SET'; ?></li>
</ul>
<h2>PHP Info:</h2>
<?php phpinfo(); ?>
</body>
</html>
