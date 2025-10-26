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
    
    // Create categories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create content_categories junction table
    $pdo->exec("CREATE TABLE IF NOT EXISTS content_categories (
        content_id INT,
        category_id INT,
        PRIMARY KEY (content_id, category_id),
        FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    )");
    
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
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>✓ Installation Complete!</h1>
    <div class='success'>
        <strong>Database tables created successfully!</strong><br><br>
        - users<br>
        - content<br>
        - categories<br>
        - content_categories
    </div>";
    
    if (isset($adminCreated)) {
        echo "<div class='info'>
            <strong>Admin Account Created:</strong><br>
            Username: <strong>admin</strong><br>
            Password: <strong>admin123</strong><br>
            <em>Please change this password after logging in!</em>
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

