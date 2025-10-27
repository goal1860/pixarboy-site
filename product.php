<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Parsedown.php';

$pdo = getDBConnection();
$slug = $_GET['slug'] ?? '';

// Get product
$stmt = $pdo->prepare("
    SELECT * FROM products 
    WHERE slug = ? AND status = 'active'
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    redirect('/');
}

// Get product categories
$stmt = $pdo->prepare("
    SELECT c.*, parent.name as parent_name
    FROM categories c
    INNER JOIN product_categories pc ON c.id = pc.category_id
    LEFT JOIN categories parent ON c.parent_id = parent.id
    WHERE pc.product_id = ?
    ORDER BY parent.name, c.name
");
$stmt->execute([$product['id']]);
$categories = $stmt->fetchAll();

// Get related content/reviews for this product
$stmt = $pdo->prepare("
    SELECT c.*, u.username as author
    FROM content c
    LEFT JOIN users u ON c.author_id = u.id
    WHERE c.product_id = ? AND c.status = 'published'
    ORDER BY c.created_at DESC
    LIMIT 5
");
$stmt->execute([$product['id']]);
$relatedContent = $stmt->fetchAll();

// Get related products (same categories)
$stmt = $pdo->prepare("
    SELECT DISTINCT p.*, AVG(pc2.category_id) as relevance
    FROM products p
    INNER JOIN product_categories pc1 ON p.id = pc1.product_id
    INNER JOIN product_categories pc2 ON pc1.category_id = pc2.category_id
    WHERE pc2.product_id = ? AND p.id != ? AND p.status = 'active'
    GROUP BY p.id
    ORDER BY relevance DESC, p.rating DESC
    LIMIT 4
");
$stmt->execute([$product['id'], $product['id']]);
$relatedProducts = $stmt->fetchAll();

// Load SEO helpers
require_once __DIR__ . '/includes/seo.php';

// SEO Configuration for Product
$pageTitle = $product['name'];
$metaDescription = $product['description'] ? 
    generateMetaDescription($product['description'], 160) : 
    'Read our detailed review of ' . $product['name'] . '. Find specs, pricing, pros & cons, and where to buy.';

$categoryNames = array_map(function($cat) {
    return $cat['name'];
}, $categories);

$seoData = [
    'title' => $product['name'] . ' - Review & Buying Guide | ' . SITE_NAME,
    'description' => $metaDescription,
    'keywords' => $product['name'] . ', ' . implode(', ', $categoryNames) . ', product review, buying guide',
    'type' => 'product',
    'url' => '/product.php?slug=' . $product['slug'],
    'image' => $product['image_url'] ?: '/assets/images/og-default.jpg',
];

include __DIR__ . '/includes/header.php';

// Generate structured data for product
generateProductStructuredData($product, $relatedContent);

// Generate breadcrumb structured data
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/']
];
if (!empty($categories)) {
    $mainCategory = $categories[0];
    if ($mainCategory['parent_name']) {
        $breadcrumbs[] = ['name' => $mainCategory['parent_name'], 'url' => '/category.php?slug=' . $mainCategory['slug']];
    }
    $breadcrumbs[] = ['name' => $mainCategory['name'], 'url' => '/category.php?slug=' . $mainCategory['slug']];
}
$breadcrumbs[] = ['name' => $product['name'], 'url' => '/product.php?slug=' . $product['slug']];
generateBreadcrumbStructuredData($breadcrumbs);

// Initialize Parsedown for markdown
$Parsedown = new Parsedown();
?>

<article class="single-product">
    <!-- Product Hero -->
    <div class="product-hero" style="background: var(--gradient-primary); padding: 3rem 0; margin-bottom: 2rem; border-radius: 12px;">
        <div class="container">
            <div style="max-width: 800px; margin: 0 auto; text-align: center; color: white;">
                <?php if (!empty($categories)): ?>
                    <div style="margin-bottom: 1rem;">
                        <?php foreach ($categories as $cat): ?>
                            <a href="/category/<?php echo urlencode($cat['slug']); ?>" 
                               class="badge" 
                               style="background: rgba(255,255,255,0.2); color: white; text-decoration: none; margin-right: 0.5rem;">
                                <?php echo htmlspecialchars($cat['parent_name'] ? $cat['parent_name'] . ' › ' . $cat['name'] : $cat['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: white;"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <?php if ($product['rating'] > 0): ?>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; font-size: 1.25rem; margin-bottom: 1.5rem;">
                        <span style="color: #fbbf24;">⭐</span>
                        <strong><?php echo number_format($product['rating'], 1); ?></strong>
                        <span style="opacity: 0.8;">/ 5.0</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($product['price']): ?>
                    <div style="font-size: 2rem; font-weight: bold; margin-bottom: 1.5rem; color: white;">
                        <?php echo htmlspecialchars($product['currency']); ?> <?php echo number_format($product['price'], 2); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Price information displayed above -->
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="post-layout">
            <!-- Main Content -->
            <div class="post-content">
                <?php if ($product['image_url']): ?>
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             style="max-width: 100%; height: auto; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                    </div>
                <?php endif; ?>
                
                <div class="card" style="margin-bottom: 2rem;">
                    <h2 style="margin-bottom: 1rem;">📝 Product Description</h2>
                    <?php if ($product['description']): ?>
                        <div class="content-body">
                            <?php echo $Parsedown->text($product['description']); ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--text-light);">No description available.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Related Content/Reviews -->
                <?php if (!empty($relatedContent)): ?>
                    <div class="card" style="margin-bottom: 2rem;">
                        <h2 style="margin-bottom: 1.5rem;">📚 Related Articles & Reviews</h2>
                        <div class="related-posts">
                            <?php foreach ($relatedContent as $content): ?>
                                <a href="/post/<?php echo urlencode($content['slug']); ?>" class="related-post-item">
                                    <h3><?php echo htmlspecialchars($content['title']); ?></h3>
                                    <?php if ($content['excerpt']): ?>
                                        <p><?php echo htmlspecialchars(substr($content['excerpt'], 0, 100)) . '...'; ?></p>
                                    <?php endif; ?>
                                    <div class="post-meta">
                                        <span>By <?php echo htmlspecialchars($content['author']); ?></span>
                                        <span>•</span>
                                        <span><?php echo date('M j, Y', strtotime($content['created_at'])); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <aside class="post-sidebar">
                <!-- Product Info Card -->
                <div class="card" style="position: sticky; top: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Product Details</h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php if ($product['rating'] > 0): ?>
                            <div>
                                <div style="color: var(--text-light); font-size: 0.875rem; margin-bottom: 0.25rem;">Rating</div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: #fbbf24;">⭐</span>
                                    <strong style="font-size: 1.25rem;"><?php echo number_format($product['rating'], 1); ?></strong>
                                    <span style="color: var(--text-light);">/ 5.0</span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($product['price']): ?>
                            <div>
                                <div style="color: var(--text-light); font-size: 0.875rem; margin-bottom: 0.25rem;">Price</div>
                                <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                    <?php echo htmlspecialchars($product['currency']); ?> <?php echo number_format($product['price'], 2); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($categories)): ?>
                            <div>
                                <div style="color: var(--text-light); font-size: 0.875rem; margin-bottom: 0.5rem;">Categories</div>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    <?php foreach ($categories as $cat): ?>
                                        <a href="/category/<?php echo urlencode($cat['slug']); ?>" class="badge badge-primary" style="text-decoration: none;">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Product information displayed above -->
                    </div>
                </div>
            </aside>
        </div>
        
        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
            <div style="margin-top: 3rem;">
                <h2 style="text-align: center; margin-bottom: 2rem;">Similar Products You Might Like</h2>
                <div class="posts-grid">
                    <?php foreach ($relatedProducts as $related): ?>
                        <article class="post-card product-card">
                            <?php if ($related['image_url']): ?>
                                <a href="/product/<?php echo urlencode($related['slug']); ?>" class="post-image">
                                    <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>">
                                </a>
                            <?php endif; ?>
                            <div class="post-body">
                                <?php if ($related['rating'] > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
                                        <span style="color: #fbbf24;">⭐</span>
                                        <strong><?php echo number_format($related['rating'], 1); ?></strong>
                                    </div>
                                <?php endif; ?>
                                
                                <h3 class="post-title">
                                    <a href="/product/<?php echo urlencode($related['slug']); ?>">
                                        <?php echo htmlspecialchars($related['name']); ?>
                                    </a>
                                </h3>
                                
                                <?php if ($related['description']): ?>
                                    <p class="post-excerpt">
                                        <?php echo htmlspecialchars(substr(strip_tags($related['description']), 0, 100)) . '...'; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                                    <?php if ($related['price']): ?>
                                        <div style="font-size: 1.25rem; font-weight: bold; color: var(--primary-color);">
                                            <?php echo htmlspecialchars($related['currency']); ?> <?php echo number_format($related['price'], 2); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <a href="/product/<?php echo urlencode($related['slug']); ?>" 
                                       class="btn btn-primary btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</article>

<?php include __DIR__ . '/includes/footer.php'; ?>

