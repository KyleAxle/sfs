const fs = require('fs');
const path = require('path');

// Create api directory if it doesn't exist
if (!fs.existsSync('api')) {
  fs.mkdirSync('api', { recursive: true });
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

rootFiles.forEach(file => {
  fs.copyFileSync(file, path.join('api', file));
  console.log(`Copied ${file} to api/`);
});

// Copy admin PHP files
if (fs.existsSync('admin')) {
  if (!fs.existsSync('api/admin')) {
    fs.mkdirSync('api/admin', { recursive: true });
  }
  const adminFiles = fs.readdirSync('admin').filter(file => file.endsWith('.php'));
  adminFiles.forEach(file => {
    fs.copyFileSync(path.join('admin', file), path.join('api/admin', file));
    console.log(`Copied admin/${file} to api/admin/`);
  });
}

// Copy config directory
if (fs.existsSync('config')) {
  if (!fs.existsSync('api/config')) {
    fs.mkdirSync('api/config', { recursive: true });
  }
  const configFiles = fs.readdirSync('config');
  configFiles.forEach(file => {
    const srcPath = path.join('config', file);
    const destPath = path.join('api/config', file);
    if (fs.statSync(srcPath).isFile()) {
      fs.copyFileSync(srcPath, destPath);
      console.log(`Copied config/${file} to api/config/`);
    }
  });
}

console.log('Build complete!');
