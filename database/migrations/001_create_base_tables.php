<?php
/**
 * Migration: Create Base Tables
 * 
 * Creates the initial database structure:
 * - users table
 * - content table
 * - categories table (with hierarchy support)
 * - content_categories junction table
 */
class Migration_001_create_base_tables extends Migration {
    
    public function getName() {
        return 'Create Base Tables';
    }
    
    public function up() {
        // Create users table
        if (!$this->tableExists('users')) {
            $this->execute("
                CREATE TABLE users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin', 'editor', 'user') DEFAULT 'user',
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_username (username),
                    INDEX idx_email (email),
                    INDEX idx_status (status),
                    INDEX idx_role (role)
                )
            ", "Failed to create users table");
            
            // Create default admin user (password: admin123)
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                'admin', 
                'admin@pixarboy.com', 
                password_hash('admin123', PASSWORD_DEFAULT), 
                'admin'
            ]);
        }
        
        // Create content table
        if (!$this->tableExists('content')) {
            $this->execute("
                CREATE TABLE content (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    content TEXT NOT NULL,
                    excerpt TEXT,
                    status ENUM('published', 'draft', 'archived') DEFAULT 'draft',
                    author_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_slug (slug),
                    INDEX idx_status (status),
                    INDEX idx_author (author_id),
                    INDEX idx_created (created_at)
                )
            ", "Failed to create content table");
        }
        
        // Create categories table with hierarchical support
        if (!$this->tableExists('categories')) {
            $this->execute("
                CREATE TABLE categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    slug VARCHAR(100) UNIQUE NOT NULL,
                    description TEXT,
                    parent_id INT DEFAULT NULL,
                    display_order INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
                    INDEX idx_parent (parent_id),
                    INDEX idx_slug (slug),
                    INDEX idx_order (display_order)
                )
            ", "Failed to create categories table");
            
            // Add default categories
            $defaultCategories = [
                ['name' => 'Reviews', 'slug' => 'reviews', 'description' => 'Product reviews and comparisons', 'parent_id' => null, 'order' => 1],
                ['name' => 'Tech', 'slug' => 'tech', 'description' => 'Technology news and updates', 'parent_id' => null, 'order' => 2],
                ['name' => 'Guides', 'slug' => 'guides', 'description' => 'How-to guides and tutorials', 'parent_id' => null, 'order' => 3],
            ];
            
            $stmt = $this->pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, display_order) VALUES (?, ?, ?, ?, ?)");
            $insertedIds = [];
            
            foreach ($defaultCategories as $cat) {
                $stmt->execute([$cat['name'], $cat['slug'], $cat['description'], $cat['parent_id'], $cat['order']]);
                $insertedIds[$cat['slug']] = $this->pdo->lastInsertId();
            }
            
            // Add subcategories to Reviews
            $subCategories = [
                ['name' => 'Audio', 'slug' => 'audio', 'description' => 'Audio equipment reviews', 'parent_id' => $insertedIds['reviews'], 'order' => 1],
                ['name' => 'Mobile', 'slug' => 'mobile', 'description' => 'Mobile device reviews', 'parent_id' => $insertedIds['reviews'], 'order' => 2],
                ['name' => 'Laptops', 'slug' => 'laptops', 'description' => 'Laptop reviews', 'parent_id' => $insertedIds['reviews'], 'order' => 3],
            ];
            
            foreach ($subCategories as $cat) {
                $stmt->execute([$cat['name'], $cat['slug'], $cat['description'], $cat['parent_id'], $cat['order']]);
            }
        }
        
        // Create content_categories junction table
        if (!$this->tableExists('content_categories')) {
            $this->execute("
                CREATE TABLE content_categories (
                    content_id INT,
                    category_id INT,
                    PRIMARY KEY (content_id, category_id),
                    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                    INDEX idx_content (content_id),
                    INDEX idx_category (category_id)
                )
            ", "Failed to create content_categories table");
        }
    }
    
    public function down() {
        // Drop tables in reverse order (respecting foreign keys)
        if ($this->tableExists('content_categories')) {
            $this->execute("DROP TABLE content_categories", "Failed to drop content_categories");
        }
        
        if ($this->tableExists('categories')) {
            $this->execute("DROP TABLE categories", "Failed to drop categories");
        }
        
        if ($this->tableExists('content')) {
            $this->execute("DROP TABLE content", "Failed to drop content");
        }
        
        if ($this->tableExists('users')) {
            $this->execute("DROP TABLE users", "Failed to drop users");
        }
    }
}

