# 📚 Complete GitHub Setup Guide

This guide will walk you through setting up Git and pushing your code to GitHub from scratch.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Step 1: Create GitHub Account](#step-1-create-github-account)
3. [Step 2: Install Git](#step-2-install-git)
4. [Step 3: Configure Git](#step-3-configure-git)
5. [Step 4: Create GitHub Repository](#step-4-create-github-repository)
6. [Step 5: Initialize Git in Your Project](#step-5-initialize-git-in-your-project)
7. [Step 6: Add Files to Git](#step-6-add-files-to-git)
8. [Step 7: Make Your First Commit](#step-7-make-your-first-commit)
9. [Step 8: Connect to GitHub](#step-8-connect-to-github)
10. [Step 9: Push to GitHub](#step-9-push-to-github)
11. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- A computer with internet connection
- Your project files ready
- About 15-20 minutes

---

## Step 1: Create GitHub Account

1. Go to [github.com](https://github.com)
2. Click **"Sign up"** (top right)
3. Enter your details:
   - Username (choose something memorable)
   - Email address
   - Password
4. Verify your email address (check your inbox)
5. Complete the setup questions (you can skip most of them)

**✅ Done!** You now have a GitHub account.

---

## Step 2: Install Git

Git is the version control software that tracks your code changes.

### For Windows:

1. Go to [git-scm.com/download/win](https://git-scm.com/download/win)
2. Download the installer (it will auto-detect your system)
3. Run the installer:
   - Click "Next" through most screens
   - **Important:** Choose "Git from the command line and also from 3rd-party software" when asked
   - Keep default options for everything else
   - Click "Install"
4. Wait for installation to complete
5. Click "Finish"

### Verify Installation:

1. Open **Command Prompt** (Press `Win + R`, type `cmd`, press Enter)
   OR open **PowerShell**
2. Type: `git --version`
3. You should see something like: `git version 2.xx.x`

**✅ Git is installed!**

---

## Step 3: Configure Git

Tell Git who you are (this is used for your commits):

1. Open **Command Prompt** or **PowerShell**
2. Type these commands (replace with YOUR info):

```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

**Example:**
```bash
git config --global user.name "John Doe"
git config --global user.email "john.doe@gmail.com"
```

**Note:** Use the SAME email you used for your GitHub account!

3. Verify it worked:
```bash
git config --global user.name
git config --global user.email
```

**✅ Git is configured!**

---

## Step 4: Create GitHub Repository

A repository (repo) is like a folder on GitHub where your code lives.

1. Go to [github.com](https://github.com) and **log in**
2. Click the **"+"** icon (top right) → **"New repository"**
3. Fill in the details:
   - **Repository name:** `sfs-appointment-system` (or any name you like)
   - **Description:** "Student/Faculty System - Appointment Management" (optional)
   - **Visibility:** 
     - Choose **Public** (free, anyone can see)
     - OR **Private** (only you can see, requires paid plan for some features)
   - **DO NOT** check "Add a README file" (we'll do that later)
   - **DO NOT** add .gitignore or license (we already have these)
4. Click **"Create repository"**

**✅ Repository created!**

**Important:** You'll see a page with instructions. **DON'T CLOSE IT YET** - you'll need the repository URL!

The URL will look like: `https://github.com/YOUR_USERNAME/sfs-appointment-system.git`

---

## Step 5: Initialize Git in Your Project

Now we'll set up Git in your local project folder.

1. Open **Command Prompt** or **PowerShell**
2. Navigate to your project folder:

```bash
cd "C:\Users\cuber\OneDrive\Desktop\sfs"
```

**Tip:** You can also:
   - Right-click in your project folder
   - Select "Git Bash Here" (if you installed Git)
   - OR open PowerShell/CMD in that folder

3. Initialize Git:
```bash
git init
```

You should see: `Initialized empty Git repository in C:\Users\cuber\OneDrive\Desktop\sfs\.git`

**✅ Git is initialized!**

---

## Step 6: Add Files to Git

Tell Git which files to track (add to version control).

### Check what files Git sees:

```bash
git status
```

You'll see a list of files. Red files are "untracked" (not yet added to Git).

### Add all files:

```bash
git add .
```

The `.` means "all files in current directory"

### Verify files were added:

```bash
git status
```

Now files should be **green** (staged and ready to commit).

**✅ Files are staged!**

---

## Step 7: Make Your First Commit

A commit is like saving a snapshot of your code.

```bash
git commit -m "Initial commit - ready for Vercel deployment"
```

**What this does:**
- `commit` = save this snapshot
- `-m` = add a message
- The text in quotes = your commit message (describe what you're saving)

You should see something like:
```
[main (root-commit) abc1234] Initial commit - ready for Vercel deployment
 X files changed, Y insertions(+)
```

**✅ First commit created!**

---

## Step 8: Connect to GitHub

Link your local repository to the GitHub repository you created.

1. Go back to your GitHub repository page (the one you created in Step 4)
2. Copy the repository URL. It will look like one of these:
   - HTTPS: `https://github.com/YOUR_USERNAME/sfs-appointment-system.git`
   - SSH: `git@github.com:YOUR_USERNAME/sfs-appointment-system.git`

**Use HTTPS** (easier for beginners)

3. In your Command Prompt/PowerShell, type:

```bash
git remote add origin https://github.com/YOUR_USERNAME/sfs-appointment-system.git
```

**Replace `YOUR_USERNAME` with your actual GitHub username!**

**Example:**
```bash
git remote add origin https://github.com/johndoe/sfs-appointment-system.git
```

4. Verify it worked:
```bash
git remote -v
```

You should see your repository URL listed.

**✅ Connected to GitHub!**

---

## Step 9: Push to GitHub

Upload your code to GitHub!

### First, rename your branch to "main" (GitHub's default):

```bash
git branch -M main
```

### Push your code:

```bash
git push -u origin main
```

**What happens:**
- Git will ask for your GitHub **username** (enter it)
- Git will ask for your **password**

**⚠️ Important:** GitHub no longer accepts regular passwords! You need a **Personal Access Token**.

### Create a Personal Access Token:

1. Go to GitHub.com → Click your profile picture (top right) → **Settings**
2. Scroll down → Click **"Developer settings"** (left sidebar)
3. Click **"Personal access tokens"** → **"Tokens (classic)"**
4. Click **"Generate new token"** → **"Generate new token (classic)"**
5. Give it a name: `Vercel Deployment`
6. Set expiration: Choose **90 days** or **No expiration** (your choice)
7. Check these permissions (scopes):
   - ✅ **repo** (full control of private repositories)
8. Scroll down → Click **"Generate token"**
9. **COPY THE TOKEN IMMEDIATELY!** (You won't see it again)
   - It looks like: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

### Use the token as your password:

When Git asks for your password, **paste the token** instead of your GitHub password.

**Example:**
```
Username: johndoe
Password: ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx  (paste your token here)
```

### After successful push:

You should see:
```
Enumerating objects: X, done.
Counting objects: 100% (X/X), done.
Writing objects: 100% (X/X), done.
To https://github.com/YOUR_USERNAME/sfs-appointment-system.git
 * [new branch]      main -> main
Branch 'main' set up to track remote branch 'main' from 'origin'.
```

**✅ Your code is on GitHub!**

### Verify:

1. Go to your GitHub repository page
2. Refresh the page
3. You should see all your files! 🎉

---

## Future Updates (After First Push)

When you make changes to your code and want to update GitHub:

```bash
# 1. Check what changed
git status

# 2. Add changed files
git add .

# 3. Commit changes
git commit -m "Description of what you changed"

# 4. Push to GitHub
git push
```

That's it! No need to connect again - Git remembers.

---

## Troubleshooting

### Problem: "fatal: not a git repository"

**Solution:** You're not in the right folder. Make sure you're in your project folder:
```bash
cd "C:\Users\cuber\OneDrive\Desktop\sfs"
git init
```

### Problem: "git: command not found"

**Solution:** Git is not installed or not in your PATH. Reinstall Git and make sure to choose "Git from the command line" option.

### Problem: "Permission denied" or "Authentication failed"

**Solutions:**
1. Make sure you're using a **Personal Access Token**, not your GitHub password
2. Check that the token has `repo` permission
3. Make sure the token hasn't expired
4. Try creating a new token

### Problem: "remote origin already exists"

**Solution:** You already added the remote. To change it:
```bash
git remote set-url origin https://github.com/YOUR_USERNAME/sfs-appointment-system.git
```

### Problem: "failed to push some refs"

**Solution:** Someone (or you) pushed changes to GitHub. Pull first:
```bash
git pull origin main --allow-unrelated-histories
git push -u origin main
```

### Problem: "Please tell me who you are"

**Solution:** Configure Git (see Step 3):
```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

### Problem: ".env file is being pushed" (Security Issue!)

**Solution:** Make sure `.env` is in `.gitignore`. Check:
```bash
# In your project folder, open .gitignore
# Make sure it contains: .env
```

If `.env` was already committed:
```bash
git rm --cached .env
git commit -m "Remove .env from repository"
git push
```

### Problem: Files are too large

**Solution:** Some files might be too big for GitHub (100MB limit). Check `.gitignore` excludes:
- `vendor/` folder
- Large files
- Upload folders

---

## Quick Reference Commands

```bash
# Check status
git status

# Add all files
git add .

# Commit changes
git commit -m "Your message here"

# Push to GitHub
git push

# Pull from GitHub (get latest changes)
git pull

# See commit history
git log

# See what branch you're on
git branch
```

---

## Next Steps

After pushing to GitHub:

1. ✅ Your code is safely stored on GitHub
2. ✅ You can now deploy to Vercel (see `VERCEL_DEPLOYMENT.md`)
3. ✅ You can share your code with others
4. ✅ You can access it from any computer

**Ready to deploy?** Check out `QUICK_DEPLOY.md` or `VERCEL_DEPLOYMENT.md`!

---

## Need More Help?

- [Git Official Documentation](https://git-scm.com/doc)
- [GitHub Help](https://docs.github.com)
- [GitHub Desktop](https://desktop.github.com) - GUI alternative (easier for beginners)

---

**Congratulations!** 🎉 You've successfully set up Git and pushed your code to GitHub!
