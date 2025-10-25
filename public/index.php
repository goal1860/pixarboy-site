<?php
require_once '../config/config.php';

// Get published content
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT c.*, u.username as author FROM content c 
                      LEFT JOIN users u ON c.author_id = u.id 
                      WHERE c.status = 'published' 
                      ORDER BY c.created_at DESC LIMIT 10");
$posts = $stmt->fetchAll();

$pageTitle = 'Home';
include '../includes/header.php';
?>

<div class="card">
    <h1>Welcome to <?php echo SITE_NAME; ?></h1>
    <p>A simple, modern content management system built with PHP.</p>
</div>

<?php if (empty($posts)): ?>
    <div class="card">
        <p style="text-align: center; color: #666;">No published content yet. <?php if (isLoggedIn()): ?><a href="<?php echo BASE_URL; ?>/admin/content.php">Create your first post!</a><?php endif; ?></p>
    </div>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <div class="card">
            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                By <?php echo htmlspecialchars($post['author']); ?> • 
                <?php echo date('F j, Y', strtotime($post['created_at'])); ?>
            </p>
            
            <?php if ($post['excerpt']): ?>
                <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
            <?php else: ?>
                <p><?php echo htmlspecialchars(substr($post['content'], 0, 200)); ?>...</p>
            <?php endif; ?>
            
            <a href="<?php echo BASE_URL; ?>/post.php?slug=<?php echo urlencode($post['slug']); ?>" class="btn btn-primary btn-sm">Read More</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

