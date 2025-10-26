<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="/"><?php echo SITE_NAME; ?></a>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <div class="mobile-menu-toggle" id="mobileMenuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <!-- Navigation Menu -->
            <ul class="nav-menu" id="navMenu">
                <li><a href="<?php echo BASE_URL; ?>/" <?php echo (!isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] == BASE_URL . '/' || $_SERVER['REQUEST_URI'] == BASE_URL . '/index.php') ? 'class="active"' : ''; ?>>Home</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?php echo BASE_URL; ?>/admin/">Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/admin/content.php">Content</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="<?php echo BASE_URL; ?>/admin/users.php">Users</a></li>
                    <?php endif; ?>
                    <li class="nav-user-item">
                        <span class="nav-username">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="<?php echo BASE_URL; ?>/logout.php" class="nav-logout">Logout</a>
                    </li>
                <?php else: ?>
                    <li><a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-gradient btn-sm nav-login-btn">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <div class="main-content">
        <div class="container">
            <?php 
            // Display flash messages
            $flash = getFlashMessage();
            if ($flash): 
            ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
