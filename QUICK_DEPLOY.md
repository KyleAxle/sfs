# ⚡ Quick Vercel Deployment

## 5-Minute Setup

### 1. Push to GitHub

**📚 New to Git?** See **[GITHUB_SETUP_GUIDE.md](./GITHUB_SETUP_GUIDE.md)** for detailed instructions!

If you already have Git set up:
```bash
git add .
git commit -m "Ready for Vercel deployment"
git push
```

### 2. Deploy on Vercel
1. Go to [vercel.com/new](https://vercel.com/new)
2. Import your GitHub repository
3. Click **Deploy** (don't configure anything yet)

### 3. Add Environment Variables
After first deployment, go to **Settings → Environment Variables** and add:

```
SUPABASE_DB_HOST=your_host
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=your_user
SUPABASE_DB_PASSWORD=your_password
SUPABASE_DB_SSLMODE=require
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=https://your-project.vercel.app/google_callback.php
```

### 4. Redeploy
Click **Deployments → ... → Redeploy**

### 5. Update Google OAuth
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Add redirect URI: `https://your-project.vercel.app/google_callback.php`

**Done!** 🎉 Your app is live at `https://your-project.vercel.app`

---

📖 **Full guide:** See `VERCEL_DEPLOYMENT.md` for detailed instructions and troubleshooting.


