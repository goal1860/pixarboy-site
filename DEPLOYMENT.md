# Hostinger Deployment Guide

Complete guide to deploy PixarBoy to Hostinger shared hosting.

## 📋 Prerequisites

- Hostinger hosting account (Premium, Business, or higher)
- Access to Hostinger Control Panel (hPanel)
- FTP client (FileZilla recommended) or SSH access
- Your domain configured in Hostinger

## 🚀 Deployment Steps

### Step 1: Prepare Your Database

1. **Log in to Hostinger hPanel**
   - Go to https://hpanel.hostinger.com

2. **Create MySQL Database**
   - Navigate to **Databases** → **MySQL Databases**
   - Click **Create New Database**
   - Database name: `u123456789_pixarboy` (example)
   - Click **Create**

3. **Create Database User**
   - In the same section, create a new user
   - Username: `u123456789_admin` (example)
   - Password: Generate a strong password (save it!)
   - Click **Create**

4. **Assign User to Database**
   - Add the user to your database with **All Privileges**
   - Save your credentials:
     ```
     DB_HOST: localhost
     DB_NAME: u123456789_pixarboy
     DB_USER: u123456789_admin
     DB_PASS: your_generated_password
     ```

### Step 2: Upload Files to Hostinger

#### Option A: Using File Manager (Easiest)

1. **Access File Manager**
   - In hPanel, go to **Files** → **File Manager**

2. **Navigate to public_html**
   - Delete default files (if any)
   - Keep `.htaccess` if it exists

3. **Upload Project Files**
   
   **Upload to ROOT directory (one level above public_html):**
   - `config/` folder
   - `includes/` folder
   - `.htaccess` (root protection)
   - `README.md`
   - `DEPLOYMENT.md`
   
   **Upload to public_html directory:**
   - All contents from your local `public_html/` folder:
     - `admin/` folder
     - `assets/` folder
     - `index.php`
     - `login.php`
     - `logout.php`
     - `post.php`
     - `install.php`
     - `.htaccess`

#### Option B: Using FTP (FileZilla)

1. **Get FTP Credentials**
   - In hPanel: **Files** → **FTP Accounts**
   - Note: Server, Username, Port

2. **Connect with FileZilla**
   ```
   Host: ftp.yourdomain.com
   Username: your_ftp_username
   Password: your_ftp_password
   Port: 21
   ```

3. **Upload Structure:**
   ```
   /home/u123456789/domains/yourdomain.com/
   ├── config/
   ├── includes/
   ├── .htaccess
   └── public_html/
       ├── admin/
       ├── assets/
       ├── *.php files
       └── .htaccess
   ```

#### Option C: Using SSH & Git (Advanced)

1. **Enable SSH in hPanel**
   - Go to **Advanced** → **SSH Access**
   - Enable SSH access

2. **Connect via SSH**
   ```bash
   ssh u123456789@your-server.hostinger.com
   ```

3. **Navigate to your domain folder**
   ```bash
   cd domains/yourdomain.com/
   ```

4. **Clone the repository**
   ```bash
   # Clone outside public_html
   git clone git@github.com:goal1860/pixarboy-site.git temp_deploy
   
   # Move config and includes to root
   mv temp_deploy/config ./
   mv temp_deploy/includes ./
   mv temp_deploy/.htaccess ./
   
   # Move public_html contents
   mv temp_deploy/public_html/* public_html/
   
   # Clean up
   rm -rf temp_deploy
   ```

### Step 3: Configure Database Connection

1. **Copy database.example.php**
   - Navigate to `config/` folder
   - Copy `database.example.php` to `database.php`

2. **Edit database.php**
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'u123456789_pixarboy');  // Your database name
   define('DB_USER', 'u123456789_admin');     // Your database user
   define('DB_PASS', 'your_database_password'); // Your password
   define('DB_CHARSET', 'utf8mb4');
   ```

3. **Set proper permissions** (via FTP or File Manager)
   - `config/database.php` → 644 (or 640 for better security)
   - `config/` folder → 755

### Step 4: Update Configuration

1. **Edit config/config.php** ⚠️ **CRITICAL**
   ```php
   define('SITE_NAME', 'PixarBoy');
   define('BASE_URL', 'https://yourdomain.com'); // ⚠️ MUST CHANGE from localhost! NO trailing slash
   define('ADMIN_EMAIL', 'your@email.com');
   ```
   
   **Warning:** If you don't update `BASE_URL` from `http://localhost`, all links and redirects (including login) will go to localhost!

2. **Enable HTTPS (in public_html/.htaccess)**
   - Uncomment these lines:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

### Step 5: Run Installation

1. **Visit Installation Page**
   ```
   https://yourdomain.com/install.php
   ```

2. **Complete Installation**
   - This will create database tables
   - Default admin account will be created:
     - **Username:** admin
     - **Password:** admin123

3. **IMPORTANT: Delete install.php**
   - After successful installation, delete or rename `install.php`
   ```bash
   # Via SSH
   rm public_html/install.php
   
   # Or via File Manager
   Delete: public_html/install.php
   ```

### Step 6: Security Checklist

✅ **Must Do:**
- [ ] Change admin password immediately
- [ ] Delete `install.php`
- [ ] Verify `config/database.php` permissions (644 or 640)
- [ ] Enable HTTPS redirect in `.htaccess`
- [ ] **⚠️ Update `BASE_URL`** from localhost to `https://yourdomain.com`

