<?php
// Database Installation Script
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'editor', 'user') DEFAULT 'user',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Create content table
    $pdo->exec("CREATE TABLE IF NOT EXISTS content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        content TEXT NOT NULL,
        excerpt TEXT,
        status ENUM('published', 'draft', 'archived') DEFAULT 'draft',
        author_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Create categories table with hierarchical support
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
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
        INDEX idx_slug (slug)
    )");
    
    // Create content_categories junction table
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_categories (
        content_id INT,
        category_id INT,
        PRIMARY KEY (content_id, category_id),
        FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        INDEX idx_content (content_id),
        INDEX idx_category (category_id)
    )");
    
    // Add some default categories if none exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $defaultCategories = [
            ['name' => 'Reviews', 'slug' => 'reviews', 'description' => 'Product reviews and comparisons', 'parent_id' => null],
            ['name' => 'Tech', 'slug' => 'tech', 'description' => 'Technology news and updates', 'parent_id' => null],
            ['name' => 'Guides', 'slug' => 'guides', 'description' => 'How-to guides and tutorials', 'parent_id' => null],
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id) VALUES (?, ?, ?, ?)");
        foreach ($defaultCategories as $cat) {
            $stmt->execute([$cat['name'], $cat['slug'], $cat['description'], $cat['parent_id']]);
        }
        
        // Add some subcategories
        $reviewsId = $pdo->lastInsertId() - 2; // First category inserted
        $subCategories = [
            ['name' => 'Audio', 'slug' => 'audio', 'description' => 'Audio equipment reviews', 'parent_id' => $reviewsId],
            ['name' => 'Mobile', 'slug' => 'mobile', 'description' => 'Mobile device reviews', 'parent_id' => $reviewsId],
        ];
        
        foreach ($subCategories as $cat) {
            $stmt->execute([$cat['name'], $cat['slug'], $cat['description'], $cat['parent_id']]);
        }
        
        $categoriesCreated = true;
    }
    
    // Check if admin user exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    if ($stmt->fetchColumn() == 0) {
        // Create default admin user (password: admin123)
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@pixarboy.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
        $adminCreated = true;
    }
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Installation Complete - PixarBoy</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>✓ Installation Complete!</h1>
    <div class='success'>
        <strong>Database tables created successfully!</strong><br><br>
        - users<br>
        - content<br>
        - categories (with hierarchical support)<br>
        - content_categories
    </div>
    
    <div class='warning'>
        <strong>⚠️ Note:</strong> This installer is for legacy support.<br><br>
        <strong>Recommended:</strong> Use the <a href='/admin/migrations.php'>migration system</a> for fresh installs and updates.<br>
        Run: <code style='background: rgba(0,0,0,0.1); padding: 2px 6px; border-radius: 3px;'>php migrate.php</code>
    </div>";
    
    if (isset($adminCreated)) {
        echo "<div class='info'>
            <strong>Admin Account Created:</strong><br>
            Username: <strong>admin</strong><br>
            Password: <strong>admin123</strong><br>
            <em>Please change this password after logging in!</em>
        </div>";
    }
    
    if (isset($categoriesCreated)) {
        echo "<div class='info'>
            <strong>Default Categories Created:</strong><br>
            - Reviews (with Audio and Mobile subcategories)<br>
            - Tech<br>
            - Guides
        </div>";
    }
    
    echo "<a href='login.php' class='btn'>Go to Login</a>
    <br><br>
    <p><em>You can safely delete this install.php file after installation.</em></p>
</body>
</html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head><title>Installation Error</title></head>
<body>
    <h1>Installation Error</h1>
    <div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>
        <strong>Error:</strong> " . $e->getMessage() . "
    </div>
    <p>Please check your database configuration in config/database.php</p>
</body>
</html>";
}

