<?php

require_once __DIR__ . '/../../config/Migration.php';

/**
 * Migration: Add Brand Field to Products
 * 
 * Adds a brand field to the products table to store product brand/manufacturer information
 */
class Migration_011_add_brand_to_products extends Migration {
    
    public function getName() {
        return 'Add Brand Field to Products';
    }
    
    public function up() {
        // Add brand column to products table
        if (!$this->columnExists('products', 'brand')) {
            $this->execute("
                ALTER TABLE products 
                ADD COLUMN brand VARCHAR(100) NULL AFTER name,
                ADD INDEX idx_brand (brand)
            ", "Failed to add brand column to products table");
        }
    }
    
    public function down() {
        if ($this->columnExists('products', 'brand')) {
            // Drop index first
            try {
                $this->execute("ALTER TABLE products DROP INDEX idx_brand");
            } catch (PDOException $e) {
                // Index might not exist, continue
            }
            
            // Drop the column
            $this->execute("ALTER TABLE products DROP COLUMN brand");
        }
    }
}