✅ **Recommended:**
- [ ] Set up automatic backups in hPanel
- [ ] Enable Cloudflare (free via Hostinger)
- [ ] Install SSL certificate (free via hPanel)
- [ ] Set up email forwarding for admin email

### Step 7: Test Your Site

1. **Homepage**
   ```
   https://yourdomain.com
   ```

2. **Admin Login**
   ```
   https://yourdomain.com/login.php
   ```

3. **Admin Dashboard**
   ```
   https://yourdomain.com/admin/
   ```

4. **Test creating a new post**
   - Login → Content → Add New Content
   - Publish and verify it appears on homepage

## 🔧 Troubleshooting

### White Screen / 500 Error

1. **Check error logs**
   - hPanel → **Advanced** → **Error Logs**

2. **Common causes:**
   - Incorrect database credentials
   - Wrong file permissions
   - PHP version mismatch

3. **Fix:**
   ```bash
   # Set correct permissions
   chmod 755 public_html
   chmod 644 public_html/*.php
   chmod 755 public_html/admin
   ```

### Database Connection Error

1. **Verify credentials in config/database.php**
2. **Check if database exists** in hPanel
3. **Ensure user has privileges** assigned
4. **Try connecting with phpMyAdmin** to test credentials

### 404 Errors on Pages

1. **Check .htaccess** is uploaded
2. **Verify mod_rewrite** is enabled (usually is on Hostinger)
3. **Check file paths** in config.php

### Login Redirects to Localhost

**Fix:**
1. Edit `config/config.php`
2. Change `BASE_URL` from `http://localhost` to your actual domain:
   ```php
   define('BASE_URL', 'https://yourdomain.com'); // NO trailing slash!
   ```
3. Save and clear browser cache

### CSS/JS Not Loading

1. **Update BASE_URL** in config/config.php
2. **Check assets/ folder** uploaded correctly
3. **Clear browser cache**
4. **Verify file permissions** (644 for files, 755 for folders)

### Can't Login

1. **Reset password via phpMyAdmin:**
   ```sql
   UPDATE users 
   SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE username = 'admin';
   ```
   New password: `admin123`

## 📊 File Structure on Hostinger

```
/home/u123456789/domains/yourdomain.com/
│
├── config/                      ← Secure (above web root)
│   ├── config.php
│   ├── database.php            ← Contains credentials
│   └── database.example.php
│
├── includes/                    ← Secure (above web root)
│   ├── header.php
│   ├── footer.php
│   └── Parsedown.php
│
├── .htaccess                    ← Denies web access to this directory
│
└── public_html/                 ← WEB ROOT (publicly accessible)
    ├── admin/
    │   ├── index.php
    │   ├── content.php
    │   └── users.php
    ├── assets/
    │   ├── css/
    │   ├── js/
    │   └── images/
    ├── .htaccess               ← Security rules
    ├── index.php
    ├── login.php
    ├── logout.php
    ├── post.php
    └── install.php            ← DELETE after installation!
```

## 🔐 Security Best Practices

1. **Keep Software Updated**
   - Regularly check for PHP updates in hPanel
   - Update your CMS when new versions release

2. **Strong Passwords**
   - Change default admin password
   - Use password manager
   - Enable 2FA if adding that feature

3. **Regular Backups**
   - Enable automatic backups in hPanel
   - Download manual backups weekly
   - Keep database and files backups

4. **Monitor Access**
   - Check error logs regularly
   - Review admin access logs
   - Watch for suspicious activity

5. **File Permissions**
   ```
   Directories: 755
   PHP files: 644
   config/database.php: 640 (more secure)
   ```

## 🚀 Performance Optimization

1. **Enable Caching**
   - Already configured in `.htaccess`
   - Browser caching for images (1 year)
   - CSS/JS caching (1 month)

2. **Enable Cloudflare** (Free via Hostinger)
   - CDN for faster loading
   - DDoS protection
   - Additional caching layer

3. **Optimize Images**
   - Use compressed images
   - SVG for illustrations (like AirPods hero)
   - WebP format when possible

4. **Enable Gzip Compression**
   - Already configured in `.htaccess`
   - Reduces file sizes by 70%

## 📞 Support

### Hostinger Support
- Live Chat: 24/7 available
- Tickets: Via hPanel
- Knowledge Base: https://support.hostinger.com

### CMS Issues
- GitHub: https://github.com/goal1860/pixarboy-site/issues
- Documentation: See README.md

## 🎉 Post-Deployment Checklist

- [ ] Site loads at https://yourdomain.com
- [ ] Can login to admin panel
- [ ] Can create/edit/delete posts
- [ ] Images load correctly
- [ ] CSS styling works
- [ ] Mobile responsive working
- [ ] SSL certificate active (padlock in browser)
- [ ] Error pages working (404, etc.)
- [ ] Contact form working (if added)
- [ ] Backups configured
- [ ] Analytics added (Google Analytics, etc.)

## 🔄 Updating Your Site

### Via Git (Recommended)

```bash
# SSH into your server
ssh u123456789@your-server.hostinger.com

# Navigate to your site
cd domains/yourdomain.com

# Pull latest changes (for non-public_html files)
git pull origin main

# Or manually upload changed files via FTP
```

### Via FTP
- Upload only changed files
- Always backup before updating
- Test on staging environment first

---

**Congratulations! Your PixarBoy is now live on Hostinger! 🎊**

For questions or issues, check the GitHub repository: https://github.com/goal1860/pixarboy-site

