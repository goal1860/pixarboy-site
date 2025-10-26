<?php
/**
 * Migration: Add Hierarchical Categories Support
 * 
 * Adds parent_id and display_order to categories table
 * to support hierarchical category structure
 */
class Migration_002_add_hierarchical_categories extends Migration {
    
    public function getName() {
        return 'Add Hierarchical Categories Support';
    }
    
    public function up() {
        // Add parent_id column if it doesn't exist
        if (!$this->columnExists('categories', 'parent_id')) {
            $this->execute("
                ALTER TABLE categories 
                ADD COLUMN parent_id INT DEFAULT NULL AFTER description,
                ADD FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
                ADD INDEX idx_parent (parent_id)
            ");
        }
        
        // Add display_order column if it doesn't exist
        if (!$this->columnExists('categories', 'display_order')) {
            $this->execute("
                ALTER TABLE categories 
                ADD COLUMN display_order INT DEFAULT 0 AFTER parent_id,
                ADD INDEX idx_order (display_order)
            ");
        }
        
        // Add updated_at column if it doesn't exist
        if (!$this->columnExists('categories', 'updated_at')) {
            $this->execute("
                ALTER TABLE categories 
                ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at
            ");
        }
        
        // Remove UNIQUE constraint from name if it exists
        // (to allow same name in different parent categories)
        if ($this->indexExists('categories', 'name')) {
            try {
                $this->execute("ALTER TABLE categories DROP INDEX name");
            } catch (Exception $e) {
                // Index might not be removable or might not exist, ignore
            }
        }
    }
    
    public function down() {
        // Remove foreign key first
        // Note: Foreign key name might vary, so we try to drop it
        try {
            // Get foreign key name
            $stmt = $this->pdo->prepare("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'categories'
                AND COLUMN_NAME = 'parent_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $stmt->execute();
            $fkName = $stmt->fetchColumn();
            
            if ($fkName) {
                $this->execute("ALTER TABLE categories DROP FOREIGN KEY `{$fkName}`");
            }
        } catch (Exception $e) {
            // Foreign key might not exist, continue
        }
        
        // Drop indexes
        if ($this->indexExists('categories', 'idx_parent')) {
            $this->execute("ALTER TABLE categories DROP INDEX idx_parent");
        }
        
        if ($this->indexExists('categories', 'idx_order')) {
            $this->execute("ALTER TABLE categories DROP INDEX idx_order");
        }
        
        // Drop columns
        if ($this->columnExists('categories', 'parent_id')) {
            $this->execute("ALTER TABLE categories DROP COLUMN parent_id");
        }
        
        if ($this->columnExists('categories', 'display_order')) {
            $this->execute("ALTER TABLE categories DROP COLUMN display_order");
        }
        
        if ($this->columnExists('categories', 'updated_at')) {
            $this->execute("ALTER TABLE categories DROP COLUMN updated_at");
        }
        
        // Re-add UNIQUE constraint to name
        if (!$this->indexExists('categories', 'name')) {
            try {
                $this->execute("ALTER TABLE categories ADD UNIQUE KEY name (name)");
            } catch (Exception $e) {
                // Might fail if there are duplicate names, that's ok
            }
        }
    }
}

