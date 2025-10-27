<?php
/**
 * SEO Helper Functions
 * Generates meta tags, Open Graph, Twitter Cards, and structured data
 */

/**
 * Generate SEO meta tags
 * 
 * @param array $data SEO data (title, description, image, url, type, etc.)
 */
function generateSEOTags($data = []) {
    // Defaults
    $defaults = [
        'title' => SITE_NAME . ' - Product Reviews & Tech News',
        'description' => 'Honest product reviews, tech insights, and buying guides to help you make informed decisions.',
        'keywords' => 'product reviews, tech reviews, buying guides, gadgets, electronics',
        'image' => '/assets/images/og-default.jpg',
        'url' => getCurrentUrl(),
        'type' => 'website',
        'author' => SITE_NAME,
        'twitter_card' => 'summary_large_image',
        'twitter_site' => '@pixarboy',
    ];
    
    $seo = array_merge($defaults, $data);
    
    // Ensure full URLs for images
    if (!preg_match('/^https?:\/\//', $seo['image'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $seo['image'] = $protocol . '://' . $host . $seo['image'];
    }
    
    // Full URL
    if (!preg_match('/^https?:\/\//', $seo['url'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $seo['url'] = $protocol . '://' . $host . $seo['url'];
    }
    
    ?>
    <!-- Primary Meta Tags -->
    <meta name="title" content="<?php echo htmlspecialchars($seo['title']); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($seo['description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seo['keywords']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($seo['author']); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($seo['url']); ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?php echo htmlspecialchars($seo['type']); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($seo['url']); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seo['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo['description']); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($seo['image']); ?>">
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="<?php echo htmlspecialchars($seo['twitter_card']); ?>">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($seo['url']); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($seo['title']); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($seo['description']); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($seo['image']); ?>">
    <?php if (isset($seo['twitter_site'])): ?>
    <meta property="twitter:site" content="<?php echo htmlspecialchars($seo['twitter_site']); ?>">
    <?php endif; ?>
    <?php
}

/**
 * Generate Product Structured Data (JSON-LD)
 * 
 * @param array $product Product data
 * @param array $reviews Optional reviews data
 */
function generateProductStructuredData($product, $reviews = []) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host;
    
    $structuredData = [
        "@context" => "https://schema.org/",
        "@type" => "Product",
        "name" => $product['name'],
        "description" => strip_tags($product['description'] ?? ''),
        "image" => $product['image_url'] ? $baseUrl . $product['image_url'] : null,
        "url" => $baseUrl . '/product/' . $product['slug'],
        "sku" => $product['id'],
    ];
    
    // Add brand if available
    if (isset($product['brand'])) {
        $structuredData["brand"] = [
            "@type" => "Brand",
            "name" => $product['brand']
        ];
    }
    
    // Add offers (price)
    if (isset($product['price']) && $product['price']) {
        $structuredData["offers"] = [
            "@type" => "Offer",
            "url" => $baseUrl . '/product/' . $product['slug'],
            "priceCurrency" => $product['currency'] ?? 'USD',
            "price" => $product['price'],
            "availability" => $product['status'] === 'active' ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
            "priceValidUntil" => date('Y-m-d', strtotime('+1 year'))
        ];
        
        // Add affiliate link if available
        if (isset($product['affiliate_link']) && $product['affiliate_link']) {
            $structuredData["offers"]["url"] = $product['affiliate_link'];
        }
    }
    
    // Add aggregate rating
    if (isset($product['rating']) && $product['rating'] > 0) {
        $structuredData["aggregateRating"] = [
            "@type" => "AggregateRating",
            "ratingValue" => $product['rating'],
            "bestRating" => "5",
            "worstRating" => "1",
            "ratingCount" => count($reviews) > 0 ? count($reviews) : 1
        ];
    }
    
    // Add reviews if available
    if (!empty($reviews)) {
        $structuredData["review"] = [];
        foreach ($reviews as $review) {
            $structuredData["review"][] = [
                "@type" => "Review",
                "reviewRating" => [
                    "@type" => "Rating",
                    "ratingValue" => $review['rating'] ?? $product['rating'],
                    "bestRating" => "5"
                ],
                "author" => [
                    "@type" => "Person",
                    "name" => $review['author'] ?? SITE_NAME
                ],
                "datePublished" => $review['created_at'] ?? date('Y-m-d'),
                "reviewBody" => strip_tags($review['excerpt'] ?? '')
            ];
        }
    }
    
    // Remove null values
    $structuredData = array_filter($structuredData);
    
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n</script>\n";
}

/**
 * Generate Website/Organization Structured Data (JSON-LD)
 */
function generateWebsiteStructuredData() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host;
    
    $structuredData = [
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "name" => SITE_NAME,
        "url" => $baseUrl,
        "description" => "Honest product reviews, tech insights, and buying guides",
        "potentialAction" => [
            "@type" => "SearchAction",
            "target" => [
                "@type" => "EntryPoint",
                "urlTemplate" => $baseUrl . "/search?q={search_term_string}"
            ],
            "query-input" => "required name=search_term_string"
        ]
    ];
    
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n</script>\n";
}

/**
 * Generate Breadcrumb Structured Data (JSON-LD)
 * 
 * @param array $breadcrumbs Array of breadcrumb items [['name' => 'Home', 'url' => '/'], ...]
 */
function generateBreadcrumbStructuredData($breadcrumbs) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl = $protocol . '://' . $host;
    
    $itemListElement = [];
    foreach ($breadcrumbs as $index => $crumb) {
        $itemListElement[] = [
            "@type" => "ListItem",
            "position" => $index + 1,
            "name" => $crumb['name'],
            "item" => $baseUrl . $crumb['url']
        ];
    }
    
    $structuredData = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => $itemListElement
    ];
    
    echo '<script type="application/ld+json">' . "\n";
    echo json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n</script>\n";
}

/**
 * Get current full URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return $protocol . '://' . $host . $uri;
}

/**
 * Generate meta description from content
 * 
 * @param string $content Full content
 * @param int $length Max length
 * @return string
 */
function generateMetaDescription($content, $length = 160) {
    $text = strip_tags($content);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' ')) . '...';
    }
    
    return $text;
}

