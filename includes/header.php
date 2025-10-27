<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php 
    if (isset($seoData['title'])) {
        echo htmlspecialchars($seoData['title']);
    } else {
        echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '';
        echo htmlspecialchars(SITE_NAME);
    }
    ?></title>
    
    <?php 
    // Include SEO helpers and generate meta tags
    require_once __DIR__ . '/seo.php';
    if (isset($seoData) && is_array($seoData)) {
        generateSEOTags($seoData);
    } else {
        generateSEOTags();
    }
    ?>
    
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="/">
                    <img src="/assets/images/logo.png" alt="<?php echo SITE_NAME; ?>" class="site-logo">
                </a>
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
