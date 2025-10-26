<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pdo = getDBConnection();
$error = '';
$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Check if category has children
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$id]);
        $childCount = $stmt->fetchColumn();
        
        if ($childCount > 0) {
            $error = 'Cannot delete category with subcategories. Delete subcategories first.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Category deleted successfully!';
        }
    } catch (PDOException $e) {
        $error = 'Error deleting category: ' . $e->getMessage();
    }
}

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = sanitize($_POST['name'] ?? '');
    $slug = sanitize($_POST['slug'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $display_order = (int)($_POST['display_order'] ?? 0);
    
    // Auto-generate slug if empty
    if (empty($slug) && !empty($name)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }
    
    if ($name && $slug) {
        try {
            if ($id) {
                // Prevent circular reference
                if ($parent_id == $id) {
                    $error = 'A category cannot be its own parent!';
                } else {
                    // Update
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, display_order = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $description, $parent_id, $display_order, $id]);
                    $success = 'Category updated successfully!';
                }
            } else {
                // Create
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, parent_id, display_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $parent_id, $display_order]);
                $success = 'Category created successfully!';
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $error = 'A category with that name or slug already exists.';
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Name and slug are required.';
    }
}

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCategory = $stmt->fetch();
}

// Fetch all categories with product count
$stmt = $pdo->query("SELECT c.*, 
    p.name as parent_name,
    COUNT(DISTINCT pc.product_id) as product_count
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    LEFT JOIN product_categories pc ON c.id = pc.category_id
    GROUP BY c.id
    ORDER BY 
        CASE WHEN c.parent_id IS NULL THEN c.id ELSE c.parent_id END,
        c.parent_id IS NULL DESC,
        c.display_order,
        c.name");
$categories = $stmt->fetchAll();

// Build hierarchical array for parent selector
function buildCategoryTree($categories, $parentId = null, $exclude = null, $level = 0) {
    $branch = [];
    foreach ($categories as $cat) {
        if ($cat['parent_id'] == $parentId && $cat['id'] != $exclude) {
            $cat['level'] = $level;
            $branch[] = $cat;
            $children = buildCategoryTree($categories, $cat['id'], $exclude, $level + 1);
            $branch = array_merge($branch, $children);
        }
    }
    return $branch;
}

$categoryTree = buildCategoryTree($categories, null, $editCategory['id'] ?? null);

$pageTitle = 'Categories';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1>📁 Categories</h1>
            <p style="color: #666; margin-top: 0.5rem;">Manage your content categories with hierarchical structure</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="/admin/" class="btn btn-outline">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                Dashboard
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <div class="content-layout">
        <!-- Category Form -->
        <div class="card">
            <h2><?php echo $editCategory ? 'Edit Category' : 'Add New Category'; ?></h2>
            <form method="POST" action="">
                <?php if ($editCategory): ?>
                    <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        placeholder="e.g., Technology"
                        value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>"
                        required
                        oninput="document.getElementById('slug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')"
                    >
                </div>
                
                <div class="form-group">
                    <label for="slug">Slug *</label>
                    <input 
                        type="text" 
                        id="slug" 
                        name="slug" 
                        class="form-control" 
                        placeholder="e.g., technology"
                        value="<?php echo $editCategory ? htmlspecialchars($editCategory['slug']) : ''; ?>"
                        required
                        pattern="[a-z0-9-]+"
                        title="Lowercase letters, numbers, and hyphens only"
                    >
                    <small style="color: #666;">URL-friendly version (lowercase, hyphens only)</small>
                </div>
                
                <div class="form-group">
                    <label for="parent_id">Parent Category</label>
                    <select id="parent_id" name="parent_id" class="form-control">
                        <option value="">— None (Top Level) —</option>
                        <?php foreach ($categoryTree as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                <?php echo ($editCategory && $editCategory['parent_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo str_repeat('&nbsp;&nbsp;&nbsp;', $cat['level']) . htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #666;">Select a parent to create a subcategory</small>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        class="form-control" 
                        rows="3"
                        placeholder="Brief description of this category..."
                    ><?php echo $editCategory ? htmlspecialchars($editCategory['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input 
                        type="number" 
                        id="display_order" 
                        name="display_order" 
                        class="form-control" 
                        value="<?php echo $editCategory ? $editCategory['display_order'] : 0; ?>"
                        min="0"
                    >
                    <small style="color: #666;">Lower numbers appear first</small>
                </div>
                
                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editCategory ? 'Update Category' : 'Create Category'; ?>
                    </button>
                    <?php if ($editCategory): ?>
                        <a href="/admin/categories.php" class="btn btn-outline">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Categories List -->
        <div class="card">
            <h2>All Categories (<?php echo count($categories); ?>)</h2>
            
            <?php if (empty($categories)): ?>
                <div class="empty-state" style="padding: 2rem;">
                    <div class="empty-state-icon">📁</div>
                    <h3>No Categories Yet</h3>
                    <p>Create your first category to organize your content.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Parent</th>
                                <th>Products</th>
                                <th>Order</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $tree = buildCategoryTree($categories);
                            foreach ($tree as $cat): 
                            ?>
                                <tr>
                                    <td>
                                        <?php echo str_repeat('&nbsp;&nbsp;&nbsp;', $cat['level']); ?>
                                        <?php if ($cat['level'] > 0): ?>
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; opacity: 0.5;">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars($cat['name']); ?></strong>
                                    </td>
                                    <td>
                                        <code style="background: var(--light-color); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars($cat['slug']); ?>
                                        </code>
                                    </td>
                                    <td>
                                        <?php if ($cat['parent_name']): ?>
                                            <span style="color: #666;"><?php echo htmlspecialchars($cat['parent_name']); ?></span>
                                        <?php else: ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $cat['product_count']; ?> product(s)</span>
                                    </td>
                                    <td><?php echo $cat['display_order']; ?></td>
                                    <td class="table-actions" style="text-align: right;">
                                        <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-primary btn-sm">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        <a href="/category/<?php echo urlencode($cat['slug']); ?>" class="btn btn-secondary btn-sm" target="_blank">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                            </svg>
                                            View
                                        </a>
                                        <a href="?delete=<?php echo $cat['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this category?<?php if ($cat['product_count'] > 0) echo '\n\nWarning: This category has ' . $cat['product_count'] . ' product(s) assigned to it.'; ?>')">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.content-layout {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
    margin-top: 2rem;
}

@media (max-width: 968px) {
    .content-layout {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>

