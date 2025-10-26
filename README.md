# PixarBoy

A beautiful, modern Content Management System built with PHP and MySQL, featuring a clean design inspired by contemporary web aesthetics.

![Version](https://img.shields.io/badge/version-2.0-blue)
![PHP](https://img.shields.io/badge/PHP-8.3+-purple)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange)

## ✨ What's New in v2.0

### 🎨 Modern UI Redesign
- **Puva-Inspired Design**: Clean, contemporary interface with gradient accents
- **Responsive Grid Layouts**: Beautiful card-based design that adapts to all screen sizes
- **Hero Section**: Eye-catching homepage with call-to-action buttons
- **Modern Typography**: Inter font family for excellent readability
- **Smooth Animations**: Fade-in effects, hover states, and smooth transitions
- **Mobile-First**: Fully responsive with mobile hamburger menu

### 🚀 Enhanced Features
- **Post Detail Pages**: Dedicated pages for each post with related content
- **Interactive JavaScript**: Mobile menu, smooth scrolling, form enhancements
- **Modern Admin Dashboard**: Stats cards with gradient designs
- **Enhanced Forms**: Better input fields with icons and placeholders
- **Social Sharing**: Share buttons for Twitter, Facebook, and LinkedIn
- **Empty States**: Friendly messages when no content exists
- **Improved Alerts**: Auto-dismissible notifications with smooth animations
- **Relative Paths**: No BASE_URL configuration needed - works on any domain automatically

## Features

### 👥 User Management
- ✅ User authentication (login/logout)
- ✅ Role-based access control (Admin, Editor, User)
- ✅ User CRUD operations with avatars
- ✅ Active/Inactive user status
- ✅ Secure password hashing

### 📝 Content Management
- ✅ Create, Read, Update, Delete (CRUD) content
- ✅ Draft, Published, and Archived status
- ✅ Content excerpts for better engagement
- ✅ Author attribution with avatars
- ✅ Slug-based SEO-friendly URLs
- ✅ Timestamp tracking
- ✅ Related posts suggestions

### 📊 Dashboard
- ✅ Beautiful statistics cards with gradients
- ✅ Recent content overview
- ✅ Quick action buttons
- ✅ Role-based menu visibility
- ✅ Welcome messages and tips

### 🎨 Design System
- ✅ CSS Variables for easy theming
- ✅ Gradient backgrounds
- ✅ Card-based layouts with shadows
- ✅ Badge components for status
- ✅ Modern button styles
- ✅ Responsive grid system
- ✅ Custom animations

## Installation

### Local Development

### 1. Configure Database

Copy `config/database.example.php` to `config/database.php` and update with your credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pixarboy_cms');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

**Note:** BASE_URL configuration is optional - the site uses relative paths and works automatically!

### 2. Install the Database

#### Option A: Using Migrations (Recommended)

Run migrations via command line:
```bash
php migrate.php migrate
```

Or visit `/admin/migrations.php` after logging in (see credentials below).

#### Option B: Legacy Installer

Visit: `http://localhost/install.php`

Both methods will:
- Create all necessary database tables
- Create default admin account
- Set up default categories

### 3. Default Credentials

**Username:** `admin`  
**Password:** `admin123`

⚠️ **Important:** Change this password immediately after first login!

### 4. Delete Installation File (Optional)

After successful installation, you can delete the `install.php` file for security:

```bash
rm install.php
```

**Note:** The migration system is the recommended approach going forward.

## Usage

### Accessing the CMS

- **Frontend:** `http://localhost/`
- **Login:** `http://localhost/login.php`
- **Admin Dashboard:** `http://localhost/admin/`

### User Roles

- **👑 Admin:** Full access to all features including user management
- **✏️ Editor:** Can manage content
- **👤 User:** Basic access (read-only)

### Managing Content

1. Log in to the admin panel
2. Navigate to "Content" in the menu
3. Click "Add New Content"
4. Fill in:
   - Title (required)
   - Content (required)
   - Excerpt (recommended for better engagement)
   - Status (Draft/Published/Archived)
5. Click "Save Content"

### Managing Users (Admin Only)

1. Navigate to "Users" in the admin menu
2. Click "Add New User"
3. Fill in user details
4. Assign role and status
5. Click "Save User"

### Managing Categories

1. Navigate to "Categories" in the admin menu
2. Create parent categories (top-level)
3. Create subcategories by selecting a parent
4. Organize with display order
5. Assign categories when creating/editing content

**Hierarchical Structure Example:**
```
📁 Reviews
  ├─ Audio
  ├─ Mobile
  └─ Laptops
📁 Tech
📁 Guides
```

## Database Migrations

PixarBoy includes a robust migration system for managing database schema changes safely.

### Why Migrations?

- ✅ **Version Control:** Track database changes like code
- ✅ **Reproducible:** Same schema on all environments
- ✅ **Reversible:** Rollback changes if needed
- ✅ **Safe:** Transactions prevent partial updates
- ✅ **Documented:** Each change is clearly explained

### Running Migrations

**Via Command Line (Recommended):**
```bash
# Show migration status
php migrate.php status

# Run pending migrations
php migrate.php migrate

# Rollback last batch
php migrate.php rollback

# Reset all (DANGER!)
php migrate.php reset
```

**Via Web Interface:**
Visit `/admin/migrations.php` (Admin only)

### Creating a Migration

1. Create a file in `database/migrations/`:
   ```
   002_your_migration_name.php
   ```

2. Use this template:
   ```php
   <?php
   class Migration_002_your_migration_name extends Migration {
       
       public function getName() {
           return 'Your Migration Name';
       }
       
       public function up() {
           // Apply changes
           $this->execute("ALTER TABLE...");
       }
       
       public function down() {
           // Revert changes
           $this->execute("ALTER TABLE...");
       }
   }
   ```

3. Run the migration:
   ```bash
   php migrate.php migrate
   ```

See `database/migrations/README.md` for detailed documentation.

## File Structure

### Hostinger Git Deployment Structure

```
pixarboy-site/                   # Repository root (deploys to public_html/)
├── config/                      # 🔒 Protected by .htaccess
│   ├── config.php               # App configuration
│   ├── database.php             # Database connection (NOT in git)
│   ├── database.example.php     # Example database config
│   ├── Migration.php            # Base migration class
│   └── MigrationRunner.php      # Migration execution engine
├── includes/                    # 🔒 Protected by .htaccess
│   ├── header.php               # Modern navigation header
│   ├── footer.php               # Enhanced footer with sections
│   └── Parsedown.php            # Markdown parser
├── database/                    # 🔒 Protected by .htaccess
│   └── migrations/              # Database migration files
│       ├── 001_create_base_tables.php  # Initial schema
│       └── README.md            # Migration documentation
├── admin/                       # ✅ Admin area
│   ├── index.php                # Modern dashboard with stats
│   ├── content.php              # Content management
│   ├── categories.php           # Hierarchical category management
│   ├── users.php                # User management
│   └── migrations.php           # Migration web interface (admin only)
├── assets/                      # ✅ Public assets
│   ├── css/
│   │   └── style.css            # Modern CSS with gradients & animations
│   ├── js/
│   │   └── main.js             # Interactive JavaScript features
│   └── images/
│       └── airpods-4-hero.svg  # Custom SVG illustrations
├── .htaccess                    # Security, routing & protection
├── .gitignore                   # Git ignore rules
├── index.php                    # Homepage with hero section & sidebar
├── post.php                     # Individual post view with Markdown
├── login.php                    # Beautiful login page
├── logout.php                   # Logout handler
├── migrate.php                  # CLI migration tool
├── install.php                  # Legacy database installer (optional)
├── HOSTINGER_GIT_DEPLOY.md      # Git deployment guide (recommended)
├── DEPLOYMENT.md                # Manual deployment guide
└── README.md                    # This file
```

**Security Note:** `config/` and `includes/` directories are protected by `.htaccess` rules that deny web access. They're in the repository but blocked from public access when deployed.

## Design Features

### Color Palette
- **Primary:** `#FF6B6B` (Coral Red)
- **Secondary:** `#4ECDC4` (Turquoise)
- **Accent:** `#FFE66D` (Yellow)
- **Dark:** `#2C3E50` (Navy)

### Typography
- **Font Family:** Inter (from Google Fonts)
- **Modern weights:** 400, 500, 600, 700, 800

### UI Components
- **Cards:** Rounded corners (16px), subtle shadows, hover effects
- **Buttons:** Multiple variants (primary, secondary, gradient, outline)
- **Forms:** Enhanced inputs with icons and smooth focus states
- **Tables:** Hover effects, modern styling, responsive
- **Badges:** Status indicators with color coding
- **Alerts:** Auto-dismissible with smooth animations

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

### Theme Customization

Edit CSS variables in `public/assets/css/style.css`:

```css
:root {
    --primary-color: #FF6B6B;
    --secondary-color: #4ECDC4;
    --accent-color: #FFE66D;
    --dark-color: #2C3E50;
    /* ... more variables */
}
```

## JavaScript Features

### Interactive Elements
- **Mobile Menu:** Hamburger menu with smooth animations
- **Smooth Scrolling:** For anchor links
- **Form Validation:** Enhanced with visual feedback
- **Auto-dismiss Alerts:** Notifications disappear after 5 seconds
- **Scroll Animations:** Elements fade in as you scroll
- **Character Counter:** For textareas with max length
- **Confirm Dialogs:** Before delete actions
- **Loading States:** Visual feedback on form submission

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
- Modern web browser (Chrome, Firefox, Safari, Edge)

### Browser Support
- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

### Hostinger Git Deployment (Recommended) 🚀

The easiest way to deploy! Use Hostinger's built-in Git deployment feature.

**Quick Start:**
1. In Hostinger hPanel: **Advanced** → **Git** → **Create Repository**
2. Repository: `https://github.com/goal1860/pixarboy-site.git`
3. Branch: `main`
4. Directory: *(leave blank)*
5. Click Create

For complete step-by-step instructions, see **[HOSTINGER_GIT_DEPLOY.md](HOSTINGER_GIT_DEPLOY.md)**.

### Manual Hostinger Deployment

For manual FTP/File Manager deployment instructions, see **[DEPLOYMENT.md](DEPLOYMENT.md)**.

### Local Development

This project works with any PHP development environment:
- **XAMPP/MAMP**: Place project in htdocs, point to `public_html/`
- **PHP Built-in**: `cd public_html && php -S localhost:8000`
- **Laradock/Docker**: Configure document root to `public_html/`

## Responsive Breakpoints

- **Desktop:** 1200px+
- **Tablet:** 768px - 1199px
- **Mobile:** 320px - 767px

## Performance

- **Minimal Dependencies:** No heavy frameworks
- **Optimized CSS:** Efficient selectors and minimal nesting
- **Lazy Loading:** Images can be lazy loaded
- **Smooth Animations:** GPU-accelerated transforms

## Future Enhancements

Potential improvements for future versions:
- [ ] WYSIWYG editor (TinyMCE/CKEditor)
- [ ] Image upload and media library
- [ ] Category management UI
- [ ] Advanced search functionality
- [ ] Pagination for listings
- [ ] User profile editing
- [ ] Email notifications
- [ ] SEO metadata fields
- [ ] REST API endpoints
- [ ] Multi-language support
- [ ] Dark mode toggle
- [ ] Comments system
- [ ] Bookmark/favorite posts

## Design Inspiration

This design is inspired by modern WordPress themes like **Puva**, featuring:
- Clean, minimalist aesthetics
- Vibrant gradients and colors
- Card-based layouts
- Modern typography
- Smooth animations
- Mobile-first approach

## Changelog

### Version 2.0 (Current)
- Complete UI redesign with modern aesthetics
- Added hero section on homepage
- Implemented responsive grid layouts
- Enhanced admin dashboard with stats cards
- Added post detail pages with related content
- Implemented mobile menu with hamburger icon
- Added smooth animations and transitions
- Enhanced forms with icons and better UX
- Improved footer with multiple sections
- Added JavaScript interactions

### Version 1.0
- Initial release
- Basic CRUD operations
- User authentication
- Role-based access control

## License

MIT License - Feel free to use and modify as needed.

## Credits

- **Design Inspiration:** Puva WordPress Theme
- **Font:** Inter by Rasmus Andersson
- **Icons:** Heroicons (SVG)

## Support

For issues or questions, please check the code documentation or create an issue in your repository.

---

Made with ❤️ by PixarBoy Team
