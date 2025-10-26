<?php
// Application Configuration
session_start();

// Site Configuration
define('SITE_NAME', 'PixarBoy');
define('BASE_URL', ''); // Use empty string for relative paths (recommended) or set to your domain
define('ADMIN_EMAIL', 'admin@pixarboy.com');

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
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
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

