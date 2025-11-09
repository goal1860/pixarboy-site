<?php
require_once __DIR__ . '/config/config.php';

$pdo = getDBConnection();
$slug = $_GET['slug'] ?? '';

// Get category
$stmt = $pdo->prepare("
    SELECT c.*, parent.name as parent_name
    FROM categories c
    LEFT JOIN categories parent ON c.parent_id = parent.id
    WHERE c.slug = ?
");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    redirect('/');
}

// Get subcategories if this is a parent category
$stmt = $pdo->prepare("
    SELECT * FROM categories 
    WHERE parent_id = ? 
    ORDER BY display_order, name
");
$stmt->execute([$category['id']]);
$subcategories = $stmt->fetchAll();

// Get products in this category (and subcategories)
$categoryIds = [$category['id']];
if (!empty($subcategories)) {
    foreach ($subcategories as $sub) {
        $categoryIds[] = $sub['id'];
    }
}

$placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
$stmt = $pdo->prepare("
    SELECT DISTINCT p.*
    FROM products p
    INNER JOIN product_categories pc ON p.id = pc.product_id
    WHERE pc.category_id IN ($placeholders) AND p.status = 'active'
    ORDER BY p.rating DESC, p.created_at DESC
");
$stmt->execute($categoryIds);
$products = $stmt->fetchAll();

// Get content/posts in this category
$stmt = $pdo->prepare("
    SELECT c.*, u.username as author
    FROM content c
    LEFT JOIN users u ON c.author_id = u.id
    INNER JOIN product_categories pc ON c.product_id = pc.product_id
    WHERE pc.category_id IN ($placeholders) AND c.status = 'published'
    ORDER BY c.created_at DESC
    LIMIT 10
");
$stmt->execute($categoryIds);
$posts = $stmt->fetchAll();

$pageTitle = $category['name'];
include __DIR__ . '/includes/header.php';
?>

<!-- Category Hero -->
<div class="page-hero">
    <div class="container">
        <div class="page-hero-content">
            <?php if ($category['parent_name']): ?>
                <div class="page-hero-breadcrumb">
                    <a href="/">Home</a>
                    <span class="page-hero-breadcrumb-separator">›</span>
                    <span class="page-hero-breadcrumb-text"><?php echo htmlspecialchars($category['parent_name']); ?></span>
                </div>
            <?php endif; ?>
            
            <h1><?php echo htmlspecialchars($category['name']); ?></h1>
            
            <?php if ($category['description']): ?>
                <p class="page-hero-description">
                    <?php echo htmlspecialchars($category['description']); ?>
                </p>
            <?php endif; ?>
            
            <div class="page-hero-stats">
                <span>📦 <?php echo count($products); ?> Products</span>
                <span>•</span>
                <span>📝 <?php echo count($posts); ?> Articles</span>
            </div>
        </div>
    </div>
</div>

<div class="container category-content">
    <!-- Subcategories -->
    <?php if (!empty($subcategories)): ?>
        <div class="category-section">
            <h2 class="category-section-title">Browse by Subcategory</h2>
            <div class="posts-grid">
                <?php foreach ($subcategories as $sub): ?>
                    <a href="/category.php?slug=<?php echo htmlspecialchars($sub['slug']); ?>" 
                       class="card subcategory-card">
                        <div class="subcategory-icon">
                            <?php
                            // Simple icon mapping
                            $icons = [
                                'audio' => '🎧', 'mobile-devices' => '📱', 'wearables' => '⌚', 'cameras' => '📷',
                                'laptops' => '💻', 'desktops' => '🖥️', 'monitors' => '🖥️', 'computer-accessories' => '⌨️',
                                'smart-speakers' => '🔊', 'home-security' => '🔒', 'smart-lighting' => '💡',
                                'gaming-consoles' => '🎮', 'pc-gaming' => '🖥️', 'gaming-controllers' => '🕹️', 'gaming-headsets' => '🎧'
                            ];
                            echo $icons[$sub['slug']] ?? '📦';
                            ?>
                        </div>
                        <h3><?php echo htmlspecialchars($sub['name']); ?></h3>
                        <?php if ($sub['description']): ?>
                            <p class="subcategory-description">
                                <?php echo htmlspecialchars($sub['description']); ?>
                            </p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Products -->
    <?php if (!empty($products)): ?>
        <div class="category-section">
            <h2 class="category-section-title">🛍️ Featured Products</h2>
            <div class="posts-grid">
                <?php foreach ($products as $product): ?>
                    <article class="post-card product-card">
                        <?php if ($product['image_url']): ?>
                            <a href="/product/<?php echo htmlspecialchars($product['slug']); ?>" class="post-image">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </a>
                        <?php else: ?>
                            <div class="post-image product-image-placeholder">
                                <?php echo strtoupper(substr($product['name'], 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-body">
                            <?php if ($product['rating'] > 0): ?>
                                <div class="product-rating">
                                    <span class="product-rating-star">⭐</span>
                                    <strong><?php echo number_format($product['rating'], 1); ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="post-title">
                                <a href="/product/<?php echo htmlspecialchars($product['slug']); ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            
                            <?php if ($product['description']): ?>
                                <p class="post-excerpt">
                                    <?php echo htmlspecialchars(substr(strip_tags($product['description']), 0, 100)) . '...'; ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="product-price-wrapper">
                                <?php if ($product['price']): ?>
                                    <div class="product-price">
                                        <?php echo htmlspecialchars($product['currency']); ?> <?php echo number_format($product['price'], 2); ?>
                                    </div>
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>
                                
                                <a href="/product/<?php echo htmlspecialchars($product['slug']); ?>" 
                                   class="btn btn-primary btn-sm">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>No products in this category yet</h3>
            <p>Check back soon for new products!</p>
            <a href="/" class="btn btn-gradient">Back to Home</a>
        </div>
    <?php endif; ?>
    
    <!-- Related Posts -->
    <?php if (!empty($posts)): ?>
        <div class="category-section">
            <h2 class="category-section-title">📝 Related Articles</h2>
            <div class="posts-grid">
                <?php foreach (array_slice($posts, 0, 6) as $post): ?>
                    <article class="post-card">
                        <div class="post-body">
                            <h3 class="post-title">
                                <a href="/post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            
                            <?php if ($post['excerpt']): ?>
                                <p class="post-excerpt">
                                    <?php echo htmlspecialchars($post['excerpt']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="post-meta">
                                <span>By <?php echo htmlspecialchars($post['author']); ?></span>
                                <span>•</span>
                                <span><?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

