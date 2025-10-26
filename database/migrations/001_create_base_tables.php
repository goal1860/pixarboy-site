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
        
        // Create basic categories table (without hierarchy)
        if (!$this->tableExists('categories')) {
            $this->execute("
                CREATE TABLE categories (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) UNIQUE NOT NULL,
                    slug VARCHAR(100) UNIQUE NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ", "Failed to create categories table");
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
            $this->execute("DROP TABLE content_categories");
        }
        
        if ($this->tableExists('categories')) {
            $this->execute("DROP TABLE categories");
        }
        
        if ($this->tableExists('content')) {
            $this->execute("DROP TABLE content");
        }
        
        if ($this->tableExists('users')) {
            $this->execute("DROP TABLE users");
        }
    }
}

