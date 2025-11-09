# Scripts Documentation

This directory contains utility scripts for managing the CMS.

## import_article.php

Generalized script to import markdown articles/reviews to the database.

### Features

- ✅ Extracts H1 heading (`# Title`) as article title
- ✅ Removes H1 from content body to prevent duplicate titles
- ✅ Generates URL-safe slug automatically
- ✅ Auto-generates excerpt from content
- ✅ Supports both local and production databases
- ✅ Handles existing articles (update or create new)
- ✅ Interactive conflict resolution

### Usage

```bash
php scripts/import_article.php <path-to-markdown-file> [--local]
```

### Parameters

- **`<path-to-markdown-file>`** (required): Path to the markdown file to import
  - Can be relative or absolute path
  - Script will try to resolve relative paths from project root
  
- **`--local`** or **`-l`** or **`local`** (optional): Import to local database
  - Default: imports to production database
  - When set: imports to local development database

### Examples

#### Import to Production (Default)

```bash
# Import from tmp directory
php scripts/import_article.php tmp/my-article.md

# Import with absolute path
php scripts/import_article.php /path/to/article.md

# Import from current directory
php scripts/import_article.php ./article.md
```

#### Import to Local Database

```bash
# Using --local flag
php scripts/import_article.php tmp/my-article.md --local

# Using -l flag
php scripts/import_article.php tmp/my-article.md -l

# Using 'local' keyword
php scripts/import_article.php tmp/my-article.md local
```

### Markdown File Format

The script expects a markdown file with:

1. **H1 Heading** (recommended): First line should be `# Article Title`
   - The H1 will be extracted as the title
   - The H1 will be removed from the content body
   
2. **Content**: Rest of the markdown content

#### Example Markdown File

```markdown
# My Awesome Article Title

This is the introduction paragraph of my article.

## Section 1

More content here...

## Section 2

Even more content...
```

**Note:** If no H1 heading is found, the script will:
- Use the first non-empty line as the title (with a warning)
- If no content is found, it will use "Untitled Article" as default

### Database Configuration

#### Production Database

The script uses these credentials for production:
- **Host:** `srv448.hstgr.io`
- **Database:** `u697935469_pixarboy`
- **User:** `u697935469_pixarboy`
- **Password:** (stored in script)

#### Local Database

The script reads local database credentials from `config/database.php`:
- **Host:** `127.0.0.1:3306` (for Docker compatibility)
- **Database:** `pixarboy_cms` (from config)
- **User:** `default` (from config)
- **Password:** `secret` (from config)

### Behavior

#### New Article

When importing a new article:
1. Extracts title from H1 heading
2. Generates URL-safe slug from title
3. Creates excerpt automatically
4. Publishes article with status `published`
5. Assigns to first available admin user

#### Existing Article

When an article with the same slug already exists:

1. **If titles match:** Automatically updates the existing article
2. **If titles differ:** Prompts for user input:
   - Press Enter: Update existing article
   - Type `new`: Create new article with date suffix in slug
   - Type `cancel`: Abort import

### Output

The script provides detailed output:

```
=====================================
Import Article to Database
Environment: PRODUCTION
=====================================

✓ Connected to database successfully!

✓ Article file loaded: tmp/my-article.md
✓ Title extracted: My Awesome Article Title
✓ H1 removed from content body
✓ Slug: my-awesome-article-title
✓ Excerpt generated
✓ Author ID: 1

Creating new article...

✓ Article published successfully!

=====================================
✓ SUCCESS!
=====================================
Article ID: 5
Article URL: /post/my-awesome-article-title
=====================================
```

### Slug Generation

The script generates URL-safe slugs by:
1. Converting to lowercase
2. Removing all non-alphanumeric characters (except spaces and hyphens)
3. Replacing spaces and multiple hyphens with single hyphens
4. Trimming hyphens from start and end

**Example:**
- Title: `Apple MacBook Air 15-inch (2025)`
- Slug: `apple-macbook-air-15-inch-2025`

### Error Handling

The script handles various error scenarios:

- **File not found:** Shows tried paths and exits
- **Database connection failed:** Shows error message
- **No users in database:** Prompts to create admin user first
- **Slug conflicts:** Interactive resolution

### Security Notes

⚠️ **Important:** This script contains database credentials. 

- **For production:** Credentials are hardcoded in the script
- **For local:** Credentials are read from `config/database.php`
- **Recommendation:** 
  - Restrict file permissions: `chmod 600 scripts/import_article.php`
  - Don't commit production credentials to version control
  - Consider using environment variables for production credentials

### Troubleshooting

#### "Database connection failed"

**Local:**
- Ensure MySQL is running in Docker
- Check that port 3306 is accessible
- Verify credentials in `config/database.php`

**Production:**
- Check network connectivity
- Verify database credentials
- Ensure database server is accessible

#### "No users found in database"

Create an admin user first:
- Use the admin panel: `/admin/users.php`
- Or run migrations: `php migrate.php`

#### "File not found"

- Use absolute paths if relative paths don't work
- Check file permissions
- Ensure file exists and is readable

### Related Scripts

- `tmp/create_product_and_import_review.php` - Creates product and imports review
- `tmp/import_article.php` - Legacy import script (production only)

### See Also

- [Main README](../README.md)
- [Deployment Guide](../DEPLOYMENT.md)

