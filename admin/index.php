<?php
require_once __DIR__ . '/../config/config.php';
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
include __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard Welcome -->
<div class="card" style="background: var(--gradient-primary); color: white; border: none;">
    <h1 style="color: white; margin-bottom: 0.5rem;">👋 Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p style="color: rgba(255,255,255,0.9); font-size: 1.1rem;">Here's what's happening with your content today.</p>
</div>

<!-- Statistics Grid -->
<div class="stats-grid">
    <div class="stat-card gradient-1">
        <h3><?php echo $stats['content']; ?></h3>
        <p>Total Posts</p>
    </div>
    
    <div class="stat-card gradient-2">
        <h3><?php echo $stats['published']; ?></h3>
        <p>Published</p>
    </div>
    
    <div class="stat-card gradient-3">
        <h3><?php echo $stats['drafts']; ?></h3>
        <p>Drafts</p>
    </div>
    
    <?php if (isAdmin()): ?>
    <div class="stat-card gradient-1">
        <h3><?php echo $stats['users']; ?></h3>
        <p>Total Users</p>
    </div>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="card">
    <h2>Quick Actions</h2>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem;">
        <a href="content.php?action=new" class="btn btn-gradient">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
            </svg>
            New Post
        </a>
        <a href="content.php" class="btn btn-primary">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
            </svg>
            All Content
        </a>
        <?php if (isAdmin()): ?>
        <a href="users.php" class="btn btn-secondary">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
            </svg>
            Manage Users
        </a>
        <?php endif; ?>
        <a href="/" class="btn btn-outline">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
            </svg>
            View Site
        </a>
    </div>
</div>

<!-- Recent Content -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Recent Content</h2>
        <a href="content.php" class="btn btn-sm btn-primary">View All</a>
    </div>
    
    <?php if (empty($recentContent)): ?>
        <div class="empty-state" style="padding: 3rem; border: none;">
            <div class="empty-state-icon">📝</div>
            <h3>No content yet</h3>
            <p>Start creating amazing content for your website!</p>
            <a href="content.php?action=new" class="btn btn-gradient">Create Your First Post</a>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
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
                        <td>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($item['author']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $item['status'] === 'published' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($item['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                        <td class="table-actions">
                            <a href="content.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <?php if ($item['status'] === 'published'): ?>
                                <a href="/post/<?php echo urlencode($item['slug']); ?>" class="btn btn-secondary btn-sm" target="_blank">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Additional Info -->
<div class="grid grid-2">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
        <h3 style="color: white;">💡 Quick Tip</h3>
        <p style="color: rgba(255,255,255,0.9);">Use the excerpt field to create compelling summaries of your posts that will appear in listings and search results.</p>
    </div>
    
    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none;">
        <h3 style="color: white;">🚀 Getting Started</h3>
        <p style="color: rgba(255,255,255,0.9);">Create engaging content, publish when ready, and share with your audience. Your dashboard shows all your stats at a glance.</p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
