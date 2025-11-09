<?php
// Application Configuration
session_start();

// Site Configuration
define('SITE_NAME', 'PixarBoy');
define('BASE_URL', ''); // Use empty string for relative paths (recommended) or set to your domain
define('ADMIN_EMAIL', 'admin@pixarboy.com');

// Environment Detection
// Check for .env file first, then fall back to server detection
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envVars = parse_ini_file($envFile);
    define('ENVIRONMENT', $envVars['ENVIRONMENT'] ?? 'development');
} else {
    // Auto-detect: production if on production domain, otherwise development
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('ENVIRONMENT', (strpos($host, 'pixarboy.com') !== false || strpos($host, 'www.pixarboy.com') !== false) ? 'production' : 'development');
}

// Asset versioning (increment this when assets change)
define('ASSET_VERSION', '1.0.1');

// Include database configuration
require_once __DIR__ . '/database.php';

// Helper Functions
function redirect($url) {
    // Use relative paths - no BASE_URL dependency
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function sanitize($data) {
    // Only strip tags and trim - don't encode quotes
    // Quotes should be stored as-is and only escaped when output to HTML
    return strip_tags(trim($data));
}

function showMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Get asset path (minified in production, readable in development)
 * @param string $type 'css' or 'js'
 * @param string $filename Filename without extension (e.g., 'main', 'style')
 * @return string Asset path with version query string
 */
function getAssetPath($type, $filename) {
    $isProduction = ENVIRONMENT === 'production';
    $basePath = '/assets/' . $type . '/';
    
    if ($isProduction) {
        // Use minified version in production
        $minifiedFile = $basePath . 'dist/' . $filename . '.min.' . $type;
        $sourceFile = __DIR__ . '/../assets/' . $type . '/' . $filename . '.' . $type;
        $distFile = __DIR__ . '/../assets/' . $type . '/dist/' . $filename . '.min.' . $type;
        
        // If minified file doesn't exist, fall back to source (with warning in dev)
        if (!file_exists($distFile)) {
            // Fall back to source file if minified doesn't exist
            return $basePath . $filename . '.' . $type . '?v=' . ASSET_VERSION;
        }
        
        return $minifiedFile . '?v=' . ASSET_VERSION;
    } else {
        // Use readable version in development
        return $basePath . $filename . '.' . $type . '?v=' . time(); // Use time() in dev for cache busting
    }
}

