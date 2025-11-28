<?php
/**
 * Database Migration CLI Tool
 * 
 * Usage:
 *   php migrate.php                 - Show migration status
 *   php migrate.php migrate         - Run pending migrations
 *   php migrate.php rollback        - Rollback last batch
 *   php migrate.php reset           - Reset all migrations (DANGER!)
 *   php migrate.php status          - Show migration status
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/MigrationRunner.php';

// Check if running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Colors for CLI output
class CliColors {
    const RESET = "\033[0m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const CYAN = "\033[36m";
    const BOLD = "\033[1m";
}

function printHeader($text) {
    echo "\n" . CliColors::BOLD . CliColors::CYAN . $text . CliColors::RESET . "\n";
    echo str_repeat("=", strlen($text)) . "\n\n";
}

function printSuccess($text) {
    echo CliColors::GREEN . "✓ " . $text . CliColors::RESET . "\n";
}

function printError($text) {
    echo CliColors::RED . "✗ " . $text . CliColors::RESET . "\n";
}

function printWarning($text) {
    echo CliColors::YELLOW . "⚠ " . $text . CliColors::RESET . "\n";
}

function printInfo($text) {
    echo CliColors::BLUE . "ℹ " . $text . CliColors::RESET . "\n";
}

try {
    $pdo = getDBConnection();
    $runner = new MigrationRunner($pdo);
    
    $command = $argv[1] ?? 'status';
    
    switch ($command) {
        case 'migrate':
        case 'up':
            printHeader("Running Migrations");
            $ran = $runner->migrate();
            
            if (empty($ran)) {
                printInfo("No pending migrations to run.");
            } else {
                printSuccess("Successfully ran " . count($ran) . " migration(s):");
                foreach ($ran as $name) {
                    echo "  - $name\n";
                }
            }
            break;
            
        case 'rollback':
        case 'down':
            printHeader("Rolling Back Migrations");
            $result = $runner->rollback();
            
            if (isset($result['message'])) {
                printInfo($result['message']);
            } else {
                printSuccess("Successfully rolled back " . count($result) . " migration(s):");
                foreach ($result as $name) {
                    echo "  - $name\n";
                }
            }
            break;
            
        case 'reset':
            printHeader("Resetting Database");
            printWarning("WARNING: This will rollback ALL migrations!");
            echo "Are you sure? Type 'yes' to confirm: ";
            $handle = fopen("php://stdin", "r");
            $confirmation = trim(fgets($handle));
            
            if ($confirmation === 'yes') {
                $result = $runner->reset();
                printSuccess($result['message'] ?? 'Database reset complete.');
            } else {
                printInfo("Reset cancelled.");
            }
            break;
            
        case 'status':
        case 'list':
        default:
            printHeader("Migration Status");
            $status = $runner->status();
            
            if (empty($status)) {
                printInfo("No migrations found.");
                printInfo("Create migration files in: database/migrations/");
            } else {
                echo sprintf(
                    "%-10s %-50s %s\n",
                    "STATUS",
                    "MIGRATION",
                    "FILE"
                );
                echo str_repeat("-", 100) . "\n";
                
                $executedCount = 0;
                $pendingCount = 0;
                
                foreach ($status as $migration) {
                    $status = $migration['executed'] ? 
                        CliColors::GREEN . "✓ Done" . CliColors::RESET : 
                        CliColors::YELLOW . "⏳ Pending" . CliColors::RESET;
                    
                    echo sprintf(
                        "%-20s %-50s %s\n",
                        $status,
                        substr($migration['name'], 0, 50),
                        $migration['file'] . ".php"
                    );
                    
                    if ($migration['executed']) {
                        $executedCount++;
                    } else {
                        $pendingCount++;
                    }
                }
                
                echo "\n";
                $totalCount = is_array($status) ? count($status) : 0;
                printInfo("Total: " . $totalCount . " | Executed: " . $executedCount . " | Pending: " . $pendingCount);
                
                if ($pendingCount > 0) {
                    echo "\n";
                    printWarning("You have $pendingCount pending migration(s). Run: php migrate.php migrate");
                }
            }
            break;
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "\n";
    printError("Error: " . $e->getMessage());
    echo "\n";
    exit(1);
}

