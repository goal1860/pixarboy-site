# Database Migrations

This directory contains database migration files for managing schema changes.

## Migration File Format

Migration files should follow this naming convention:
```
###_description_of_migration.php
```

Example: `001_add_hierarchical_categories.php`

## Migration Class Structure

Each migration file must contain a class that extends `Migration`:

```php
<?php
class Migration_###_description extends Migration {
    
    public function getName() {
        return 'Human Readable Migration Name';
    }
    
    public function up() {
        // Apply the migration
        $this->execute("ALTER TABLE...");
    }
    
    public function down() {
        // Revert the migration
        $this->execute("ALTER TABLE...");
    }
}
```

## Helper Methods

The `Migration` base class provides these helper methods:

- `tableExists($tableName)` - Check if a table exists
- `columnExists($tableName, $columnName)` - Check if a column exists
- `indexExists($tableName, $indexName)` - Check if an index exists
- `execute($sql, $errorMessage)` - Execute SQL with error handling

## Running Migrations

### Via Web Interface
Visit: `/admin/migrations.php` (Admin only)

### Via Command Line
```bash
# Show migration status
php migrate.php status

# Run pending migrations
php migrate.php migrate

# Rollback last batch
php migrate.php rollback

# Reset all migrations (DANGER!)
php migrate.php reset
```

## Best Practices

1. **Always test migrations** on a development database first
2. **Write reversible migrations** - implement both `up()` and `down()`
3. **Check before altering** - use helper methods to check existence
4. **Use transactions** - migrations run in transactions automatically
5. **Keep migrations small** - one logical change per migration
6. **Never edit executed migrations** - create a new migration instead
7. **Document complex changes** - add comments in the migration

## Migration Order

Migrations are executed in alphabetical order based on filename. Use numbered prefixes:
- `001_initial_setup.php`
- `002_add_categories.php`
- `003_add_user_profiles.php`

## Example Migration

```php
<?php
class Migration_002_add_featured_flag_to_posts extends Migration {
    
    public function getName() {
        return 'Add Featured Flag to Posts';
    }
    
    public function up() {
        if (!$this->columnExists('content', 'is_featured')) {
            $this->execute("
                ALTER TABLE content 
                ADD COLUMN is_featured BOOLEAN DEFAULT FALSE AFTER status,
                ADD INDEX idx_featured (is_featured)
            ");
        }
    }
    
    public function down() {
        if ($this->columnExists('content', 'is_featured')) {
            $this->execute("
                ALTER TABLE content 
                DROP COLUMN is_featured
            ");
        }
    }
}
```

## Troubleshooting

### Migration Failed
- Check the error message in the output
- Verify database credentials
- Ensure tables/columns don't already exist
- Check for syntax errors in SQL

### Migration Stuck
- Check the `migrations` table for records
- Verify file naming matches class name
- Ensure migration class extends `Migration`

### Cannot Rollback
- Ensure `down()` method is properly implemented
- Check for circular dependencies
- Verify foreign keys can be dropped

