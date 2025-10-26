<?php
/**
 * Migration: Create Base Tables
 * 
 * Creates the initial database structure:
 * - users table (for authors/admins)
 * - content table (for reviews/articles)
 * - products table (for affiliate products)
 * - categories table (for product categories)
 * - product_categories junction table (many-to-many)
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
        
        // Create products table
        if (!$this->tableExists('products')) {
            $this->execute("
                CREATE TABLE products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    description TEXT,
                    price DECIMAL(10, 2),
                    currency VARCHAR(3) DEFAULT 'USD',
                    affiliate_link VARCHAR(500),
                    image_url VARCHAR(500),
                    rating DECIMAL(2, 1) DEFAULT 0,
                    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_slug (slug),
                    INDEX idx_status (status),
                    INDEX idx_rating (rating)
                )
            ", "Failed to create products table");
        }
        
        // Create product_categories junction table
        if (!$this->tableExists('product_categories')) {
            $this->execute("
                CREATE TABLE product_categories (
                    product_id INT,
                    category_id INT,
                    PRIMARY KEY (product_id, category_id),
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                    INDEX idx_product (product_id),
                    INDEX idx_category (category_id)
                )
            ", "Failed to create product_categories table");
        }
    }
    
    public function down() {
        // Drop tables in reverse order (respecting foreign keys)
        if ($this->tableExists('product_categories')) {
            $this->execute("DROP TABLE product_categories");
        }
        
        if ($this->tableExists('products')) {
            $this->execute("DROP TABLE products");
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

