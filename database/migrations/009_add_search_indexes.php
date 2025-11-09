<?php

require_once __DIR__ . '/../../config/Migration.php';

/**
 * Migration: Add Search Indexes
 * 
 * Adds indexes to improve search performance:
 * - FULLTEXT index on title, content, and excerpt for fast text search
 * - Composite index on (status, created_at) for common query patterns
 */
class Migration_009_add_search_indexes extends Migration {
    
    public function getName() {
        return 'Add Search Indexes';
    }
    
    public function up() {
        // Add FULLTEXT index for search (InnoDB supports FULLTEXT since MySQL 5.6)
        // This provides much faster and better relevance scoring for text search
        if (!$this->indexExists('content', 'idx_fulltext_search')) {
            try {
                $this->execute("
                    ALTER TABLE content 
                    ADD FULLTEXT INDEX idx_fulltext_search (title, content, excerpt)
                ", "Failed to create FULLTEXT index");
                echo "✓ Created FULLTEXT index on (title, content, excerpt)\n";
            } catch (Exception $e) {
                // If FULLTEXT fails (e.g., MyISAM required or version too old), create prefix indexes instead
                echo "⚠ FULLTEXT index not available: " . $e->getMessage() . "\n";
                echo "  Creating prefix indexes instead for LIKE search optimization\n";
                
                // Add prefix index on title (first 100 chars) for better LIKE performance
                if (!$this->indexExists('content', 'idx_title_prefix')) {
                    try {
                        $this->execute("
                            ALTER TABLE content 
                            ADD INDEX idx_title_prefix (title(100))
                        ", "Failed to create title prefix index");
                        echo "✓ Created prefix index on title(100)\n";
                    } catch (Exception $e2) {
                        echo "⚠ Could not create title prefix index: " . $e2->getMessage() . "\n";
                    }
                }
            }
        } else {
            echo "✓ FULLTEXT index already exists\n";
        }
        
        // Add composite index on (status, created_at) for common query pattern
        // This helps queries that filter by status and order by created_at
        if (!$this->indexExists('content', 'idx_status_created')) {
            $this->execute("
                ALTER TABLE content 
                ADD INDEX idx_status_created (status, created_at DESC)
            ", "Failed to create composite index");
            echo "✓ Created composite index on (status, created_at)\n";
        } else {
            echo "✓ Composite index already exists\n";
        }
        
        // Add index on title for exact/prefix matches (helps with title LIKE queries)
        if (!$this->indexExists('content', 'idx_title')) {
            try {
                // Try prefix index first (works with VARCHAR)
                $this->execute("
                    ALTER TABLE content 
                    ADD INDEX idx_title (title(50))
                ", "Failed to create title index");
                echo "✓ Created prefix index on title(50)\n";
            } catch (PDOException $e) {
                // If that fails, try full column index
                try {
                    $this->execute("
                        ALTER TABLE content 
                        ADD INDEX idx_title (title)
                    ", "Failed to create title index");
                    echo "✓ Created index on title\n";
                } catch (PDOException $e2) {
                    echo "⚠ Could not create title index: " . $e2->getMessage() . "\n";
                }
            }
        } else {
            echo "✓ Title index already exists\n";
        }
    }
    
    public function down() {
        // Drop indexes in reverse order
        if ($this->indexExists('content', 'idx_title')) {
            $this->execute("ALTER TABLE content DROP INDEX idx_title");
            echo "✓ Dropped title index\n";
        }
        
        if ($this->indexExists('content', 'idx_status_created')) {
            $this->execute("ALTER TABLE content DROP INDEX idx_status_created");
            echo "✓ Dropped composite index\n";
        }
        
        if ($this->indexExists('content', 'idx_fulltext_search')) {
            $this->execute("ALTER TABLE content DROP INDEX idx_fulltext_search");
            echo "✓ Dropped FULLTEXT index\n";
        }
        
        if ($this->indexExists('content', 'idx_title_prefix')) {
            $this->execute("ALTER TABLE content DROP INDEX idx_title_prefix");
            echo "✓ Dropped title prefix index\n";
        }
    }
}

