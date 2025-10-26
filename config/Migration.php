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
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Helper: Check if column exists
     */
    protected function columnExists($tableName, $columnName) {
        $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE ?");
        $stmt->execute([$columnName]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Helper: Check if index exists
     */
    protected function indexExists($tableName, $indexName) {
        $stmt = $this->pdo->prepare("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?");
        $stmt->execute([$indexName]);
        return $stmt->rowCount() > 0;
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

