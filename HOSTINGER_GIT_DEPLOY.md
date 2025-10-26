# Hostinger Git Deployment Guide

Quick and easy deployment using Hostinger's built-in Git deployment feature.

## 🚀 One-Click Deployment Steps

### 1. Access Git Deployment

1. **Login to Hostinger hPanel**
   - Go to https://hpanel.hostinger.com

2. **Navigate to Git**
   - Go to **Advanced** → **Git** (or search for "Git")
   - Click **"Create New Repository"**

### 2. Configure Git Deployment

Fill in the following fields:

```
Repository:
https://github.com/goal1860/pixarboy-site.git

Branch:
main

Directory (optional):
[LEAVE BLANK]
```

**Important:** Leave the Directory field **BLANK** so it deploys directly to `public_html/`

### 3. Deploy

1. Click **"Create"**
2. Hostinger will:
   - Clone the repository
   - Deploy to `public_html/`
   - Show deployment status

3. **Wait for deployment to complete** (usually 30-60 seconds)

### 4. Configure Database

1. **Create MySQL Database**
   - In hPanel: **Databases** → **MySQL Databases**
   - Click **"Create New Database"**
   - Database name will be like: `u123456789_pixarboy`
   - Create a user and assign all privileges
   - **Save these credentials!**

2. **Edit Database Configuration**
   
   Via File Manager:
   - Go to **Files** → **File Manager**
   - Navigate to `public_html/config/`
   - Copy `database.example.php` to `database.php`
   - Edit `database.php` with your credentials:
   
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'u123456789_pixarboy');     // Your database name
   define('DB_USER', 'u123456789_user');          // Your database user
   define('DB_PASS', 'your_password_here');       // Your password
   ```

3. **Update Site Configuration** ⚠️ **CRITICAL**
   
   Edit `public_html/config/config.php` and update BASE_URL with your actual domain:
   ```php
   define('SITE_NAME', 'PixarBoy');
   define('BASE_URL', 'https://yourdomain.com'); // ⚠️ MUST CHANGE! No trailing slash!
   define('ADMIN_EMAIL', 'your@email.com');
   ```
   
   **Warning:** If you don't change `BASE_URL` from `http://localhost`, all links and redirects (including login) will go to localhost instead of your domain!

### 5. Run Installation

1. **Visit Installation Page**
   ```
   https://yourdomain.com/install.php
   ```

2. **Complete Installation**
   - Creates database tables
   - Sets up default admin user
   - Default credentials:
     - Username: `admin`
     - Password: `admin123`

3. **🔒 DELETE install.php**
   - **CRITICAL:** After installation, delete `install.php` via File Manager
   - Path: `public_html/install.php`

### 6. Enable HTTPS

1. **Edit .htaccess**
   - File Manager → `public_html/.htaccess`
   - Find these lines (around line 11-12):
   
   ```apache
   # RewriteCond %{HTTPS} off
   # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

2. **Uncomment them** (remove the #):
   
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

3. **Save** - Your site will now force HTTPS

### 7. Test Your Site

Visit these URLs to verify everything works:

- ✅ Homepage: `https://yourdomain.com`
- ✅ Login: `https://yourdomain.com/login.php`
- ✅ Admin: `https://yourdomain.com/admin/`
- ✅ Sample Post: `https://yourdomain.com/post.php?slug=apple-airpods-4-review`

## 🔄 Updating Your Site

When you push changes to GitHub, update your Hostinger deployment:

### Option 1: Auto-Deploy (Recommended)

1. In hPanel Git section
2. Click **"Pull"** next to your repository
3. Hostinger will pull latest changes

### Option 2: Manual Redeploy

1. Delete the current deployment
2. Create a new one with the same settings
3. All changes will be deployed

### Option 3: Git Pull via SSH

```bash
ssh u123456789@your-server.hostinger.com
cd public_html
git pull origin main
```

## 📁 File Structure After Deployment

```
Hostinger public_html/
├── config/                 # 🔒 Protected by .htaccess
│   ├── config.php
│   ├── database.php       # You create this
│   └── database.example.php
├── includes/              # 🔒 Protected by .htaccess
│   ├── header.php
│   ├── footer.php
│   └── Parsedown.php
├── admin/                 # ✅ Web accessible
│   ├── index.php
│   ├── content.php
│   └── users.php
├── assets/                # ✅ Web accessible
│   ├── css/
│   ├── js/
│   └── images/
├── .htaccess             # Security & routing
├── .git/                 # Git repository
├── index.php             # Homepage
├── login.php
├── post.php
└── install.php           # ❌ DELETE after installation!
```

