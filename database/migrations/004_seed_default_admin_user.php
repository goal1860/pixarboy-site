<?php
/**
 * Migration: Seed Default Admin User
 * 
 * Creates a default admin user for initial access
 */
class Migration_004_seed_default_admin_user extends Migration {
    
    public function getName() {
        return 'Seed Default Admin User';
    }
    
    public function up() {
        // Check if admin user already exists
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        if ($stmt->fetchColumn() > 0) {
            echo "(Admin user already exists, skipping) ";
            return;
        }
        
        // Create default admin user
        // Username: admin
        // Password: admin123
        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password, role, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'admin',
            'admin@pixarboy.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin',
            'active'
        ]);
        
        echo "(Admin user created: admin / admin123) ";
    }
    
    public function down() {
        // Remove the default admin user
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE username = ? AND email = ?");
        $stmt->execute(['admin', 'admin@pixarboy.com']);
    }
}

