<?php
/**
 * Base Migration Class
 * All migrations should extend this class
 */
abstract class Migration {
    protected $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Run the migration
     */
    abstract public function up();
    
    /**
     * Reverse the migration
     */
    abstract public function down();
    
    /**
     * Get migration name/identifier
     */
    abstract public function getName();
    
    /**
     * Helper: Check if table exists
     */
    protected function tableExists($tableName) {
        try {
            // Use INFORMATION_SCHEMA for reliable table checking
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ?
            ");
            $stmt->execute([$tableName]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Helper: Check if column exists
     */
    protected function columnExists($tableName, $columnName) {
        try {
            // Use INFORMATION_SCHEMA for reliable column checking
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = ?
            ");
            $stmt->execute([$tableName, $columnName]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Helper: Check if index exists
     */
    protected function indexExists($tableName, $indexName) {
        try {
            // Use INFORMATION_SCHEMA for reliable index checking
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND INDEX_NAME = ?
            ");
            $stmt->execute([$tableName, $indexName]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Helper: Safe query execution with error handling
     */
    protected function execute($sql, $errorMessage = null) {
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            $message = $errorMessage ?? "SQL Error: " . $e->getMessage();
            throw new Exception($message . "\nSQL: " . $sql);
        }
    }
}

