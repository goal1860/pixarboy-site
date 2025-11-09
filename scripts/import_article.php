<?php
/**
 * Generalized Article Import Script
 * 
 * Imports markdown articles/reviews to the database (local or production)
 * 
 * Features:
 * - Extracts H1 heading (# Title) as article title
 * - Removes H1 from content body to prevent duplicate titles
 * - Generates URL-safe slug
 * - Auto-generates excerpt
 * - Supports both local and production databases
 * - Handles existing articles (update or create new)
 * 
 * Usage:
 *   php scripts/import_article.php <path-to-markdown-file> [--local]
 * 
 * Examples:
 *   # Import to production (default)
 *   php scripts/import_article.php tmp/my-article.md
 *   
 *   # Import to local database
 *   php scripts/import_article.php tmp/my-article.md --local
 *   php scripts/import_article.php tmp/my-article.md local
 */

// Parse command line arguments
$isLocal = false;
$filePath = null;

for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    
    if ($arg === '--local' || $arg === 'local' || $arg === '-l') {
        $isLocal = true;
    } elseif (empty($filePath) && !preg_match('/^-/', $arg)) {
        $filePath = $arg;
    }
}

// Validate file path
if (empty($filePath)) {
    echo "Usage: php scripts/import_article.php <path-to-markdown-file> [--local]\n\n";
    echo "Options:\n";
    echo "  <path-to-markdown-file>  Path to the markdown file to import\n";
    echo "  --local, -l, local       Import to local database (default: production)\n\n";
    echo "Examples:\n";
    echo "  php scripts/import_article.php tmp/my-article.md\n";
    echo "  php scripts/import_article.php tmp/my-article.md --local\n";
    exit(1);
}

// Database configuration
if ($isLocal) {
    // Local database credentials
    require_once __DIR__ . '/../config/database.php';
    
    function getDBConnection() {
        try {
            // Use localhost with port 3306 to force TCP connection (for Docker)
            $host = '127.0.0.1';
            $port = '3306';
            $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . "\nPlease ensure MySQL is running and accessible on localhost:3306.\n");
        }
    }
    
    $dbEnv = "LOCAL";
} else {
    // Production database credentials
    define('DB_HOST', 'srv448.hstgr.io');
    define('DB_NAME', 'u697935469_pixarboy');
    define('DB_USER', 'u697935469_pixarboy');
    define('DB_PASS', '2r*rRgiPgW');
    
    function getDBConnection() {
        try {
            $host = DB_HOST;
            if ($host !== 'localhost') {
                $ip = gethostbyname($host);
                if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $host = $ip;
                }
            }
            
            $dsn = "mysql:host=" . $host . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    $dbEnv = "PRODUCTION";
}

// Generate URL-safe slug
function generateSlug($title) {
    $slug = strtolower(trim($title));
    // Remove all non-alphanumeric characters except spaces and hyphens
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    // Replace multiple spaces/hyphens with single hyphen
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    // Trim hyphens from start and end
    $slug = trim($slug, '-');
    return $slug;
}

// Extract H1 title and remove it from content
function extractTitleAndContent($markdownContent) {
    $lines = explode("\n", $markdownContent);
    $title = '';
    $content = $markdownContent;
    $titleFound = false;
    
    foreach ($lines as $index => $line) {
        $trimmed = trim($line);
        
        if (preg_match('/^\s*#\s+(.+)$/', $line, $matches)) {
            $lineWithoutSpaces = ltrim($line);
            if (strlen($lineWithoutSpaces) > 1 && $lineWithoutSpaces[0] === '#' && $lineWithoutSpaces[1] !== '#') {
                $title = trim($matches[1]);
                unset($lines[$index]);
                
                $filteredLines = array_values($lines);
                while (isset($filteredLines[0]) && trim($filteredLines[0]) === '') {
                    array_shift($filteredLines);
                }
                
                $content = implode("\n", $filteredLines);
                $titleFound = true;
                break;
            }
        }
    }
    
    if (!$titleFound) {
        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            if (!empty($trimmed)) {
                $title = $trimmed;
                unset($lines[$index]);
                
                $filteredLines = array_values($lines);
                while (isset($filteredLines[0]) && trim($filteredLines[0]) === '') {
                    array_shift($filteredLines);
                }
                
                $content = implode("\n", $filteredLines);
                echo "⚠️  Warning: No H1 heading (# Title) found, using first line as title\n";
                break;
            }
        }
    }
    
    if (empty($title)) {
        $title = "Untitled Article";
        echo "⚠️  Warning: No title found, using default title\n";
    }
    
    return ['title' => $title, 'content' => trim($content)];
}

