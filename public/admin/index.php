<?php
require_once '../../config/config.php';
requireLogin();

$pdo = getDBConnection();

// Get statistics
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'content' => $pdo->query("SELECT COUNT(*) FROM content")->fetchColumn(),
    'published' => $pdo->query("SELECT COUNT(*) FROM content WHERE status = 'published'")->fetchColumn(),
    'drafts' => $pdo->query("SELECT COUNT(*) FROM content WHERE status = 'draft'")->fetchColumn(),
];

// Get recent content
$stmt = $pdo->query("SELECT c.*, u.username as author FROM content c 
                     LEFT JOIN users u ON c.author_id = u.id 
                     ORDER BY c.created_at DESC LIMIT 5");
$recentContent = $stmt->fetchAll();

$pageTitle = 'Dashboard';
include '../../includes/header.php';
?>

<div class="card">
    <h1>Dashboard</h1>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $stats['content']; ?></h3>
        <p>Total Posts</p>
    </div>
    
    <div class="stat-card">
        <h3><?php echo $stats['published']; ?></h3>
        <p>Published</p>
    </div>
    
    <div class="stat-card">
        <h3><?php echo $stats['drafts']; ?></h3>
        <p>Drafts</p>
    </div>
    
    <?php if (isAdmin()): ?>
    <div class="stat-card">
        <h3><?php echo $stats['users']; ?></h3>
        <p>Users</p>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Recent Content</h2>
    
    <?php if (empty($recentContent)): ?>
        <p style="text-align: center; color: #666;">No content yet. <a href="content.php?action=new">Create your first post!</a></p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentContent as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                    <td><?php echo htmlspecialchars($item['author']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $item['status'] === 'published' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($item['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                    <td class="table-actions">
                        <a href="content.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>