## 🔐 Security Notes

### What's Protected

✅ **config/** directory - Blocked by .htaccess  
✅ **includes/** directory - Blocked by .htaccess  
✅ **database.php** - Specifically blocked  
✅ **.git/** directory - Hidden from web  
✅ **README.md** and docs - Blocked from web access  

### What You Must Do

1. **Change admin password** immediately after first login
2. **Delete install.php** after installation
3. **Keep database.php secret** (it's in .gitignore)
4. **Enable HTTPS** (uncomment in .htaccess)
5. **Use strong passwords** for database and admin

## 🛠️ Troubleshooting

### Issue: White Screen

**Cause:** PHP error or wrong paths

**Fix:**
1. Check error logs: hPanel → **Advanced** → **Error Logs**
2. Verify `config/database.php` exists and has correct credentials
3. Check file permissions (usually automatic on Hostinger)

### Issue: Config/Includes Accessible

**Cause:** .htaccess not working

**Fix:**
1. Verify `.htaccess` file exists in public_html root
2. Check if mod_rewrite is enabled (usually is on Hostinger)
3. Try accessing: `https://yourdomain.com/config/` - should show 403 Forbidden

### Issue: Login Redirects to Localhost

**Cause:** BASE_URL not updated from default

**Fix:**
1. Edit `config/config.php`
2. Change from `http://localhost` to your actual domain:
   ```php
   define('BASE_URL', 'https://yourdomain.com'); // NO trailing slash!
   ```
3. Save and test login again

### Issue: CSS/JS Not Loading

**Cause:** Wrong BASE_URL

**Fix:**
1. Edit `config/config.php`
2. Update `BASE_URL` to your actual domain:
   ```php
   define('BASE_URL', 'https://yourdomain.com');
   ```
3. No trailing slash!

### Issue: Database Connection Failed

**Cause:** Wrong database credentials

**Fix:**
1. Double-check credentials in `config/database.php`
2. Test in hPanel → **phpMyAdmin**
3. Ensure user has ALL PRIVILEGES on the database

### Issue: Git Pull Fails

**Cause:** Local changes conflict with repository

**Fix:**
1. Via SSH:
   ```bash
   cd public_html
   git stash              # Save local changes
   git pull origin main   # Pull updates
   git stash pop          # Restore local changes
   ```

2. Or reset to remote:
   ```bash
   cd public_html
   git fetch origin
   git reset --hard origin/main
   ```

## 📊 File Permissions

Hostinger usually sets these automatically, but if needed:

```
Directories: 755
PHP files: 644
config/database.php: 640 (more secure)
```

## ⚡ Performance Tips

1. **Enable Cloudflare** (free via Hostinger)
   - CDN for faster loading
   - DDoS protection
   - Free SSL

2. **Browser Caching**
   - Already enabled in `.htaccess`
   - Images cached for 1 year
   - CSS/JS cached for 1 month

3. **Compression**
   - Gzip enabled in `.htaccess`
   - Reduces file sizes by ~70%

## 🎯 Quick Reference

| Task | Command/Path |
|------|-------------|
| Deploy | hPanel → Git → Create Repository |
| Update | hPanel → Git → Pull |
| Database | hPanel → Databases → MySQL |
| File Manager | hPanel → Files → File Manager |
| Error Logs | hPanel → Advanced → Error Logs |
| SSL | hPanel → Security → SSL |

## 📝 Post-Deployment Checklist

- [ ] Site loads at https://yourdomain.com
- [ ] Database configured (`config/database.php`)
- [ ] `install.php` run once
- [ ] `install.php` deleted
- [ ] Admin password changed
- [ ] HTTPS enabled (`.htaccess` uncommented)
- [ ] **⚠️ BASE_URL** updated from localhost to your domain in `config/config.php`
- [ ] Can login to admin panel
- [ ] Can create/edit/delete posts
- [ ] CSS/JS loading correctly
- [ ] Images displaying
- [ ] Mobile responsive working
- [ ] `config/` directory returns 403 (protected)
- [ ] `includes/` directory returns 403 (protected)

## 🆘 Getting Help

### Hostinger Support
- Live Chat: 24/7 in hPanel
- Email: support@hostinger.com
- Knowledge Base: support.hostinger.com

### Project Issues
- GitHub: https://github.com/goal1860/pixarboy-site/issues
- Check README.md and DEPLOYMENT.md for more details

---

**Congratulations!** Your PixarBoy is now deployed via Hostinger Git! 🎊

Deployment URL: `https://yourdomain.com`  
Admin Panel: `https://yourdomain.com/admin/`

