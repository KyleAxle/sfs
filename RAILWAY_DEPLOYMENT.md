# 🚂 Railway Deployment Guide - Complete Beginner Tutorial

This guide will help you deploy your PHP application to Railway so other users can access it on the internet.

**⏱️ Estimated Time:** 20-30 minutes

---

## 📋 What You'll Need Before Starting

1. ✅ **Your code on GitHub** - If you haven't done this yet, see **[GITHUB_SETUP_GUIDE.md](./GITHUB_SETUP_GUIDE.md)** first
2. ✅ **Your Supabase database credentials** - You should have these from when you set up your database
3. ✅ **Your Google OAuth credentials** (if you're using Google login) - You should have these from Google Cloud Console
4. ✅ **A computer with internet connection**

**Don't worry if you don't understand everything yet - we'll explain as we go!**

---

## Step 1: Create a Railway Account

**What is Railway?** Railway is a platform that hosts your website on the internet so other people can access it.

1. Go to [railway.app](https://railway.app)
2. Click **"Start a New Project"** or **"Login"** (top right)
3. You'll see options to sign up with:
   - **GitHub** (recommended - easiest since your code is already there!)
   - Email
   - Google
   
   **Choose GitHub** - it will automatically connect to your GitHub account
4. Click **"Authorize Railway"** when GitHub asks for permission
5. Complete any additional setup steps if prompted

**✅ Done!** You now have a Railway account.

**Note:** Railway gives you $5 free credit per month, which is usually enough for small applications!

---

## Step 2: Create a New Project on Railway

1. After logging in, you'll see the Railway dashboard
2. Click the big **"New Project"** button (usually in the top right or center)
3. You'll see options:
   - **Deploy from GitHub repo** (choose this!)
   - **Empty Project**
   - **Deploy a Template**
4. Click **"Deploy from GitHub repo"**

---

## Step 3: Connect Your GitHub Repository

1. Railway will show a list of your GitHub repositories
2. If you don't see your repository:
   - Click **"Configure GitHub App"** or **"Install on GitHub"**
   - Select which repositories Railway can access
   - Choose **"All repositories"** or select just your `sfs` repository
   - Click **"Install"**
3. Find your repository in the list (it should be named `sfs` or similar)
4. Click on your repository

**✅ Done!** Railway is now connected to your GitHub repository.

---

## Step 4: Configure Your Project

Railway will automatically detect your project and start setting it up. Here's what to configure:

### 4.1 Project Settings

1. **Project Name:** Railway will suggest a name (like `sfs`)
   - You can keep it or change it to something like `sfs-appointment-system`
   - This is just for your reference

2. **Service Name:** Railway will create a "service" for your app
   - Keep the default name or change it
   - This doesn't affect your website URL

### 4.2 Build Settings

Railway should auto-detect PHP, but let's make sure:

1. Click on your service (the box that appeared)
2. Go to the **"Settings"** tab
3. Scroll down to **"Build Command"**
   - Leave it empty (Railway will auto-detect)
   - OR set it to: `composer install` (if you have composer.json)

4. **Start Command:**
   - Set this to: `php -S 0.0.0.0:$PORT`
   - This tells Railway how to start your PHP application
   - **Important:** Railway uses the `$PORT` environment variable

5. **Root Directory:**
   - Leave as `.` (current directory)
   - This means Railway will look in the root of your repository

**✅ Done!** Your project is configured.

---

## Step 5: Add Environment Variables

**This is critical!** You need to add your environment variables so your app can connect to the database.

### 5.1 Access Environment Variables

1. In your Railway project, click on your service
2. Go to the **"Variables"** tab
3. You'll see a section to add environment variables

### 5.2 Add Your Supabase Database Credentials

Click **"New Variable"** for each one and add:

#### Variable 1: Database Host
- **Name:** `SUPABASE_DB_HOST`
- **Value:** Your Supabase host (looks like `aws-1-ap-southeast-1.pooler.supabase.com`)
- **Where to find it:** In your `.env` file or Supabase dashboard → Settings → Database

#### Variable 2: Database Port
- **Name:** `SUPABASE_DB_PORT`
- **Value:** `5432`

#### Variable 3: Database Name
- **Name:** `SUPABASE_DB_NAME`
- **Value:** `postgres`

#### Variable 4: Database User
- **Name:** `SUPABASE_DB_USER`
- **Value:** Your database username (looks like `postgres.ndnoevxzgczvyghaktxn`)
- **Where to find it:** In your `.env` file or Supabase dashboard

#### Variable 5: Database Password
- **Name:** `SUPABASE_DB_PASSWORD`
- **Value:** Your actual Supabase database password
- **⚠️ Important:** Copy this exactly from your `.env` file or Supabase dashboard

#### Variable 6: SSL Mode
- **Name:** `SUPABASE_DB_SSLMODE`
- **Value:** `require`

### 5.3 Add Your Google OAuth Credentials (If Using Google Login)

#### Variable 7: Google Client ID
- **Name:** `GOOGLE_CLIENT_ID`
- **Value:** Your Google OAuth Client ID
- **Where to find it:** Google Cloud Console → APIs & Services → Credentials

#### Variable 8: Google Client Secret
- **Name:** `GOOGLE_CLIENT_SECRET`
- **Value:** Your Google OAuth Client Secret
- **Where to find it:** Google Cloud Console → APIs & Services → Credentials

#### Variable 9: Google Redirect URI
- **Name:** `GOOGLE_REDIRECT_URI`
- **Value:** `https://your-project-name.up.railway.app/google_callback.php`
- **⚠️ Important:** You'll get your Railway URL after deployment - update this then!
- **For now:** Use a placeholder like `https://placeholder.up.railway.app/google_callback.php`

### 5.4 Add Optional Variables (If You Have Them)

#### Variable 10: Groq API Key (Optional)
- **Name:** `GROQ_API_KEY`
- **Value:** Your Groq API key (if you have one)

#### Variable 11: PHP Fallback (Optional)
- **Name:** `USE_PHP_FALLBACK`
- **Value:** `false`

**✅ Done!** All environment variables are added.

---

## Step 6: Deploy Your Project

1. Railway will automatically start deploying when you connect your repository
2. You can also manually trigger a deployment:
   - Go to the **"Deployments"** tab
   - Click **"Redeploy"** if needed

3. **Watch the deployment:**
   - You'll see build logs in real-time
   - Wait for it to finish (usually 2-5 minutes)
   - Look for "Build successful" or "Deployed successfully"

**✅ Done!** Your project is deploying!

---

## Step 7: Get Your Railway URL

After deployment completes:

1. Go to your service in Railway
2. Click the **"Settings"** tab
3. Scroll down to **"Domains"** or **"Networking"**
4. You'll see your Railway URL, something like:
   - `https://your-project-name.up.railway.app`
5. **Copy this URL** - this is your live website!

**✅ Done!** You have your live website URL!

---

## Step 8: Update Google OAuth Redirect URI

**Why do we need this?** Google needs to know that your Railway URL is allowed to use Google login.

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Make sure you're logged in with the same account you used to create your OAuth credentials
3. Click the **hamburger menu** (☰) in the top left
4. Go to **"APIs & Services"** → **"Credentials"**
5. Find your OAuth 2.0 Client ID in the list
6. Click on it to edit
7. Scroll down to **"Authorized redirect URIs"**
8. Click **"+ ADD URI"**
9. Paste your Railway URL + `/google_callback.php`
   - **Example:** `https://your-project-name.up.railway.app/google_callback.php`
10. Click **"SAVE"**

11. **Update the environment variable in Railway:**
    - Go back to Railway → Your service → **"Variables"** tab
    - Find `GOOGLE_REDIRECT_URI`
    - Click to edit
    - Update it with your actual Railway URL
    - Save

**✅ Done!** Google now knows your Railway URL is allowed.

---

## Step 9: Redeploy After Updates

After updating environment variables or Google OAuth settings:

1. Go to the **"Deployments"** tab in Railway
2. Click **"Redeploy"** on the latest deployment
3. Wait for it to finish (1-2 minutes)

**✅ Done!** Your changes are live!

---

## Step 10: Test Your Website

Now let's make sure everything works!

1. **Visit your website:**
   - Go to your Railway URL (like `https://your-project-name.up.railway.app`)
   - You should see your login page!

2. **Test the login page:**
   - Go to: `https://your-project-name.up.railway.app/login.html`
   - The page should load without errors

3. **Test registration:**
   - Go to: `https://your-project-name.up.railway.app/register.html`
   - Try creating a test account to see if the database connection works

4. **Test Google login (if configured):**
   - Click the "Sign in with Google" button
   - It should redirect to Google's login page

**✅ If everything loads, congratulations! Your website is live!**

---

## 🐛 Troubleshooting Common Problems

### Problem: "Build Failed" or Deployment Error

**What this means:** Something went wrong during the build process.

**How to fix:**
1. Go to Railway → Your service → **"Deployments"** tab
2. Click on the failed deployment
3. Look at the build logs
4. Common issues:
   - **"Start command not found"** → Make sure you set: `php -S 0.0.0.0:$PORT`
   - **"Port not specified"** → Make sure you're using `$PORT` in the start command
   - **Missing files** → Check that all files are in your GitHub repository

---

### Problem: "Database Connection Failed" or "500 Error"

**What this means:** Your app can't connect to your Supabase database.

**How to fix:**
1. Go to Railway → Your service → **"Variables"** tab
2. Double-check that ALL your Supabase variables are added correctly:
   - `SUPABASE_DB_HOST`
   - `SUPABASE_DB_PORT`
   - `SUPABASE_DB_NAME`
   - `SUPABASE_DB_USER`
   - `SUPABASE_DB_PASSWORD`
   - `SUPABASE_DB_SSLMODE`
3. Make sure there are **no extra spaces** when you copy-paste
4. Make sure values match exactly what's in your `.env` file
5. Redeploy your service
6. Wait a few minutes and try again

**Still not working?**
- Check your Supabase dashboard to make sure your project is active
- Verify your database password is correct (try connecting with it locally first)

---

### Problem: "Google OAuth Not Working" or "Redirect URI Mismatch"

**What this means:** Google doesn't recognize your Railway URL.

**How to fix:**
1. Make sure you added your Railway URL to Google Cloud Console (Step 8)
2. The URL in Google Cloud Console must **exactly match** your Railway URL
3. Make sure the `GOOGLE_REDIRECT_URI` environment variable in Railway matches too
4. It should look like: `https://your-project-name.up.railway.app/google_callback.php`
5. After updating, wait 5-10 minutes for Google to update their settings
6. Redeploy your Railway service
7. Try again

---

### Problem: "File Upload Not Working" or "Profile Picture Not Saving"

**What this means:** Railway's file system is temporary - files get deleted when the service restarts.

**Why this happens:** Railway uses containers that reset, so uploaded files don't persist.

**How to fix (for now):**
- This feature won't work on Railway without additional setup
- You'll need to use cloud storage (like Supabase Storage) instead
- For now, users can still use your app, but profile picture uploads won't work
- This is a more advanced fix - your app will still work for everything else!

---

### Problem: "Session Not Working" or "Logged Out After Refresh"

**What this means:** PHP sessions might not work reliably on Railway.

**Why this happens:** Railway containers can restart, which clears session data.

**How to fix (advanced):**
- This requires code changes to use Supabase Auth or JWT tokens instead
- For now, users might need to log in again if the service restarts
- This is a more advanced fix

---

### Problem: "404 Not Found" or "Page Not Found"

**What this means:** Railway can't find the page you're looking for.

**How to fix:**
1. Make sure you're using the correct URL
2. Check that the file exists in your GitHub repository
3. Try accessing: `https://your-project-name.up.railway.app/index.php` or `https://your-project-name.up.railway.app/login.html`
4. Check the Railway deployment logs for any errors

---

### Problem: "Service Crashed" or "Application Error"

**What this means:** Your PHP application crashed or has an error.

**How to fix:**
1. Go to Railway → Your service → **"Deployments"** tab
2. Click on the latest deployment
3. Check the **"Logs"** tab for error messages
4. Common issues:
   - PHP syntax errors
   - Missing environment variables
   - Database connection issues
5. Fix the issue and push to GitHub (Railway will auto-redeploy)

---

## 📝 Important Notes

### ⚠️ File Uploads

**Current Limitation:** Profile picture uploads won't work on Railway because the file system is temporary.

**Future Fix:** You'll need to use cloud storage (like Supabase Storage) for file uploads. This requires code changes, but your app will work fine for everything else!

### ⚠️ PHP Sessions

**Current Limitation:** PHP sessions might not work perfectly on Railway.

**What this means:** Users might need to log in again if the service restarts.

**Future Fix:** Consider using Supabase Auth or JWT tokens instead of PHP sessions.

### ⚠️ Database Connections

Your Supabase database connection should work fine! Just make sure you've added all the environment variables correctly.

### ⚠️ Free Tier Limits

Railway gives you $5 free credit per month. For a small application, this is usually enough, but:
- Monitor your usage in the Railway dashboard
- You'll get notified if you're approaching the limit
- You can upgrade to a paid plan if needed (starts around $5/month)

---

## ✅ Checklist: Is Everything Working?

Use this checklist to make sure your deployment is complete:

- [ ] Railway account created
- [ ] Project created and connected to GitHub
- [ ] Start command set to: `php -S 0.0.0.0:$PORT`
- [ ] All environment variables added (Supabase + Google OAuth)
- [ ] Google OAuth redirect URI updated in Google Cloud Console
- [ ] Google OAuth redirect URI updated in Railway environment variables
- [ ] Service deployed successfully
- [ ] Website loads at your Railway URL
- [ ] Login page works
- [ ] Registration page works
- [ ] Database connection works (can create accounts)
- [ ] Google login works (if configured)

**If all boxes are checked, you're done!** 🎉

---

## 🎯 What to Do After Deployment

### Share Your Website

Your website is now live! You can share the URL with anyone:
- `https://your-project-name.up.railway.app`

### Make Updates

When you want to update your website:

1. Make changes to your code on your computer
2. Push the changes to GitHub (see GITHUB_SETUP_GUIDE.md)
3. Railway will **automatically** detect the changes and redeploy!
4. Your website will update automatically (usually takes 2-5 minutes)

**That's it!** No need to manually deploy again - Railway does it for you!

### Monitor Your Service

1. Go to your Railway dashboard
2. Check the **"Metrics"** tab to see:
   - CPU usage
   - Memory usage
   - Request count
3. Check the **"Logs"** tab to see:
   - Application logs
   - Error messages
   - Debug information

---

## 🆘 Need More Help?

### Railway Resources
- [Railway Documentation](https://docs.railway.app) - Official guides
- [Railway Discord](https://discord.gg/railway) - Community support
- [Railway Status](https://status.railway.app) - Check if Railway is having issues

### Your Project Resources
- **GitHub Setup:** See `GITHUB_SETUP_GUIDE.md` if you need help with Git
- **Quick Reference:** See the troubleshooting section above

---

## 🎉 Congratulations!

Your application is now live on the internet! Anyone with the URL can access it.

**Your website URL:** `https://your-project-name.up.railway.app`

Share it with your users and start getting feedback! 🚀

---

**Questions?** Check the troubleshooting section above, or refer to the Railway documentation for more help.
