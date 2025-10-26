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
        $slug = sanitize(strtolower(str_replace(' ', '-', $_POST['title'])));
        $content = $_POST['content']; // Don't sanitize content - allow HTML
        $excerpt = sanitize($_POST['excerpt']);
        $status = $_POST['status'];
        $authorId = $_SESSION['user_id'];
        
        try {
            if ($action === 'new') {
                $stmt = $pdo->prepare("INSERT INTO content (title, slug, content, excerpt, status, author_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $excerpt, $status, $authorId]);
                showMessage('Content created successfully!', 'success');
            } else {
                $stmt = $pdo->prepare("UPDATE content SET title=?, slug=?, content=?, excerpt=?, status=? WHERE id=?");
                $stmt->execute([$title, $slug, $content, $excerpt, $status, $contentId]);
                showMessage('Content updated successfully!', 'success');
            }
            redirect('/admin/content.php');
        } catch (PDOException $e) {
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
}

// Get all content
if ($action === 'list') {
    $stmt = $pdo->query("SELECT c.*, u.username as author FROM content c 
                         LEFT JOIN users u ON c.author_id = u.id 
                         ORDER BY c.created_at DESC");
    $contentList = $stmt->fetchAll();
}

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
                                    <a href="<?php echo BASE_URL; ?>/post.php?slug=<?php echo urlencode($c['slug']); ?>" class="btn btn-secondary btn-sm" target="_blank" title="View Post">
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
        
        <form method="POST">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
