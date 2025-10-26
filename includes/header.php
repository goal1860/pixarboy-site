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
                <li><a href="/">Home</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <li><a href="/admin/">Dashboard</a></li>
                    <li><a href="/admin/content.php">Content</a></li>
                    <li><a href="/admin/products.php">Products</a></li>
                    <li><a href="/admin/categories.php">Categories</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="/admin/users.php">Users</a></li>
                        <li><a href="/admin/migrations.php">Migrations</a></li>
                    <?php endif; ?>
                    <li class="nav-user-item">
                        <span class="nav-username">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="/logout.php" class="nav-logout">Logout</a>
                    </li>
                <?php else: ?>
                    <li><a href="/login.php" class="btn btn-gradient btn-sm nav-login-btn">Login</a></li>
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
