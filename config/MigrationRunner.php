<?php
require_once __DIR__ . '/Migration.php';

/**
 * Migration Runner
 * Manages and executes database migrations
 */
class MigrationRunner {
    private $pdo;
    private $migrationsPath;
    
    public function __construct($pdo, $migrationsPath = null) {
        $this->pdo = $pdo;
        $this->migrationsPath = $migrationsPath ?? __DIR__ . '/../database/migrations';
        $this->ensureMigrationsTable();
    }
    
    /**
     * Create migrations tracking table if it doesn't exist
     */
    private function ensureMigrationsTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) UNIQUE NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    /**
     * Get all migration files
     */
    private function getMigrationFiles() {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }
        
        $files = glob($this->migrationsPath . '/*.php');
        sort($files);
        return $files;
    }
    
    /**
     * Get executed migrations from database
     */
    private function getExecutedMigrations() {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get next batch number
     */
    private function getNextBatch() {
        $stmt = $this->pdo->query("SELECT COALESCE(MAX(batch), 0) + 1 as next_batch FROM migrations");
        return $stmt->fetchColumn();
    }
    
    /**
     * Load migration class from file
     */
    private function loadMigration($file) {
        require_once $file;
        $className = basename($file, '.php');
        
        if (!class_exists($className)) {
            throw new Exception("Migration class '{$className}' not found in {$file}");
        }
        
        return new $className($this->pdo);
    }
    
    /**
     * Run pending migrations
     */
    public function migrate() {
        $files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        $batch = $this->getNextBatch();
        $ran = [];
        
        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            
            if (in_array($migrationName, $executed)) {
                continue;
            }
            
            try {
                $migration = $this->loadMigration($file);
                
                echo "Migrating: {$migration->getName()}... ";
                
                $this->pdo->beginTransaction();
                $migration->up();
                
                // Record migration
                $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
                $stmt->execute([$migrationName, $batch]);
                
                $this->pdo->commit();
                
                $ran[] = $migration->getName();
                echo "✓ DONE\n";
                
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                echo "✗ FAILED\n";
                throw new Exception("Migration '{$migrationName}' failed: " . $e->getMessage());
            }
        }
        
        return $ran;
    }
    
    /**
     * Rollback last batch of migrations
     */
    public function rollback() {
        $stmt = $this->pdo->query("SELECT MAX(batch) as last_batch FROM migrations");
        $lastBatch = $stmt->fetchColumn();
        
        if (!$lastBatch) {
            return ['message' => 'Nothing to rollback.'];
        }
        
        $stmt = $this->pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $rolledBack = [];
        
        foreach ($migrations as $migrationName) {
            $file = $this->migrationsPath . '/' . $migrationName . '.php';
            
            if (!file_exists($file)) {
                echo "Warning: Migration file not found: {$file}\n";
                continue;
            }
            
            try {
                $migration = $this->loadMigration($file);
                
                echo "Rolling back: {$migration->getName()}... ";
                
                $this->pdo->beginTransaction();
                $migration->down();
                
                // Remove migration record
                $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
                $stmt->execute([$migrationName]);
                
                $this->pdo->commit();
                
                $rolledBack[] = $migration->getName();
                echo "✓ DONE\n";
                
            } catch (Exception $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                echo "✗ FAILED\n";
                throw new Exception("Rollback of '{$migrationName}' failed: " . $e->getMessage());
            }
        }
        
        return $rolledBack;
    }
    
    /**
     * Get migration status
     */
    public function status() {
        $files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        $status = [];
        
        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            $migration = $this->loadMigration($file);
            
            $status[] = [
                'name' => $migration->getName(),
                'file' => $migrationName,
                'executed' => in_array($migrationName, $executed)
            ];
        }
        
        return $status;
    }
    
    /**
     * Reset all migrations (DANGER!)
     */
    public function reset() {
        // Get all migrations in reverse order
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY batch DESC, id DESC");
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($migrations as $migrationName) {
            $file = $this->migrationsPath . '/' . $migrationName . '.php';
            
            if (file_exists($file)) {
                try {
                    $migration = $this->loadMigration($file);
                    echo "Rolling back: {$migration->getName()}... ";
                    $migration->down();
                    echo "✓ DONE\n";
                } catch (Exception $e) {
                    echo "✗ FAILED: " . $e->getMessage() . "\n";
                }
            }
        }
        
        // Clear migrations table
        $this->pdo->exec("TRUNCATE TABLE migrations");
        
        return ['message' => 'All migrations rolled back.'];
    }
}

