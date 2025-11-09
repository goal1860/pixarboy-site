<?php

require_once __DIR__ . '/../../config/Migration.php';

/**
 * Migration: Add Tags Support
 * 
 * Adds tag functionality for articles:
 * - tags table (for tag definitions)
 * - content_tags junction table (many-to-many relationship between content and tags)
 */
class Migration_010_add_tags_support extends Migration {
    
    public function getName() {
        return 'Add Tags Support';
    }
    
    public function up() {
        // Create tags table
        if (!$this->tableExists('tags')) {
            $this->execute("
                CREATE TABLE tags (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) UNIQUE NOT NULL,
                    slug VARCHAR(100) UNIQUE NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_slug (slug),
                    INDEX idx_name (name)
                )
            ", "Failed to create tags table");
            echo "✓ Created tags table\n";
        } else {
            echo "✓ Tags table already exists\n";
        }
        
        // Create content_tags junction table
        if (!$this->tableExists('content_tags')) {
            $this->execute("
                CREATE TABLE content_tags (
                    content_id INT NOT NULL,
                    tag_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (content_id, tag_id),
                    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
                    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
                    INDEX idx_content (content_id),
                    INDEX idx_tag (tag_id)
                )
            ", "Failed to create content_tags table");
            echo "✓ Created content_tags junction table\n";
        } else {
            echo "✓ Content_tags table already exists\n";
        }
    }
    
    public function down() {
        // Drop junction table first (due to foreign keys)
        if ($this->tableExists('content_tags')) {
            // Get actual foreign key names from INFORMATION_SCHEMA
            $stmt = $this->pdo->prepare("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'content_tags' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $stmt->execute();
            $foreignKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Drop each foreign key
            foreach ($foreignKeys as $fkName) {
                try {
                    // Use backticks for MySQL identifier quoting
                    $this->execute("ALTER TABLE content_tags DROP FOREIGN KEY `" . str_replace('`', '``', $fkName) . "`");
                } catch (Exception $e) {
                    // Continue if foreign key doesn't exist or already dropped
                }
            }
            
            $this->execute("DROP TABLE content_tags");
            echo "✓ Dropped content_tags table\n";
        }
        
        // Drop tags table
        if ($this->tableExists('tags')) {
            $this->execute("DROP TABLE tags");
            echo "✓ Dropped tags table\n";
        }
    }
}