// Generate excerpt from markdown content
function generateExcerpt($content, $length = 200) {
    $excerpt = preg_replace('/^#+\s*/m', '', $content);
    $excerpt = preg_replace('/\*\*(.*?)\*\*/', '$1', $excerpt);
    $excerpt = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $excerpt);
    $excerpt = strip_tags($excerpt);
    $excerpt = trim($excerpt);
    
    $lines = explode("\n", $excerpt);
    $firstLine = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && strlen($line) > 20) {
            $firstLine = $line;
            break;
        }
    }
    
    if (strlen($firstLine) > $length) {
        $firstLine = substr($firstLine, 0, $length);
        $lastSpace = strrpos($firstLine, ' ');
        if ($lastSpace !== false) {
            $firstLine = substr($firstLine, 0, $lastSpace);
        }
        $firstLine .= '...';
    }
    
    return $firstLine ?: substr($excerpt, 0, $length);
}

// Main execution
echo "=====================================\n";
echo "Import Article to Database\n";
echo "Environment: $dbEnv\n";
echo "=====================================\n\n";

try {
    $pdo = getDBConnection();
    echo "✓ Connected to database successfully!\n\n";
    
    // Resolve file path
    if (!file_exists($filePath)) {
        // Try relative to project root
        $projectRoot = dirname(__DIR__);
        $fullPath = $projectRoot . '/' . ltrim($filePath, '/');
        if (file_exists($fullPath)) {
            $filePath = $fullPath;
        } else {
            die("✗ Error: File not found: {$argv[1]}\n   Tried: $filePath\n   Tried: $fullPath\n");
        }
    }
    
    // Read the markdown file
    $markdownContent = file_get_contents($filePath);
    if ($markdownContent === false) {
        die("✗ Error: Could not read file: $filePath\n");
    }
    
    echo "✓ Article file loaded: $filePath\n";
    
    // Extract title and content
    $data = extractTitleAndContent($markdownContent);
    $title = $data['title'];
    $content = $data['content'];
    
    echo "✓ Title extracted: $title\n";
    echo "✓ H1 removed from content body\n";
    
    // Generate slug
    $slug = generateSlug($title);
    echo "✓ Slug: $slug\n";
    
    // Check if article already exists
    $stmt = $pdo->prepare("SELECT id, title, product_id FROM content WHERE slug = ?");
    $stmt->execute([$slug]);
    $existingArticle = $stmt->fetch();
    
    // Generate excerpt
    $excerpt = generateExcerpt($content);
    echo "✓ Excerpt generated\n";
    
    // Get admin user ID
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
    $adminUser = $stmt->fetch();
    
    if (!$adminUser) {
        $stmt = $pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
        $adminUser = $stmt->fetch();
    }
    
    if (!$adminUser) {
        die("✗ Error: No users found in database. Please create an admin user first.\n");
    }
    
    $authorId = $adminUser['id'];
    echo "✓ Author ID: $authorId\n";
    
    // Insert or update article
    if ($existingArticle) {
        echo "\n⚠️  Article with slug '$slug' already exists (ID: {$existingArticle['id']})\n";
        echo "   Existing Title: {$existingArticle['title']}\n";
        echo "   New Title: $title\n\n";
        
        // Auto-update if titles match
        $titleSimilar = (strtolower(trim($existingArticle['title'])) === strtolower(trim($title)));
        
        if ($titleSimilar) {
            echo "✓ Titles match - auto-updating existing article...\n";
            $isUpdate = true;
            $contentId = $existingArticle['id'];
        } else {
            echo "Options:\n";
            echo "1. Update existing article (default)\n";
            echo "2. Create new with different slug\n";
            echo "3. Cancel\n";
            echo "\nPress Enter to update, type 'new' to create new, or 'cancel' to abort: ";
            
            $handle = fopen("php://stdin", "r");
            $input = trim(fgets($handle));
            fclose($handle);
            
            if (strtolower($input) === 'cancel') {
                echo "\n✗ Import cancelled.\n";
                exit(0);
            } elseif (strtolower($input) === 'new') {
                $slug = $slug . '-' . date('Y-m-d');
                echo "✓ Using new slug: $slug\n";
                $isUpdate = false;
            } else {
                echo "✓ Updating existing article...\n";
                $isUpdate = true;
                $contentId = $existingArticle['id'];
            }
        }
    } else {
        $isUpdate = false;
        echo "\nCreating new article...\n";
    }
    
    // Insert or update
    if ($isUpdate) {
        $stmt = $pdo->prepare("
            UPDATE content 
            SET title = ?, 
                content = ?, 
                excerpt = ?, 
                status = 'published',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $content, $excerpt, $contentId]);
        echo "\n✓ Article updated successfully!\n";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO content (title, slug, content, excerpt, status, author_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'published', ?, NOW(), NOW())
        ");
        $stmt->execute([$title, $slug, $content, $excerpt, $authorId]);
        $contentId = $pdo->lastInsertId();
        echo "\n✓ Article published successfully!\n";
    }
    
    echo "\n=====================================\n";
    echo "✓ SUCCESS!\n";
    echo "=====================================\n";
    echo "Article ID: $contentId\n";
    echo "Article URL: /post/$slug\n";
    echo "=====================================\n\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    if (isset($e->xdebug_message)) {
        echo $e->xdebug_message . "\n";
    }
    exit(1);
}

