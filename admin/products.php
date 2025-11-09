<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'new' || $action === 'edit') {
        $name = sanitize($_POST['name']);
        $slug = sanitize(strtolower(str_replace(' ', '-', $_POST['name'])));
        $description = $_POST['description']; // Allow HTML
        $price = $_POST['price'] ? floatval($_POST['price']) : null;
        $currency = sanitize($_POST['currency'] ?? 'USD');
        $affiliateLink = sanitize($_POST['affiliate_link']);
        $imageUrl = sanitize($_POST['image_url']);
        $rating = $_POST['rating'] ? floatval($_POST['rating']) : 0;
        $status = $_POST['status'];
        $categoryIds = $_POST['categories'] ?? [];
        
        try {
            $pdo->beginTransaction();
            
            if ($action === 'new') {
                $stmt = $pdo->prepare("INSERT INTO products (name, slug, description, price, currency, affiliate_link, image_url, rating, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $price, $currency, $affiliateLink, $imageUrl, $rating, $status]);
                $productId = $pdo->lastInsertId();
                $message = 'Product created successfully!';
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name=?, slug=?, description=?, price=?, currency=?, affiliate_link=?, image_url=?, rating=?, status=? WHERE id=?");
                $stmt->execute([$name, $slug, $description, $price, $currency, $affiliateLink, $imageUrl, $rating, $status, $productId]);
                
                // Delete existing categories
                $stmt = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?");
                $stmt->execute([$productId]);
                $message = 'Product updated successfully!';
            }
            
            // Insert product categories - ensure category IDs are integers
            if (!empty($categoryIds)) {
                // Debug: Log what categories are being submitted
                $submittedCategories = [];
                foreach ($categoryIds as $catId) {
                    $submittedCategories[] = (int)$catId;
                }
                error_log("Product $productId: Submitting categories: " . implode(', ', $submittedCategories));
                
                $stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
                foreach ($categoryIds as $categoryId) {
                    // Cast to integer to ensure proper type
                    $categoryId = (int)$categoryId;
                    // Validate category exists and get its info for debugging
                    $checkStmt = $pdo->prepare("SELECT id, name, parent_id FROM categories WHERE id = ?");
                    $checkStmt->execute([$categoryId]);
                    $categoryInfo = $checkStmt->fetch();
                    if ($categoryInfo) {
                        $stmt->execute([$productId, $categoryId]);
                        error_log("Product $productId: Assigned category ID $categoryId ({$categoryInfo['name']}, parent: " . ($categoryInfo['parent_id'] ?: 'none') . ")");
                    } else {
                        // Log invalid category ID (shouldn't happen, but helps debug)
                        error_log("Product $productId: Invalid category ID attempted: $categoryId");
                    }
                }
            }
            
            $pdo->commit();
            showMessage($message, 'success');
            redirect('/admin/products.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($action === 'delete' && $productId) {
    try {
        $pdo->beginTransaction();
        
        // Delete product categories first
        $stmt = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        // Delete product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        
        $pdo->commit();
        showMessage('Product deleted successfully!', 'success');
    } catch (PDOException $e) {
        $pdo->rollBack();
        showMessage('Error deleting product: ' . $e->getMessage(), 'error');
    }
    redirect('/admin/products.php');
}

// Get product for editing
if ($action === 'edit' && $productId) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $item = $stmt->fetch();
    if (!$item) {
        redirect('/admin/products.php');
    }
    
    // Get product categories - ensure they're integers for proper comparison
    $stmt = $pdo->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
    $stmt->execute([$productId]);
    $productCategories = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

// Get all products with category count
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT p.*, COUNT(DISTINCT pc.category_id) as category_count
        FROM products p 
        LEFT JOIN product_categories pc ON p.id = pc.product_id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $productList = $stmt->fetchAll();
}

// Get all categories for the form (hierarchical)
// CRITICAL: Order must ensure children come immediately after their parent
// The form rendering logic expects: Parent, then its children, then next parent, etc.
$stmt = $pdo->query("
    SELECT c.*, parent.name as parent_name, parent.id as parent_category_id
    FROM categories c
    LEFT JOIN categories parent ON c.parent_id = parent.id
    ORDER BY 
        COALESCE(c.parent_id, c.id),
        CASE WHEN c.parent_id IS NULL THEN 0 ELSE 1 END,
        c.display_order,
        c.name
");
$allCategories = $stmt->fetchAll();

// Reorganize categories to ensure proper parent-child grouping
// This ensures children appear immediately after their parent, even if SQL ordering is inconsistent
$organizedCategories = [];
$parentCategories = [];
$childCategories = [];

// Separate parents and children
foreach ($allCategories as $cat) {
    if ($cat['parent_id'] === null) {
        $parentCategories[] = $cat;
    } else {
        $childCategories[] = $cat;
    }
}

// Sort parent categories by ID to ensure consistent ordering
usort($parentCategories, function($a, $b) {
    return (int)$a['id'] - (int)$b['id'];
});

// Build organized list: parent, then its children, then next parent, etc.
foreach ($parentCategories as $parent) {
    $organizedCategories[] = $parent;
    // Add all children of this parent (already sorted by SQL query)
    foreach ($childCategories as $child) {
        if ((int)$child['parent_id'] === (int)$parent['id']) {
            $organizedCategories[] = $child;
        }
    }
}

$allCategories = $organizedCategories;

$pageTitle = ucfirst($action) . ' Products';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="margin-bottom: 0.25rem;">🛍️ Product Management</h2>
                <p style="color: var(--text-light); margin: 0;">Manage affiliate products and reviews</p>
            </div>
            <a href="?action=new" class="btn btn-gradient">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add New Product
            </a>
        </div>
        
        <?php if (empty($productList)): ?>
            <div class="empty-state" style="border: none;">
                <div class="empty-state-icon">📦</div>
                <h3>No products yet</h3>
                <p>Click "Add New Product" to add your first affiliate product!</p>
                <a href="?action=new" class="btn btn-gradient">Add Your First Product</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Rating</th>
                            <th>Categories</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productList as $p): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <?php if ($p['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($p['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($p['name']); ?>"
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color);">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--gradient-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                            <?php echo strtoupper(substr($p['name'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                        <br>
                                        <small style="color: var(--text-light);">/<?php echo htmlspecialchars($p['slug']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($p['price']): ?>
                                    <strong><?php echo htmlspecialchars($p['currency']); ?> <?php echo number_format($p['price'], 2); ?></strong>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['rating'] > 0): ?>
                                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                                        <span style="color: #fbbf24;">⭐</span>
                                        <strong><?php echo number_format($p['rating'], 1); ?></strong>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">Not rated</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['category_count'] > 0): ?>
                                    <span class="badge badge-info"><?php echo $p['category_count']; ?> categories</span>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $p['status'] === 'active' ? 'success' : 
                                        ($p['status'] === 'inactive' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $p['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($p['created_at'])); ?></td>
                            <td class="table-actions">
                                <?php if ($p['affiliate_link']): ?>
                                    <a href="<?php echo htmlspecialchars($p['affiliate_link']); ?>" class="btn btn-secondary btn-sm" target="_blank" title="View Affiliate Link">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                                            <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/>
                                            <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <a href="?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                    </svg>
                                </a>
                                <a href="?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" 
                                   data-confirm="Are you sure you want to delete '<?php echo htmlspecialchars($p['name']); ?>'?" title="Delete">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); color: var(--text-light); text-align: center;">
                <p>Total: <strong><?php echo count($productList); ?></strong> product(s)</p>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="card">
        <div style="margin-bottom: 2rem;">
            <h2><?php echo $action === 'new' ? '✨ Add New Product' : '✏️ Edit Product'; ?></h2>
            <p style="color: var(--text-light); margin-top: 0.5rem;">
                <?php echo $action === 'new' ? 'Add an affiliate product to review' : 'Update product information'; ?>
            </p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" onsubmit="return validateCategorySelection(this);">
            <div class="form-group">
                <label for="name">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                    </svg>
                    Product Name *
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-control" 
                    placeholder="e.g. Apple AirPods Pro (2nd Generation)"
                    value="<?php echo isset($item) ? htmlspecialchars($item['name']) : ''; ?>" 
                    required
                >
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="price">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                        </svg>
                        Price
                    </label>
                    <input 
                        type="number" 
                        id="price" 
                        name="price" 
                        class="form-control" 
                        step="0.01"
                        min="0"
                        placeholder="249.99"
                        value="<?php echo isset($item) ? htmlspecialchars($item['price']) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency" class="form-control">
                        <option value="USD" <?php echo (!isset($item) || $item['currency'] === 'USD') ? 'selected' : ''; ?>>USD $</option>
                        <option value="EUR" <?php echo (isset($item) && $item['currency'] === 'EUR') ? 'selected' : ''; ?>>EUR €</option>
                        <option value="GBP" <?php echo (isset($item) && $item['currency'] === 'GBP') ? 'selected' : ''; ?>>GBP £</option>
                        <option value="JPY" <?php echo (isset($item) && $item['currency'] === 'JPY') ? 'selected' : ''; ?>>JPY ¥</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="affiliate_link">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                    </svg>
                    Affiliate Link
                </label>
                <input 
                    type="url" 
                    id="affiliate_link" 
                    name="affiliate_link" 
                    class="form-control" 
                    placeholder="https://amazon.com/..."
                    value="<?php echo isset($item) ? htmlspecialchars($item['affiliate_link']) : ''; ?>"
                >
                <small style="color: var(--text-light);">Amazon, eBay, or other affiliate link</small>
            </div>
            
            <div class="form-group">
                <label for="image_url">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                    </svg>
                    Product Image URL
                </label>
                <input 
                    type="text" 
                    id="image_url" 
                    name="image_url" 
                    class="form-control" 
                    placeholder="https://example.com/image.jpg or /assets/images/image.jpg"
                    value="<?php echo isset($item) ? htmlspecialchars($item['image_url']) : ''; ?>"
                >
                <small style="color: var(--text-light);">Full URL or relative path (e.g., /assets/images/product.jpg)</small>
            </div>
            
            <div class="form-group">
                <label for="rating">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    Rating
                </label>
                <input 
                    type="number" 
                    id="rating" 
                    name="rating" 
                    class="form-control" 
                    step="0.1"
                    min="0"
                    max="5"
                    placeholder="4.5"
                    value="<?php echo isset($item) ? htmlspecialchars($item['rating']) : ''; ?>"
                >
                <small style="color: var(--text-light);">Rating out of 5.0</small>
            </div>
            
            <div class="form-group">
                <label for="description">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd"/>
                        <path d="M15 7h1a2 2 0 012 2v5.5a1.5 1.5 0 01-3 0V7z"/>
                    </svg>
                    Description
                </label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="form-control" 
                    rows="6" 
                    placeholder="Detailed product description, features, pros/cons..."
                ><?php echo isset($item) ? htmlspecialchars($item['description']) : ''; ?></textarea>
                <small style="color: var(--text-light);">Supports Markdown formatting</small>
            </div>
            
            <div class="form-group">
                <label for="categories">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/>
                    </svg>
                    Categories
                </label>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                    <?php 
                    $currentParent = null;
                    foreach ($allCategories as $cat): 
                        // Ensure both values are integers for proper comparison
                        $catId = (int)$cat['id'];
                        $isChecked = isset($productCategories) && in_array($catId, $productCategories, true);
                        $isParent = $cat['parent_id'] === null;
                        // Get parent ID for validation
                        $parentId = $cat['parent_id'] ? (int)$cat['parent_id'] : null;
                    ?>
                        <?php if ($isParent): ?>
                            <?php if ($currentParent !== null): ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-bottom: 1rem;">
                                <div style="font-weight: 600; margin-bottom: 0.5rem; color: var(--primary-color);">
                                    <label style="cursor: pointer;" title="Category ID: <?php echo $catId; ?>">
                                        <input 
                                            type="checkbox" 
                                            name="categories[]" 
                                            value="<?php echo $catId; ?>"
                                            id="cat_<?php echo $catId; ?>"
                                            data-cat-id="<?php echo $catId; ?>"
                                            data-cat-name="<?php echo htmlspecialchars($cat['name']); ?>"
                                            <?php echo $isChecked ? 'checked' : ''; ?>
                                            style="margin-right: 0.5rem;"
                                        >
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </label>
                                </div>
                            <?php $currentParent = $catId; ?>
                        <?php else: ?>
                            <div style="margin-left: 1.5rem; margin-bottom: 0.25rem;">
                                <label style="cursor: pointer; font-weight: normal;" title="Category ID: <?php echo $catId; ?>, Parent ID: <?php echo $parentId; ?>">
                                    <input 
                                        type="checkbox" 
                                        name="categories[]" 
                                        value="<?php echo $catId; ?>"
                                        id="cat_<?php echo $catId; ?>"
                                        data-cat-id="<?php echo $catId; ?>"
                                        data-cat-name="<?php echo htmlspecialchars($cat['name']); ?>"
                                        data-parent-id="<?php echo $parentId; ?>"
                                        <?php echo $isChecked ? 'checked' : ''; ?>
                                        style="margin-right: 0.5rem;"
                                    >
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                    <?php if ($cat['parent_name']): ?>
                                        <span style="color: var(--text-light); font-size: 0.85em;">(<?php echo htmlspecialchars($cat['parent_name']); ?>)</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($currentParent !== null): ?>
                        </div>
                    <?php endif; ?>
                </div>
                <small style="color: var(--text-light);">Select one or more categories</small>
            </div>
            
            <div class="form-group">
                <label for="status">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Status *
                </label>
                <select id="status" name="status" class="form-control" required>
                    <option value="active" <?php echo (!isset($item) || $item['status'] === 'active') ? 'selected' : ''; ?>>✅ Active - Available for review</option>
                    <option value="inactive" <?php echo (isset($item) && $item['status'] === 'inactive') ? 'selected' : ''; ?>>⏸️ Inactive - Hidden from public</option>
                    <option value="out_of_stock" <?php echo (isset($item) && $item['status'] === 'out_of_stock') ? 'selected' : ''; ?>>📦 Out of Stock - Not available</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-top: 2rem;">
                <button type="submit" class="btn btn-success">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Save Product
                </button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
                <?php if (isset($item)): ?>
                    <a href="?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-danger" data-confirm="Are you sure you want to delete this product?" style="margin-left: auto;">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Delete
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<script>
function validateCategorySelection(form) {
    // Debug: Log all selected categories before submission
    const checkboxes = form.querySelectorAll('input[name="categories[]"]:checked');
    const selectedCategories = [];
    checkboxes.forEach(function(cb) {
        const catId = cb.value;
        const catName = cb.getAttribute('data-cat-name') || 'Unknown';
        const parentId = cb.getAttribute('data-parent-id') || 'none';
        selectedCategories.push({
            id: catId,
            name: catName,
            parentId: parentId
        });
        console.log('Selected category:', catId, '-', catName, '(Parent ID:', parentId + ')');
    });
    
    if (selectedCategories.length === 0) {
        console.warn('No categories selected');
    } else {
        console.log('Total categories selected:', selectedCategories.length);
    }
    
    return true; // Allow form submission
}

// Also log on page load which categories are checked
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="categories[]"]:checked');
    if (checkboxes.length > 0) {
        console.log('Currently checked categories:');
        checkboxes.forEach(function(cb) {
            const catId = cb.value;
            const catName = cb.getAttribute('data-cat-name') || 'Unknown';
            const parentId = cb.getAttribute('data-parent-id') || 'none';
            console.log('  - ID:', catId, 'Name:', catName, 'Parent ID:', parentId);
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

