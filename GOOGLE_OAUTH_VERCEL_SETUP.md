# 🔐 Google OAuth Setup for Vercel - Simple Guide

This guide will help you add your existing Google OAuth credentials to Vercel.

**⏱️ Estimated Time:** 5 minutes

---

## 📋 What You'll Need

1. Your existing Google OAuth credentials:
   - Client ID
   - Client Secret
2. Your Vercel project URL (like `https://your-project.vercel.app`)

---

## Step 1: Update Redirect URI in Google Cloud Console (If Needed)

**First, make sure your Google OAuth credentials have the correct redirect URI for Vercel:**

1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Sign in with your Google account
3. Select your project
4. Go to **"APIs & Services"** → **"Credentials"**
5. Click on your OAuth Client ID
6. Scroll down to **"Authorized redirect URIs"**
7. Check if you have: `https://your-project-name.vercel.app/google_callback.php`
   - **Replace `your-project-name` with your actual Vercel project name!**
   - Example: `https://kylejashrapsfs.vercel.app/google_callback.php`
8. If it's not there, click **"+ ADD URI"** and add it
9. Click **"SAVE"**

**✅ Done!** Your Google OAuth is configured for Vercel.

---

## Step 2: Add Credentials to Vercel

Now add your existing credentials to Vercel environment variables:

### 2.1 Go to Vercel Environment Variables

1. Go to [vercel.com](https://vercel.com) and log in
2. Click on your project
3. Click **"Settings"** (top menu)
4. Click **"Environment Variables"** (left sidebar)

### 2.2 Add Client ID

1. Click **"Add New"** or **"Add"** button
2. **Key:** Type exactly: `GOOGLE_CLIENT_ID`
3. **Value:** Paste your existing Client ID
   - You can find this in Google Cloud Console → **APIs & Services** → **Credentials** → Click on your OAuth Client ID
4. Check all three boxes: ✅ **Production**, ✅ **Preview**, ✅ **Development**
5. Click **"Save"**

### 2.3 Add Client Secret

1. Click **"Add New"** or **"Add"** button again
2. **Key:** Type exactly: `GOOGLE_CLIENT_SECRET`
3. **Value:** Paste your existing Client Secret
   - You can find this in Google Cloud Console → **APIs & Services** → **Credentials** → Click on your OAuth Client ID
   - **Note:** If you can't see it, you may need to create new credentials or check if you saved it somewhere
4. Check all three boxes: ✅ **Production**, ✅ **Preview**, ✅ **Development**
5. Click **"Save"**

### 2.4 Add Redirect URI

1. Click **"Add New"** or **"Add"** button again
2. **Key:** Type exactly: `GOOGLE_REDIRECT_URI`
3. **Value:** Type: `https://your-project-name.vercel.app/google_callback.php`
   - **Replace `your-project-name` with your actual Vercel project name!**
   - Example: `https://kylejashrapsfs.vercel.app/google_callback.php`
   - **Where to find your project name:** Go to Vercel dashboard → Your project → Look at the top for your URL
4. Check all three boxes: ✅ **Production**, ✅ **Preview**, ✅ **Development**
5. Click **"Save"**

**✅ Done!** All three environment variables are added!

---

## Step 3: Redeploy Your Project

After adding environment variables, you need to redeploy:

1. Go to the **"Deployments"** tab (top menu)
2. Find your latest deployment
3. Click the **three dots** (⋯) on the right
4. Click **"Redeploy"**
5. Make sure **"Production"** is selected
6. Click **"Redeploy"** button
7. Wait 1-3 minutes for it to finish

**✅ Done!** Your project is now redeployed with Google OAuth!

---

## Step 4: Test Google Login

1. Go to your website: `https://your-project-name.vercel.app`
2. Go to the login page
3. Look for a **"Sign in with Google"** button
4. Click it
5. You should see Google's login screen
6. Sign in with your Google account
7. You should be redirected back to your website

**✅ If it works, you're done!** 🎉

---

## 🐛 Troubleshooting

### Problem: "Redirect URI mismatch" error

**What this means:** The redirect URI in Google Cloud Console doesn't match your Vercel URL.

**How to fix:**
1. Go back to Google Cloud Console → **APIs & Services** → **Credentials**
2. Click on your OAuth Client ID
3. Scroll down to **"Authorized redirect URIs"**
4. Make sure you have: `https://your-actual-project-name.vercel.app/google_callback.php`
5. If it's wrong, click the pencil icon to edit
6. Update it with your correct Vercel URL
7. Click **"SAVE"**
8. Wait 5-10 minutes for Google to update
9. Try again

### Problem: "Invalid client" error

**What this means:** Your Client ID or Client Secret is wrong in Vercel.

**How to fix:**
1. Go to Vercel → Your Project → **Settings** → **Environment Variables**
2. Check that:
   - `GOOGLE_CLIENT_ID` matches exactly what's in Google Cloud Console
   - `GOOGLE_CLIENT_SECRET` matches exactly what's in Google Cloud Console
   - There are no extra spaces before or after the values
3. If they're wrong, click the three dots (⋯) next to the variable → **Edit**
4. Fix the value and save
5. Redeploy your project

### Problem: Google login button doesn't appear

**What this means:** The button might not be showing on your page.

**How to fix:**
1. Check your login page HTML file
2. Look for a link to `google_login.php`
3. Make sure the file exists in your project
4. Check your browser's developer console (F12) for any errors

### Problem: "Access blocked" error from Google

**What this means:** Your app might need verification (for production use).

**How to fix:**
- For testing: This is normal! Google will show a warning that the app isn't verified
- Click **"Advanced"** → **"Go to [Your App Name] (unsafe)"**
- This is fine for development/testing
- For production: You'll need to verify your app with Google (more advanced)

---

## 📝 Quick Reference

### What to add in Vercel:

| Key | Value |
|-----|-------|
| `GOOGLE_CLIENT_ID` | Your Client ID from Google Cloud Console |
| `GOOGLE_CLIENT_SECRET` | Your Client Secret from Google Cloud Console |
| `GOOGLE_REDIRECT_URI` | `https://your-project.vercel.app/google_callback.php` |

### Where to find your Vercel project name:

1. Go to your Vercel dashboard
2. Click on your project
3. Look at the top - it shows your project URL
4. Or go to **Settings** → **Domains** - you'll see your domain there

---

## ✅ Checklist

Use this to make sure you did everything:

- [ ] Updated authorized redirect URI in Google Cloud Console (with your Vercel URL)
- [ ] Added `GOOGLE_CLIENT_ID` to Vercel environment variables
- [ ] Added `GOOGLE_CLIENT_SECRET` to Vercel environment variables
- [ ] Added `GOOGLE_REDIRECT_URI` to Vercel environment variables (with correct project name)
- [ ] Redeployed your Vercel project
- [ ] Tested Google login on your website

**If all boxes are checked, you're done!** 🎉

---

## 🆘 Need More Help?

- [Google Cloud Console Help](https://cloud.google.com/docs)
- [Vercel Documentation](https://vercel.com/docs)
- Check your Vercel deployment logs if something isn't working

---

**Congratulations!** Your Google OAuth login should now work on Vercel! 🚀
