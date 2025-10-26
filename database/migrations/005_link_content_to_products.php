<?php

require_once __DIR__ . '/../../config/Migration.php';

class Migration_005_link_content_to_products extends Migration {
    
    public function up() {
        // Add product_id column to content table
        if (!$this->columnExists('content', 'product_id')) {
            $this->execute("
                ALTER TABLE content 
                ADD COLUMN product_id INT NULL AFTER author_id,
                ADD INDEX idx_product_id (product_id)
            ");
            
            // Add foreign key constraint
            $this->execute("
                ALTER TABLE content
                ADD CONSTRAINT fk_content_product 
                FOREIGN KEY (product_id) REFERENCES products(id) 
                ON DELETE SET NULL
            ");
        }
    }
    
    public function down() {
        if ($this->columnExists('content', 'product_id')) {
            // Drop foreign key first (check if it exists)
            try {
                $this->execute("ALTER TABLE content DROP FOREIGN KEY fk_content_product");
            } catch (PDOException $e) {
                // Foreign key might not exist, continue
            }
            
            // Drop the index
            try {
                $this->execute("ALTER TABLE content DROP INDEX idx_product_id");
            } catch (PDOException $e) {
                // Index might not exist, continue
            }
            
            // Drop the column
            $this->execute("ALTER TABLE content DROP COLUMN product_id");
        }
    }
    
    public function getName() {
        return "Link Content to Products";
    }
}

