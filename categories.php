<?php
require_once __DIR__ . '/config/config.php';

$pdo = getDBConnection();

// Get all parent categories
$stmt = $pdo->query("
    SELECT * FROM categories 
    WHERE parent_id IS NULL 
    ORDER BY display_order, name
");
$parentCategories = $stmt->fetchAll();

// For each parent category, get subcategories and products
$categoriesWithProducts = [];
foreach ($parentCategories as $parent) {
    // Get subcategories
    $stmt = $pdo->prepare("
        SELECT * FROM categories 
        WHERE parent_id = ? 
        ORDER BY display_order, name
    ");
    $stmt->execute([$parent['id']]);
    $subcategories = $stmt->fetchAll();
    
    // Get products for this category and its subcategories
    $categoryIds = [$parent['id']];
    foreach ($subcategories as $sub) {
        $categoryIds[] = $sub['id'];
    }
    
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.*, COUNT(DISTINCT pc.category_id) as category_count
        FROM products p
        INNER JOIN product_categories pc ON p.id = pc.product_id
        WHERE pc.category_id IN ($placeholders) AND p.status = 'active'
        GROUP BY p.id
        ORDER BY p.rating DESC, p.created_at DESC
        LIMIT 6
    ");
    $stmt->execute($categoryIds);
    $products = $stmt->fetchAll();
    
    // Get product count for this category
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.id) as total_products
        FROM products p
        INNER JOIN product_categories pc ON p.id = pc.product_id
        WHERE pc.category_id IN ($placeholders) AND p.status = 'active'
    ");
    $stmt->execute($categoryIds);
    $productCount = $stmt->fetchColumn();
    
    $categoriesWithProducts[] = [
        'category' => $parent,
        'subcategories' => $subcategories,
        'products' => $products,
        'product_count' => $productCount
    ];
}

// Icon mapping for categories
$categoryIcons = [
    'electronics' => '⚡',
    'computers' => '💻',
    'smart-home' => '🏠',
    'gaming' => '🎮',
    'audio' => '🎧',
    'mobile-devices' => '📱',
    'wearables' => '⌚',
    'cameras' => '📷',
    'laptops' => '💻',
    'desktops' => '🖥️',
    'monitors' => '🖥️',
    'computer-accessories' => '⌨️',
    'smart-speakers' => '🔊',
    'home-security' => '🔒',
    'smart-lighting' => '💡',
    'gaming-consoles' => '🎮',
    'pc-gaming' => '🖥️',
    'gaming-controllers' => '🕹️',
    'gaming-headsets' => '🎧'
];

$pageTitle = 'Categories';
$seoData = [
    'title' => 'Product Categories - ' . SITE_NAME,
    'description' => 'Browse products by category. Find the best products in Electronics, Computers, Smart Home, Gaming, and more.',
    'keywords' => 'product categories, electronics, computers, smart home, gaming, tech products',
    'type' => 'website',
    'url' => '/categories.php',
];

include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/seo.php';
generateBreadcrumbStructuredData([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Categories', 'url' => '/categories.php']
]);
?>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container">
        <div class="page-hero-content">
            <h1>🛍️ Browse by Category</h1>
            <p class="page-hero-description">
                Explore our curated selection of products organized by category. Find exactly what you're looking for.
            </p>
        </div>
    </div>
</div>

<div class="container" id="categoriesContent">
    <!-- Filter Bar -->
    <div class="filter-bar card" style="margin-bottom: 2rem; padding: 1.5rem; position: sticky; top: 80px; z-index: 100; background: white;">
        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
            <!-- Search Filter -->
            <div style="flex: 1; min-width: 250px;">
                <input type="text" 
                       id="productSearch" 
                       placeholder="🔍 Search products by name or brand..." 
                       class="form-control"
                       style="width: 100%;">
            </div>
            
            <!-- Category Filter -->
            <div style="flex: 1; min-width: 200px;">
                <select id="categoryFilter" class="form-control" style="width: 100%;">
                    <option value="">All Categories</option>
                    <?php foreach ($parentCategories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['slug']); ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Rating Filter -->
            <div style="min-width: 150px;">
                <select id="ratingFilter" class="form-control" style="width: 100%;">
                    <option value="">All Ratings</option>
                    <option value="4.5">4.5+ Stars</option>
                    <option value="4.0">4.0+ Stars</option>
                    <option value="3.5">3.5+ Stars</option>
                    <option value="3.0">3.0+ Stars</option>
                </select>
            </div>
            
            <!-- Clear Filters -->
            <button id="clearFilters" class="btn btn-secondary btn-sm" style="white-space: nowrap;">
                Clear Filters
            </button>
        </div>
        
        <!-- Active Filters Display -->
        <div id="activeFilters" style="margin-top: 1rem; display: none;">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <span style="font-size: 0.875rem; color: var(--text-light);">Active filters:</span>
                <span id="filterTags" style="display: flex; gap: 0.5rem; flex-wrap: wrap;"></span>
            </div>
        </div>
    </div>
    <?php if (empty($categoriesWithProducts)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>No categories available</h3>
            <p>Categories will appear here once they're added.</p>
            <a href="/" class="btn btn-gradient">Back to Home</a>
        </div>
    <?php else: ?>
        <?php foreach ($categoriesWithProducts as $catData): 
            $category = $catData['category'];
            $subcategories = $catData['subcategories'];
            $products = $catData['products'];
            $productCount = $catData['product_count'];
            $icon = $categoryIcons[$category['slug']] ?? '📦';
        ?>
            <div class="category-group" 
                 data-category-slug="<?php echo htmlspecialchars($category['slug']); ?>"
                 style="margin-bottom: 4rem;">
                <!-- Category Header -->
                <div class="category-group-header" style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <span style="font-size: 2.5rem;"><?php echo $icon; ?></span>
                        <div>
                            <h2 style="margin: 0; font-size: 2rem;">
                                <a href="/category.php?slug=<?php echo htmlspecialchars($category['slug']); ?>" 
                                   style="color: var(--dark-color); text-decoration: none;">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </h2>
                            <?php if ($category['description']): ?>
                                <p style="margin: 0.5rem 0 0 0; color: var(--text-light);">
                                    <?php echo htmlspecialchars($category['description']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <span class="badge badge-info" style="font-size: 0.9rem;">
                            📦 <?php echo $productCount; ?> Product<?php echo $productCount != 1 ? 's' : ''; ?>
                        </span>
                        <?php if (!empty($subcategories)): ?>
                            <span class="badge badge-secondary" style="font-size: 0.9rem;">
                                📁 <?php echo count($subcategories); ?> Subcategor<?php echo count($subcategories) != 1 ? 'ies' : 'y'; ?>
                            </span>
                        <?php endif; ?>
                        <a href="/category.php?slug=<?php echo htmlspecialchars($category['slug']); ?>" 
                           class="btn btn-primary btn-sm">
                            View All →
                        </a>
                    </div>
                </div>
                
                <!-- Subcategories -->
                <?php if (!empty($subcategories)): ?>
                    <div style="margin-bottom: 2rem;">
                        <h3 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--text-light); font-weight: 600;">
                            Subcategories
                        </h3>
                        <div class="posts-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                            <?php foreach ($subcategories as $sub): 
                                $subIcon = $categoryIcons[$sub['slug']] ?? '📦';
                            ?>
                                <a href="/category.php?slug=<?php echo htmlspecialchars($sub['slug']); ?>" 
                                   class="card subcategory-link-card" 
                                   style="padding: 1.5rem; text-align: center; text-decoration: none; transition: all 0.2s ease;">
                                    <div style="font-size: 2rem; margin-bottom: 0.5rem; transition: transform 0.2s ease;"><?php echo $subIcon; ?></div>
                                    <h4 style="margin: 0; font-size: 1rem; color: var(--dark-color);">
                                        <?php echo htmlspecialchars($sub['name']); ?>
                                    </h4>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Featured Products -->
                <?php if (!empty($products)): ?>
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--text-light); font-weight: 600;">
                            Featured Products
                        </h3>
                        <div class="posts-grid product-grid">
                            <?php foreach ($products as $product): 
                                // Get product categories for filtering
                                $stmt = $pdo->prepare("
                                    SELECT c.slug 
                                    FROM categories c
                                    INNER JOIN product_categories pc ON c.id = pc.category_id
                                    WHERE pc.product_id = ?
                                ");
                                $stmt->execute([$product['id']]);
                                $productCategories = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                $productCategorySlugs = implode(' ', array_map(function($slug) {
                                    return 'cat-' . htmlspecialchars($slug);
                                }, $productCategories));
                            ?>
                                <article class="post-card product-card product-item" 
                                         data-product-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>"
                                         data-product-brand="<?php echo htmlspecialchars(strtolower($product['brand'] ?? '')); ?>"
                                         data-product-rating="<?php echo $product['rating'] ?? 0; ?>"
                                         data-product-price="<?php echo $product['price'] ?? 0; ?>"
                                         data-category-slugs="<?php echo $productCategorySlugs; ?>"
                                         data-parent-category="<?php echo htmlspecialchars($category['slug']); ?>">
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
                                        <?php if (!empty($product['brand'])): ?>
                                            <div style="font-size: 0.75rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">
                                                <?php echo htmlspecialchars($product['brand']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
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
                        
                        <?php if ($productCount > count($products)): ?>
                            <div style="text-align: center; margin-top: 2rem;">
                                <a href="/category.php?slug=<?php echo htmlspecialchars($category['slug']); ?>" 
                                   class="btn btn-secondary">
                                    View All <?php echo $productCount; ?> Products →
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border: none; padding: 2rem;">
                        <div class="empty-state-icon">📦</div>
                        <h3>No products in this category yet</h3>
                        <p>Check back soon for new products!</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('productSearch');
    const categoryFilter = document.getElementById('categoryFilter');
    const ratingFilter = document.getElementById('ratingFilter');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const activeFiltersDiv = document.getElementById('activeFilters');
    const filterTagsDiv = document.getElementById('filterTags');
    const productItems = document.querySelectorAll('.product-item');
    const categoryGroups = document.querySelectorAll('.category-group');
    
    function updateActiveFilters() {
        const filters = [];
        if (searchInput.value.trim()) {
            filters.push({ type: 'search', value: searchInput.value.trim() });
        }
        if (categoryFilter.value) {
            const categoryName = categoryFilter.options[categoryFilter.selectedIndex].text;
            filters.push({ type: 'category', value: categoryName });
        }
        if (ratingFilter.value) {
            filters.push({ type: 'rating', value: ratingFilter.value + '+ Stars' });
        }
        
        if (filters.length > 0) {
            activeFiltersDiv.style.display = 'block';
            filterTagsDiv.innerHTML = filters.map(filter => {
                return `<span class="badge badge-primary" style="font-size: 0.875rem;">
                    ${filter.type === 'search' ? '🔍' : filter.type === 'category' ? '📁' : '⭐'} 
                    ${filter.value}
                    <button onclick="removeFilter('${filter.type}')" style="margin-left: 0.5rem; background: none; border: none; color: inherit; cursor: pointer; font-size: 1rem; line-height: 1;">×</button>
                </span>`;
            }).join('');
        } else {
            activeFiltersDiv.style.display = 'none';
        }
    }
    
    window.removeFilter = function(type) {
        if (type === 'search') {
            searchInput.value = '';
        } else if (type === 'category') {
            categoryFilter.value = '';
        } else if (type === 'rating') {
            ratingFilter.value = '';
        }
        filterProducts();
    };
    
    function filterProducts() {
        const searchTerm = searchInput.value.trim().toLowerCase();
        const selectedCategory = categoryFilter.value;
        const minRating = parseFloat(ratingFilter.value) || 0;
        
        updateActiveFilters();
        
        let visibleCount = 0;
        
        categoryGroups.forEach(group => {
            const groupCategorySlug = group.getAttribute('data-category-slug');
            const productsInGroup = group.querySelectorAll('.product-item');
            let groupVisibleCount = 0;
            
            productsInGroup.forEach(product => {
                const productName = product.getAttribute('data-product-name') || '';
                const productBrand = product.getAttribute('data-product-brand') || '';
                const productRating = parseFloat(product.getAttribute('data-product-rating')) || 0;
                const parentCategory = product.getAttribute('data-parent-category') || '';
                
                // Search filter
                const matchesSearch = !searchTerm || 
                    productName.includes(searchTerm) || 
                    productBrand.includes(searchTerm);
                
                // Category filter
                const matchesCategory = !selectedCategory || 
                    parentCategory === selectedCategory ||
                    product.getAttribute('data-category-slugs').includes('cat-' + selectedCategory);
                
                // Rating filter
                const matchesRating = productRating >= minRating;
                
                if (matchesSearch && matchesCategory && matchesRating) {
                    product.style.display = '';
                    groupVisibleCount++;
                    visibleCount++;
                } else {
                    product.style.display = 'none';
                }
            });
            
            // Show/hide category group based on visible products
            const productGrid = group.querySelector('.product-grid');
            if (productGrid && groupVisibleCount === 0) {
                group.style.display = 'none';
            } else {
                group.style.display = '';
            }
        });
        
        // Show "no results" message if needed
        let noResultsMsg = document.getElementById('noResultsMessage');
        if (visibleCount === 0 && (searchTerm || selectedCategory || minRating > 0)) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.id = 'noResultsMessage';
                noResultsMsg.className = 'empty-state';
                noResultsMsg.innerHTML = `
                    <div class="empty-state-icon">🔍</div>
                    <h3>No products found</h3>
                    <p>Try adjusting your filters to see more results.</p>
                    <button onclick="document.getElementById('clearFilters').click()" class="btn btn-gradient">
                        Clear All Filters
                    </button>
                `;
                // Find the main content container (the one with categoriesContent ID)
                const mainContainer = document.getElementById('categoriesContent');
                if (mainContainer) {
                    const firstGroup = mainContainer.querySelector('.category-group');
                    if (firstGroup) {
                        mainContainer.insertBefore(noResultsMsg, firstGroup);
                    } else {
                        // Insert after filter bar if no category groups
                        const filterBar = mainContainer.querySelector('.filter-bar');
                        if (filterBar && filterBar.nextSibling) {
                            mainContainer.insertBefore(noResultsMsg, filterBar.nextSibling);
                        } else {
                            mainContainer.appendChild(noResultsMsg);
                        }
                    }
                }
            }
            noResultsMsg.style.display = 'block';
        } else if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    }
    
    // Event listeners
    searchInput.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);
    ratingFilter.addEventListener('change', filterProducts);
    
    clearFiltersBtn.addEventListener('click', function() {
        searchInput.value = '';
        categoryFilter.value = '';
        ratingFilter.value = '';
        filterProducts();
        searchInput.focus();
    });
    
    // Initial filter check (in case of URL parameters)
    filterProducts();
});
</script>

