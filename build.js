const fs = require('fs');
const path = require('path');

try {
  // Create api directory if it doesn't exist
  if (!fs.existsSync('api')) {
    fs.mkdirSync('api', { recursive: true });
    console.log('Created api directory');
  }

  // Copy root PHP files to api directory
  const rootFiles = fs.readdirSync('.').filter(file => 
    file.endsWith('.php') && 
    file !== 'vercel.json' &&
    !file.startsWith('test_') &&
    !file.startsWith('debug_') &&
    !file.startsWith('check_') &&
    !file.startsWith('add_') &&
    !file.startsWith('create_') &&
    !file.startsWith('fix_') &&
    !file.startsWith('enable_') &&
    !file.startsWith('force_')
  );

  console.log(`Found ${rootFiles.length} root PHP files to copy`);
  rootFiles.forEach(file => {
    try {
      fs.copyFileSync(file, path.join('api', file));
      console.log(`✓ Copied ${file} to api/`);
    } catch (err) {
      console.error(`✗ Failed to copy ${file}:`, err.message);
    }
  });

  // Copy admin PHP files
  if (fs.existsSync('admin')) {
    if (!fs.existsSync('api/admin')) {
      fs.mkdirSync('api/admin', { recursive: true });
    }
    const adminFiles = fs.readdirSync('admin').filter(file => file.endsWith('.php'));
    console.log(`Found ${adminFiles.length} admin PHP files to copy`);
    adminFiles.forEach(file => {
      try {
        fs.copyFileSync(path.join('admin', file), path.join('api/admin', file));
        console.log(`✓ Copied admin/${file} to api/admin/`);
      } catch (err) {
        console.error(`✗ Failed to copy admin/${file}:`, err.message);
      }
    });
  }

  // Copy config directory
  if (fs.existsSync('config')) {
    if (!fs.existsSync('api/config')) {
      fs.mkdirSync('api/config', { recursive: true });
    }
    const configFiles = fs.readdirSync('config');
    console.log(`Found ${configFiles.length} config files to copy`);
    configFiles.forEach(file => {
      try {
        const srcPath = path.join('config', file);
        const destPath = path.join('api/config', file);
        if (fs.statSync(srcPath).isFile()) {
          fs.copyFileSync(srcPath, destPath);
          console.log(`✓ Copied config/${file} to api/config/`);
        }
      } catch (err) {
        console.error(`✗ Failed to copy config/${file}:`, err.message);
      }
    });
  }

  // Copy google-api-php-client directory (needed for Google OAuth)
  if (fs.existsSync('google-api-php-client')) {
    console.log('Copying google-api-php-client directory...');
    // Copy the entire directory structure
    function copyRecursive(src, dest) {
      if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
      }
      const entries = fs.readdirSync(src, { withFileTypes: true });
      for (const entry of entries) {
        const srcPath = path.join(src, entry.name);
        const destPath = path.join(dest, entry.name);
        if (entry.isDirectory()) {
          copyRecursive(srcPath, destPath);
        } else {
          fs.copyFileSync(srcPath, destPath);
        }
      }
    }
    copyRecursive('google-api-php-client', 'api/google-api-php-client');
    console.log('✓ Copied google-api-php-client to api/');
  }

  // Verify api directory has files
  const apiFiles = fs.readdirSync('api').filter(f => f.endsWith('.php'));
  console.log(`\nBuild complete! ${apiFiles.length} PHP files in api/ directory`);
  
  if (apiFiles.length === 0) {
    console.error('WARNING: No PHP files found in api/ directory!');
    process.exit(1);
  }
} catch (error) {
  console.error('Build failed:', error);
  process.exit(1);
}
