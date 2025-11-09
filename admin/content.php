<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';
$contentId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'new' || $action === 'edit') {
        $title = sanitize($_POST['title']);
        // Create URL-safe slug: lowercase, remove special chars, replace spaces with hyphens
        $slug = strtolower(trim(preg_replace('/[^a-z0-9\s-]/', '', strtolower($_POST['title']))));
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        $content = $_POST['content']; // Don't sanitize content - allow HTML
        $excerpt = sanitize($_POST['excerpt']);
        $heroImageUrl = sanitize($_POST['hero_image_url']);
        $status = $_POST['status'];
        $productId = !empty($_POST['product_id']) ? $_POST['product_id'] : null;
        
        // Handle hero image upload
        if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/images/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $file = $_FILES['hero_image'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            
            // Get file extension
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Allowed file types
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            
            if (in_array($fileExt, $allowed)) {
                if ($fileError === 0) {
                    if ($fileSize < 5000000) { // 5MB limit
                        // Generate unique filename
                        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
                        $fileDestination = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($fileTmpName, $fileDestination)) {
                            $heroImageUrl = '/assets/images/' . $newFileName;
                        } else {
                            $error = 'Failed to upload image.';
                        }
                    } else {
                        $error = 'File too large. Maximum size is 5MB.';
                    }
                } else {
                    $error = 'Error uploading file.';
                }
            } else {
                $error = 'Invalid file type. Allowed: ' . implode(', ', $allowed);
            }
        }
        
        // Allow admins to change author, otherwise use current user
        if (isAdmin() && !empty($_POST['author_id'])) {
            $authorId = intval($_POST['author_id']);
        } else {
            $authorId = $_SESSION['user_id'];
        }
        
        try {
            $pdo->beginTransaction();
            
            if ($action === 'new') {
                $stmt = $pdo->prepare("INSERT INTO content (title, slug, content, excerpt, hero_image_url, status, author_id, product_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $excerpt, $heroImageUrl, $status, $authorId, $productId]);
                $contentId = $pdo->lastInsertId();
                showMessage('Content created successfully!', 'success');
            } else {
                $stmt = $pdo->prepare("UPDATE content SET title=?, slug=?, content=?, excerpt=?, hero_image_url=?, status=?, author_id=?, product_id=? WHERE id=?");
                $stmt->execute([$title, $slug, $content, $excerpt, $heroImageUrl, $status, $authorId, $productId, $contentId]);
                showMessage('Content updated successfully!', 'success');
            }
            
            // Handle tags
            $tagIds = [];
            if (!empty($_POST['tags'])) {
                // Handle both comma-separated string and array
                $tagInput = $_POST['tags'];
                if (is_string($tagInput)) {
                    $tagNames = array_map('trim', explode(',', $tagInput));
                } else {
                    $tagNames = array_map('trim', $tagInput);
                }
                $tagNames = array_filter($tagNames, function($name) {
                    return !empty($name);
                });
                
                foreach ($tagNames as $tagName) {
                    $tagName = sanitize($tagName);
                    if (empty($tagName)) continue;
                    
                    // Generate slug from tag name
                    $tagSlug = strtolower(trim(preg_replace('/[^a-z0-9\s-]/', '', strtolower($tagName))));
                    $tagSlug = preg_replace('/[\s-]+/', '-', $tagSlug);
                    $tagSlug = trim($tagSlug, '-');
                    
                    // Check if tag exists
                    $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
                    $stmt->execute([$tagSlug]);
                    $existingTag = $stmt->fetch();
                    
                    if ($existingTag) {
                        $tagIds[] = $existingTag['id'];
                    } else {
                        // Create new tag
                        $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                        $stmt->execute([$tagName, $tagSlug]);
                        $tagIds[] = $pdo->lastInsertId();
                    }
                }
            }
            
            // Delete existing content_tags for this content
            $stmt = $pdo->prepare("DELETE FROM content_tags WHERE content_id = ?");
            $stmt->execute([$contentId]);
            
            // Insert new content_tags
            if (!empty($tagIds)) {
                $stmt = $pdo->prepare("INSERT INTO content_tags (content_id, tag_id) VALUES (?, ?)");
                foreach ($tagIds as $tagId) {
                    $stmt->execute([$contentId, $tagId]);
                }
            }
            
            $pdo->commit();
            redirect('/admin/content.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Handle delete
if ($action === 'delete' && $contentId) {
    $stmt = $pdo->prepare("DELETE FROM content WHERE id = ?");
    $stmt->execute([$contentId]);
    showMessage('Content deleted successfully!', 'success');
    redirect('/admin/content.php');
}

// Get content for editing
if ($action === 'edit' && $contentId) {
    $stmt = $pdo->prepare("SELECT * FROM content WHERE id = ?");
    $stmt->execute([$contentId]);
    $item = $stmt->fetch();
    if (!$item) {
        redirect('/admin/content.php');
    }
    
    // Get existing tags for this content
    $stmt = $pdo->prepare("SELECT t.id, t.name FROM tags t 
                          INNER JOIN content_tags ct ON t.id = ct.tag_id 
                          WHERE ct.content_id = ? 
                          ORDER BY t.name");
    $stmt->execute([$contentId]);
    $contentTags = $stmt->fetchAll();
} else {
    $contentTags = [];
}

// Get all content
if ($action === 'list') {
    $stmt = $pdo->query("SELECT c.*, u.username as author, p.name as product_name FROM content c 
                         LEFT JOIN users u ON c.author_id = u.id 
                         LEFT JOIN products p ON c.product_id = p.id
                         ORDER BY c.created_at DESC");
    $contentList = $stmt->fetchAll();
    
    // Get tags for each content item
    foreach ($contentList as &$content) {
        $stmt = $pdo->prepare("SELECT t.name FROM tags t 
                              INNER JOIN content_tags ct ON t.id = ct.tag_id 
                              WHERE ct.content_id = ? 
                              ORDER BY t.name");
        $stmt->execute([$content['id']]);
        $content['tags'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    unset($content);
}

// Get all products for the dropdown
$stmt = $pdo->query("SELECT id, name FROM products WHERE status = 'active' ORDER BY name");
$allProducts = $stmt->fetchAll();

// Get all users for author dropdown (admins only)
$stmt = $pdo->query("SELECT id, username, email FROM users ORDER BY username");
$allUsers = $stmt->fetchAll();

// Get all tags for autocomplete
$stmt = $pdo->query("SELECT id, name, slug FROM tags ORDER BY name");
$allTags = $stmt->fetchAll();

$pageTitle = ucfirst($action) . ' Content';
include __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="margin-bottom: 0.25rem;">📝 Content Management</h2>
                <p style="color: var(--text-light); margin: 0;">Manage all your posts and content</p>
            </div>
            <a href="?action=new" class="btn btn-gradient">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Add New Content
            </a>
        </div>
        
        <?php if (empty($contentList)): ?>
            <div class="empty-state" style="border: none;">
                <div class="empty-state-icon">📄</div>
                <h3>No content yet</h3>
                <p>Click "Add New Content" to create your first post!</p>
                <a href="?action=new" class="btn btn-gradient">Create Your First Post</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Tags</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contentList as $c): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($c['title']); ?></strong>
                                <br>
                                <small style="color: var(--text-light);">/<?php echo htmlspecialchars($c['slug']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($c['author']); ?></td>
                            <td>
                                <?php if (!empty($c['tags'])): ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        <?php foreach ($c['tags'] as $tag): ?>
                                            <span class="badge badge-info" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['product_name']): ?>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($c['product_name']); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $c['status'] === 'published' ? 'success' : 
                                        ($c['status'] === 'draft' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($c['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($c['created_at'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($c['updated_at'])); ?></td>
                            <td class="table-actions">
                                <?php if ($c['status'] === 'published'): ?>
                                    <a href="/post/<?php echo htmlspecialchars($c['slug']); ?>" class="btn btn-secondary btn-sm" target="_blank" title="View Post">
                                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                    </svg>
                                </a>
                                <a href="?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-danger btn-sm" 
                                   data-confirm="Are you sure you want to delete '<?php echo htmlspecialchars($c['title']); ?>'?" title="Delete">
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
                <p>Total: <strong><?php echo count($contentList); ?></strong> post(s)</p>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="card">
        <div style="margin-bottom: 2rem;">
            <h2><?php echo $action === 'new' ? '✨ Add New Content' : '✏️ Edit Content'; ?></h2>
            <p style="color: var(--text-light); margin-top: 0.5rem;">
                <?php echo $action === 'new' ? 'Create engaging content for your audience' : 'Update your content'; ?>
            </p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                    </svg>
                    Title *
                </label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    class="form-control" 
                    placeholder="Enter an engaging title..."
                    value="<?php echo isset($item) ? htmlspecialchars($item['title']) : ''; ?>" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="excerpt">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    Excerpt
                </label>
                <textarea 
                    id="excerpt" 
                    name="excerpt" 
                    class="form-control" 
                    rows="3"
                    maxlength="300"
                    placeholder="A brief summary that will appear in listings and search results..."
                ><?php echo isset($item) ? htmlspecialchars($item['excerpt']) : ''; ?></textarea>
                <small style="color: var(--text-light);">Optional short description (recommended for better engagement)</small>
            </div>
            
            <div class="form-group">
                <label for="hero_image">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                    </svg>
                    Hero Image
                </label>
                
                <!-- Image Upload -->
                <div style="margin-bottom: 1rem;">
                    <input 
                        type="file" 
                        id="hero_image" 
                        name="hero_image" 
                        class="form-control" 
                        accept="image/*"
                        onchange="previewImage(this)"
                    >
                    <small style="color: var(--text-light);">Upload an image (JPG, PNG, GIF, WebP, SVG) - Max 5MB</small>
                </div>
                
                <!-- Image Preview -->
                <div id="image-preview" style="margin-bottom: 1rem; display: none;">
                    <img id="preview-img" src="" alt="Preview" style="max-width: 300px; max-height: 200px; border-radius: 8px; box-shadow: var(--shadow-sm);">
                </div>
                
                <!-- Current Image Display -->
                <?php if (isset($item) && !empty($item['hero_image_url'])): ?>
                <div style="margin-bottom: 1rem;">
                    <label style="font-weight: 600; color: var(--text-color);">Current Image:</label>
                    <div style="margin-top: 0.5rem;">
                        <img src="<?php echo htmlspecialchars($item['hero_image_url']); ?>" 
                             alt="Current hero image" 
                             style="max-width: 300px; max-height: 200px; border-radius: 8px; box-shadow: var(--shadow-sm);">
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Manual URL Input -->
                <div>
                    <label for="hero_image_url" style="font-weight: 600; color: var(--text-color);">Or enter image URL:</label>
                    <input 
                        type="text" 
                        id="hero_image_url" 
                        name="hero_image_url" 
                        class="form-control" 
                        placeholder="/assets/images/hero.jpg or https://example.com/image.jpg"
                        value="<?php echo isset($item) ? htmlspecialchars($item['hero_image_url']) : ''; ?>"
                        style="margin-top: 0.5rem;"
                    >
                    <small style="color: var(--text-light);">Full URL or relative path for the featured image</small>
                </div>
            </div>
            
            <?php if (isAdmin()): ?>
            <div class="form-group">
                <label for="author_id">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    Author
                </label>
                <select id="author_id" name="author_id" class="form-control">
                    <?php foreach ($allUsers as $user): ?>
                        <option value="<?php echo $user['id']; ?>" 
                                <?php echo (isset($item) && $item['author_id'] == $user['id']) || (!isset($item) && $user['id'] == $_SESSION['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--text-light);">Select the author for this content (Admin only)</small>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="product_id">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                    </svg>
                    Linked Product
                </label>
                <select id="product_id" name="product_id" class="form-control">
                    <option value="">— No Product —</option>
                    <?php foreach ($allProducts as $product): ?>
                        <option value="<?php echo $product['id']; ?>" 
                                <?php echo (isset($item) && $item['product_id'] == $product['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--text-light);">
                    Link this review to a product. The product's affiliate link will be used for the "Buy on Amazon" button.
                </small>
            </div>
            
            <div class="form-group" style="position: relative;">
                <label for="tags-input">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                    Tags
                </label>
                <div id="tags-container" style="margin-bottom: 0.5rem; min-height: 40px; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; background: var(--bg-color);">
                    <!-- Tags will be displayed here -->
                </div>
                <div style="position: relative;">
                    <input 
                        type="text" 
                        id="tags-input" 
                        class="form-control" 
                        placeholder="Type a tag and press Enter or comma..."
                        autocomplete="off"
                        style="margin-top: 0.5rem;"
                    >
                    <div id="tags-autocomplete" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--border-color); border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 1000; margin-top: 2px; box-shadow: var(--shadow-md);"></div>
                </div>
                <input type="hidden" id="tags" name="tags" value="">
                <small style="color: var(--text-light);">
                    Add tags to categorize your content. Type a tag name and press Enter or comma. Tags are created automatically if they don't exist.
                </small>
            </div>
            
            <div class="form-group">
                <label for="content">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd"/>
                        <path d="M15 7h1a2 2 0 012 2v5.5a1.5 1.5 0 01-3 0V7z"/>
                    </svg>
                    Content *
                </label>
                <textarea 
                    id="content" 
                    name="content" 
                    class="form-control" 
                    rows="15" 
                    placeholder="Write your amazing content here..."
                    required
                ><?php echo isset($item) ? htmlspecialchars($item['content']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="status">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    Status *
                </label>
                <select id="status" name="status" class="form-control" required>
                    <option value="draft" <?php echo (!isset($item) || $item['status'] === 'draft') ? 'selected' : ''; ?>>📝 Draft - Keep working on it</option>
                    <option value="published" <?php echo (isset($item) && $item['status'] === 'published') ? 'selected' : ''; ?>>✅ Published - Make it live</option>
                    <option value="archived" <?php echo (isset($item) && $item['status'] === 'archived') ? 'selected' : ''; ?>>📦 Archived - Hide from public</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; padding-top: 1rem; border-top: 1px solid var(--border-color); margin-top: 2rem;">
                <button type="submit" class="btn btn-success">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Save Content
                </button>
                <a href="content.php" class="btn btn-secondary">Cancel</a>
                <?php if (isset($item)): ?>
                    <a href="?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-danger" data-confirm="Are you sure you want to delete this content?" style="margin-left: auto;">
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
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

// Tag management
(function() {
    const tagsInput = document.getElementById('tags-input');
    const tagsContainer = document.getElementById('tags-container');
    const tagsHidden = document.getElementById('tags');
    const tagsAutocomplete = document.getElementById('tags-autocomplete');
    
    if (!tagsInput) return; // Exit if not on edit/new page
    
    // Available tags for autocomplete
    const availableTags = <?php echo json_encode(array_map(function($tag) { return ['id' => $tag['id'], 'name' => $tag['name']]; }, $allTags)); ?>;
    
    // Current tags
    let currentTags = <?php echo json_encode(array_map(function($tag) { return $tag['name']; }, $contentTags)); ?>;
    
    // Initialize tags display
    function updateTagsDisplay() {
        tagsContainer.innerHTML = '';
        if (currentTags.length === 0) {
            const emptyMsg = document.createElement('span');
            emptyMsg.textContent = 'No tags yet. Start typing to add tags...';
            emptyMsg.style.color = 'var(--text-light)';
            emptyMsg.style.fontStyle = 'italic';
            tagsContainer.appendChild(emptyMsg);
        } else {
            currentTags.forEach(function(tagName, index) {
                const tagElement = createTagElement(tagName);
                tagsContainer.appendChild(tagElement);
            });
        }
        updateHiddenInput();
    }
    
    function createTagElement(tagName) {
        const tagDiv = document.createElement('div');
        tagDiv.className = 'tag-badge';
        tagDiv.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.875rem;';
        
        const tagText = document.createElement('span');
        tagText.textContent = tagName;
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '×';
        removeBtn.style.cssText = 'background: none; border: none; color: white; cursor: pointer; font-size: 1.2rem; line-height: 1; padding: 0; margin-left: 0.25rem; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 50%;';
        removeBtn.onmouseover = function() { this.style.background = 'rgba(255,255,255,0.2)'; };
        removeBtn.onmouseout = function() { this.style.background = 'none'; };
        removeBtn.onclick = function() {
            removeTag(tagName);
        };
        
        tagDiv.appendChild(tagText);
        tagDiv.appendChild(removeBtn);
        
        return tagDiv;
    }
    
    function addTag(tagName) {
        tagName = tagName.trim();
        if (!tagName || currentTags.includes(tagName)) {
            return;
        }
        currentTags.push(tagName);
        updateTagsDisplay();
        tagsInput.value = '';
        hideAutocomplete();
    }
    
    function removeTag(tagName) {
        currentTags = currentTags.filter(function(tag) {
            return tag !== tagName;
        });
        updateTagsDisplay();
    }
    
    function updateHiddenInput() {
        tagsHidden.value = currentTags.join(',');
    }
    
    function showAutocomplete(filter) {
        if (!filter || filter.trim() === '') {
            hideAutocomplete();
            return;
        }
        
        const filterLower = filter.toLowerCase();
        const matches = availableTags.filter(function(tag) {
            return tag.name.toLowerCase().includes(filterLower) && !currentTags.includes(tag.name);
        }).slice(0, 10);
        
        if (matches.length === 0) {
            hideAutocomplete();
            return;
        }
        
        tagsAutocomplete.innerHTML = '';
        matches.forEach(function(tag) {
            const item = document.createElement('div');
            item.style.cssText = 'padding: 0.75rem; cursor: pointer; border-bottom: 1px solid var(--border-color);';
            item.onmouseover = function() { this.style.background = 'var(--bg-hover)'; };
            item.onmouseout = function() { this.style.background = 'white'; };
            item.textContent = tag.name;
            item.onclick = function() {
                addTag(tag.name);
            };
            tagsAutocomplete.appendChild(item);
        });
        
        // Autocomplete is already positioned relative to input container
        tagsAutocomplete.style.display = 'block';
    }
    
    function hideAutocomplete() {
        tagsAutocomplete.style.display = 'none';
    }
    
    // Event listeners
    tagsInput.addEventListener('input', function() {
        showAutocomplete(this.value);
    });
    
    tagsInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const value = this.value.trim();
            if (value) {
                addTag(value);
            }
        } else if (e.key === 'Escape') {
            hideAutocomplete();
        }
    });
    
    tagsInput.addEventListener('blur', function() {
        // Delay to allow click on autocomplete items
        setTimeout(function() {
            hideAutocomplete();
        }, 200);
    });
    
    // Initialize
    updateTagsDisplay();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
