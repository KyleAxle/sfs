# 🚀 Vercel Deployment Guide

This guide will help you deploy your PHP application to Vercel so other users can access it.

## Prerequisites

1. A [Vercel account](https://vercel.com/signup) (free tier works!)
2. Your code pushed to a Git repository (GitHub, GitLab, or Bitbucket)
3. Your Supabase database credentials
4. Your Google OAuth credentials (if using Google login)

## Step 1: Prepare Your Repository

### 1.1 Push to Git

**📚 Don't know how to use Git?** See the complete guide: **[GITHUB_SETUP_GUIDE.md](./GITHUB_SETUP_GUIDE.md)**

If you already know Git, push your code to GitHub/GitLab/Bitbucket:

```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git push -u origin main
```

**For beginners:** The detailed step-by-step guide includes:
- Creating a GitHub account
- Installing Git
- Setting up your repository
- Pushing your code
- Troubleshooting common issues

### 1.2 Verify Files

Make sure these files are in your repository:
- ✅ `vercel.json` (configuration file)
- ✅ `.vercelignore` (files to exclude)
- ✅ All your PHP and HTML files
- ✅ `config/` directory
- ✅ `assets/` directory
- ✅ `google-api-php-client/` directory (or use Composer)

**Important:** Do NOT commit your `.env` file! It should be in `.gitignore`.

## Step 2: Install Vercel CLI (Optional but Recommended)

You can deploy via the web interface, but CLI is faster:

```bash
npm install -g vercel
```

## Step 3: Deploy to Vercel

### Option A: Deploy via Web Interface (Easiest)

1. Go to [vercel.com](https://vercel.com)
2. Click **"Add New Project"**
3. Import your Git repository
4. Vercel will auto-detect your project
5. Click **"Deploy"**

### Option B: Deploy via CLI

```bash
# Login to Vercel
vercel login

# Deploy (first time)
vercel

# Follow the prompts:
# - Set up and deploy? Yes
# - Which scope? Your account
# - Link to existing project? No
# - Project name? sfs-appointment-system (or your choice)
# - Directory? ./
```

## Step 4: Configure Environment Variables

**This is critical!** You need to add your environment variables in Vercel:

### 4.1 Access Environment Variables

1. Go to your project on Vercel dashboard
2. Click **Settings** → **Environment Variables**

### 4.2 Add These Variables

Add all variables from your `.env` file:

```
SUPABASE_DB_HOST=aws-1-ap-southeast-1.pooler.supabase.com
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres.ndnoevxzgczvyghaktxn
SUPABASE_DB_PASSWORD=your_actual_password_here
SUPABASE_DB_SSLMODE=require

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://your-project.vercel.app/google_callback.php

GROQ_API_KEY=your_groq_api_key (optional)
USE_PHP_FALLBACK=false
```

**Important Notes:**
- Replace `GOOGLE_REDIRECT_URI` with your actual Vercel URL after deployment
- Make sure to add these for **Production**, **Preview**, and **Development** environments
- Never share your actual passwords/keys publicly

### 4.3 Set Up Google OAuth (If Using Google Login)

**📚 Need detailed help?** See the complete beginner-friendly guide: **[GOOGLE_OAUTH_VERCEL_SETUP.md](./GOOGLE_OAUTH_VERCEL_SETUP.md)**

**Quick steps:**
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create OAuth 2.0 credentials (see detailed guide above)
3. Add authorized redirect URI: `https://your-project.vercel.app/google_callback.php`
4. Add the credentials to Vercel environment variables (see Step 4.2 above)

## Step 5: Redeploy After Adding Environment Variables

After adding environment variables, you need to redeploy:

1. Go to **Deployments** tab
2. Click the **"..."** menu on the latest deployment
3. Click **"Redeploy"**

Or via CLI:
```bash
vercel --prod
```

## Step 6: Test Your Deployment

1. Visit your Vercel URL: `https://your-project.vercel.app`
2. Test the login page: `https://your-project.vercel.app/login.html`
3. Test registration: `https://your-project.vercel.app/register.html`
4. Test database connection by trying to register a user

## Common Issues & Solutions

### Issue 1: "Database Connection Failed"

**Solution:**
- Verify all Supabase environment variables are set correctly in Vercel
- Check that your Supabase project is active
- Ensure `SUPABASE_DB_SSLMODE=require` is set

### Issue 2: "Google OAuth Not Working"

**Solution:**
- Update Google Cloud Console with your Vercel URL
- Update `GOOGLE_REDIRECT_URI` environment variable in Vercel
- Make sure both Client ID and Secret are set

### Issue 3: "File Upload Not Working"

**Solution:**
- Vercel serverless functions have limited file system access
- Consider using Supabase Storage or another cloud storage service for file uploads
- Update `upload_profile.php` to use cloud storage

### Issue 4: "Session Not Persisting"

**Solution:**
- Vercel serverless functions are stateless
- Consider using Supabase Auth or JWT tokens instead of PHP sessions
- Or use Vercel's KV storage for sessions

### Issue 5: "500 Internal Server Error"

**Solution:**
- Check Vercel function logs: **Deployments** → Click deployment → **Functions** tab
- Verify all required PHP extensions are available
- Check that all environment variables are set

## Step 7: Custom Domain (Optional)

1. Go to **Settings** → **Domains**
2. Add your custom domain
3. Follow DNS configuration instructions
4. Update `GOOGLE_REDIRECT_URI` with your custom domain

## Step 8: Continuous Deployment

Vercel automatically deploys when you push to your Git repository:

1. Make changes locally
2. Commit and push to Git
3. Vercel automatically builds and deploys
4. You'll get a preview URL for each commit

## Monitoring & Logs

- **Function Logs:** Deployments → Click deployment → Functions tab
- **Analytics:** Analytics tab (available on paid plans)
- **Real-time Logs:** Use `vercel logs` CLI command

## Important Notes

⚠️ **File Uploads:** Vercel serverless functions have a 4.5MB request body limit and limited file system access. The current `update_profile.php` uses local file storage which **will not work** on Vercel. You need to:
   - Use Supabase Storage (recommended) or another cloud storage service
   - Update `update_profile.php` to upload directly to Supabase Storage
   - Update profile picture URLs to point to Supabase Storage URLs

⚠️ **Sessions:** PHP sessions may not work reliably in serverless environments. Consider using:
- Supabase Auth (recommended)
- JWT tokens
- Vercel KV for session storage

⚠️ **Database Connections:** Connection pooling is handled automatically by Supabase, but be aware of connection limits on the free tier.

## Next Steps

1. ✅ Deploy to Vercel
2. ✅ Configure environment variables
3. ✅ Update Google OAuth redirect URI
4. ✅ Test all functionality
5. ✅ Share your URL with users!

## Support

- [Vercel Documentation](https://vercel.com/docs)
- [Vercel PHP Runtime](https://vercel.com/docs/runtimes/php)
- [Vercel Community](https://github.com/vercel/vercel/discussions)

---

**Your app will be live at:** `https://your-project.vercel.app` 🎉

