<?php

require_once __DIR__ . '/../../config/Migration.php';

/**
 * Migration: Create Contact Messages Table
 * 
 * Creates a table to store contact form submissions
 */
class Migration_012_create_contact_messages_table extends Migration {
    
    public function getName() {
        return 'Create Contact Messages Table';
    }
    
    public function up() {
        // Create contact_messages table
        if (!$this->tableExists('contact_messages')) {
            $this->execute("
                CREATE TABLE contact_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    status ENUM('new', 'read', 'replied', 'archived') DEFAULT 'new',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_status (status),
                    INDEX idx_created (created_at),
                    INDEX idx_email (email)
                )
            ", "Failed to create contact_messages table");
        }
    }
    
    public function down() {
        if ($this->tableExists('contact_messages')) {
            $this->execute("DROP TABLE contact_messages");
        }
    }
}

