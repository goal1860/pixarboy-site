<?php
require_once '../../config/config.php';
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
include '../../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Content Management</h2>
            <a href="?action=new" class="btn btn-success">Add New Content</a>
        </div>
        
        <?php if (empty($contentList)): ?>
            <p style="text-align: center; color: #666;">No content yet. Click "Add New Content" to create your first post!</p>
        <?php else: ?>
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
                        <td><?php echo htmlspecialchars($c['title']); ?></td>
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
                            <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirm('Are you sure you want to delete this content?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php else: ?>
    <div class="card">
        <h2><?php echo $action === 'new' ? 'Add New Content' : 'Edit Content'; ?></h2>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="<?php echo isset($item) ? htmlspecialchars($item['title']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" class="form-control" rows="3"><?php 
                    echo isset($item) ? htmlspecialchars($item['excerpt']) : ''; 
                ?></textarea>
                <small style="color: #666;">Optional short description (shown in listings)</small>
            </div>
            
            <div class="form-group">
                <label for="content">Content *</label>
                <textarea id="content" name="content" class="form-control" rows="15" required><?php 
                    echo isset($item) ? htmlspecialchars($item['content']) : ''; 
                ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="draft" <?php echo (!isset($item) || $item['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo (isset($item) && $item['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                    <option value="archived" <?php echo (isset($item) && $item['status'] === 'archived') ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-success">Save Content</button>
            <a href="content.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>

