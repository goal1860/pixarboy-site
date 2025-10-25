# PixarBoy CMS

A simple, modern Content Management System built with PHP and MySQL.

## Features

### User Management
- ✅ User authentication (login/logout)
- ✅ Role-based access control (Admin, Editor, User)
- ✅ User CRUD operations
- ✅ Active/Inactive user status
- ✅ Secure password hashing

### Content Management
- ✅ Create, Read, Update, Delete (CRUD) content
- ✅ Draft, Published, and Archived status
- ✅ Content excerpts
- ✅ Author attribution
- ✅ Slug-based URLs
- ✅ Timestamp tracking

### Dashboard
- ✅ Statistics overview
- ✅ Recent content list
- ✅ Quick access to management tools
- ✅ Role-based menu visibility

## Installation

### 1. Install the Database

Visit: http://localhost/install.php

This will:
- Create all necessary database tables
- Create default admin account

### 2. Default Credentials

**Username:** `admin`  
**Password:** `admin123`

⚠️ **Important:** Change this password immediately after first login!

### 3. Delete Installation File

After successful installation, delete the `install.php` file for security:

```bash
rm install.php
```

## Usage

### Accessing the CMS

- **Frontend:** http://localhost/
- **Login:** http://localhost/login.php
- **Admin Dashboard:** http://localhost/admin/

### User Roles

- **Admin:** Full access to all features including user management
- **Editor:** Can manage content
- **User:** Basic access (read-only)

### Managing Content

1. Log in to the admin panel
2. Navigate to "Content" in the menu
3. Click "Add New Content"
4. Fill in:
   - Title (required)
   - Content (required)
   - Excerpt (optional)
   - Status (Draft/Published/Archived)
5. Click "Save Content"

### Managing Users (Admin Only)

1. Navigate to "Users" in the admin menu
2. Click "Add New User"
3. Fill in user details
4. Assign role and status
5. Click "Save User"

## File Structure

```
pixarboy/
├── assets/
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   ├── js/
│   │   └── main.js          # JavaScript enhancements
│   └── images/              # Image assets
├── config/
│   ├── config.php           # App configuration
│   └── database.php         # Database connection
├── includes/
│   ├── header.php          # Page header template
│   └── footer.php          # Page footer template
├── public/
│   ├── index.php           # Homepage
│   ├── login.php           # Login page
│   ├── logout.php          # Logout handler
│   └── admin/
│       ├── index.php       # Dashboard
│       ├── users.php       # User management
│       └── content.php     # Content management
├── install.php             # Database installer
└── README.md              # This file
```

## Database Schema

### Users Table
- `id` - Primary key
- `username` - Unique username
- `email` - Unique email address
- `password` - Hashed password
- `role` - User role (admin/editor/user)
- `status` - Account status (active/inactive)
- `created_at`, `updated_at` - Timestamps

### Content Table
- `id` - Primary key
- `title` - Content title
- `slug` - URL-friendly slug
- `content` - Main content body
- `excerpt` - Short description
- `status` - Publication status (draft/published/archived)
- `author_id` - Foreign key to users table
- `created_at`, `updated_at` - Timestamps

### Categories Table
- `id` - Primary key
- `name` - Category name
- `slug` - URL-friendly slug
- `description` - Category description
- `created_at` - Timestamp

### Content_Categories Table
- Junction table linking content to categories
- `content_id` - Foreign key to content
- `category_id` - Foreign key to categories

## Configuration

### Database Settings

Edit `config/database.php` to change database credentials:

```php
define('DB_HOST', 'mysql');
define('DB_NAME', 'pixarboy_cms');
define('DB_USER', 'default');
define('DB_PASS', 'secret');
```

### Application Settings

Edit `config/config.php` to customize:

```php
define('SITE_NAME', 'PixarBoy CMS');
define('BASE_URL', 'http://localhost');
define('ADMIN_EMAIL', 'admin@pixarboy.com');
```

## Security Features

- ✅ Password hashing using PHP's `password_hash()`
- ✅ SQL injection prevention using PDO prepared statements
- ✅ XSS protection with input sanitization
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ CSRF protection recommended for production

## Development

### Requirements
- PHP 8.3+
- MySQL 8.0+
- Nginx/Apache web server

### Local Development with Laradock
This project is configured to work with Laradock. The document root points to `/var/www/pixarboy/public`.

## Future Enhancements

Potential improvements for future versions:
- [ ] WYSIWYG editor for content
- [ ] Image upload and media library
- [ ] Category management UI
- [ ] Search functionality
- [ ] Pagination for listings
- [ ] User profile editing
- [ ] Email notifications
- [ ] SEO metadata fields
- [ ] API endpoints
- [ ] Multi-language support

## License

MIT License - Feel free to use and modify as needed.

## Support

For issues or questions, please check the code documentation or create an issue in your repository.

