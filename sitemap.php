<?php
/**
 * Dynamic XML Sitemap Generator
 * Generates sitemap for search engines
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: application/xml; charset=utf-8');

$pdo = getDBConnection();
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'pixarboy.com';
$baseUrl = $protocol . '://' . $host;

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Homepage
echo "  <url>\n";
echo "    <loc>{$baseUrl}/</loc>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>1.0</priority>\n";
echo "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
echo "  </url>\n";

// Published content/posts
$stmt = $pdo->query("SELECT slug, updated_at FROM content WHERE status = 'published' ORDER BY updated_at DESC");
$posts = $stmt->fetchAll();

foreach ($posts as $post) {
    $lastmod = date('Y-m-d', strtotime($post['updated_at']));
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/post/" . htmlspecialchars($post['slug']) . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "  </url>\n";
}

// Active products
$stmt = $pdo->query("SELECT slug, updated_at FROM products WHERE status = 'active' ORDER BY updated_at DESC");
$products = $stmt->fetchAll();

foreach ($products as $product) {
    $lastmod = date('Y-m-d', strtotime($product['updated_at']));
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/product/" . htmlspecialchars($product['slug']) . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.9</priority>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "  </url>\n";
}

// Categories
$stmt = $pdo->query("SELECT slug, updated_at FROM categories ORDER BY display_order, name");
$categories = $stmt->fetchAll();

foreach ($categories as $category) {
    $lastmod = date('Y-m-d', strtotime($category['updated_at']));
    echo "  <url>\n";
    echo "    <loc>{$baseUrl}/category/" . htmlspecialchars($category['slug']) . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "  </url>\n";
}

echo '</urlset>';

