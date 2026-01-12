# ⚡ Quick Railway Deployment

## 5-Minute Setup

### 1. Push to GitHub
**📚 New to Git?** See **[GITHUB_SETUP_GUIDE.md](./GITHUB_SETUP_GUIDE.md)** for detailed instructions!

If you already have Git set up:
```bash
git add .
git commit -m "Ready for Railway deployment"
git push
```

### 2. Deploy on Railway
1. Go to [railway.app/new](https://railway.app/new)
2. Click **"Deploy from GitHub repo"**
3. Select your repository
4. Railway will auto-detect and start deploying

### 3. Configure Start Command
1. Click on your service
2. Go to **Settings** tab
3. Set **Start Command** to: `php -S 0.0.0.0:$PORT`
4. Save

### 4. Add Environment Variables
Go to **Variables** tab and add:

```
SUPABASE_DB_HOST=your_host
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=your_user
SUPABASE_DB_PASSWORD=your_password
SUPABASE_DB_SSLMODE=require
GOOGLE_CLIENT_ID=your_client_id (if using Google login)
GOOGLE_CLIENT_SECRET=your_client_secret (if using Google login)
GOOGLE_REDIRECT_URI=https://your-project.up.railway.app/google_callback.php
```

### 5. Get Your URL
1. Go to **Settings** → **Networking**
2. Copy your Railway URL
3. Update `GOOGLE_REDIRECT_URI` with your actual URL
4. Update Google Cloud Console with your Railway URL

### 6. Redeploy
Click **Deployments** → **Redeploy**

**Done!** 🎉 Your app is live at `https://your-project.up.railway.app`

---

📖 **Full guide:** See `RAILWAY_DEPLOYMENT.md` for detailed instructions and troubleshooting.
