<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-60V9WWRCXX"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-60V9WWRCXX');
    </script>
    
    <title><?php 
    if (isset($seoData['title'])) {
        echo htmlspecialchars($seoData['title']);
    } else {
        echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '';
        echo htmlspecialchars(SITE_NAME);
    }
    ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="96x96" href="/assets/images/favicon-96x96.png">
    <link rel="shortcut icon" href="/assets/images/favicon-96x96.png">
    <link rel="apple-touch-icon" sizes="96x96" href="/assets/images/favicon-96x96.png">
    
    <?php 
    // Include SEO helpers and generate meta tags
    require_once __DIR__ . '/seo.php';
    if (isset($seoData) && is_array($seoData)) {
        generateSEOTags($seoData);
    } else {
        generateSEOTags();
    }
    ?>
    
    <link rel="stylesheet" href="<?php echo getAssetPath('css', 'style'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if (isset($preloadImageUrl) && !empty($preloadImageUrl)): ?>
        <link rel="preload" as="image" href="<?php echo htmlspecialchars($preloadImageUrl); ?>" fetchpriority="high">
    <?php endif; ?>
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
                <li><a href="/categories.php">Categories</a></li>
                
                <?php if (isLoggedIn()): ?>
                    <li class="nav-dropdown">
                        <a href="#" class="nav-dropdown-toggle">
                            Admin
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; margin-left: 0.25rem; vertical-align: middle; transition: transform 0.3s ease;">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        <ul class="nav-dropdown-menu">
                            <li><a href="/admin/">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 0.5rem;">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                Dashboard
                            </a></li>
                            <li><a href="/admin/content.php">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 0.5rem;">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                </svg>
                                Content
                            </a></li>
                            <li><a href="/admin/products.php">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 0.5rem;">
                                    <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                                </svg>
                                Products
                            </a></li>
                            <li><a href="/admin/categories.php">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 0.5rem;">
                                    <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                                </svg>
                                Categories
                            </a></li>
                            <?php if (isAdmin()): ?>
                                <li><a href="/admin/users.php">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 0.5rem;">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                    </svg>
                                    Users
                                </a></li>
                                <li><a href="/admin/migrations.php">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="margin-right: 0.5rem;">
                                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                    </svg>
                                    Migrations
                                </a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
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
