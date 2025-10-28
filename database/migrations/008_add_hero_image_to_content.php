<?php

require_once __DIR__ . '/../../config/Migration.php';

/**
 * Migration: Add Hero Image to Content
 * 
 * Adds hero_image_url column to content table for custom post images
 */
class Migration_008_add_hero_image_to_content extends Migration {
    
    public function up() {
        if (!$this->columnExists('content', 'hero_image_url')) {
            $this->execute("
                ALTER TABLE content 
                ADD COLUMN hero_image_url VARCHAR(500) NULL AFTER excerpt
            ");
        }
    }
    
    public function down() {
        if ($this->columnExists('content', 'hero_image_url')) {
            $this->execute("ALTER TABLE content DROP COLUMN hero_image_url");
        }
    }
    
    public function getName() {
        return "Add Hero Image to Content";
    }
}

