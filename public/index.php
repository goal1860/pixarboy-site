<?php
require_once '../config/config.php';

// Get published content
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT c.*, u.username as author FROM content c 
                      LEFT JOIN users u ON c.author_id = u.id 
                      WHERE c.status = 'published' 
                      ORDER BY c.created_at DESC LIMIT 12");
$posts = $stmt->fetchAll();

$pageTitle = 'Home';
include '../includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Welcome to <?php echo SITE_NAME; ?></h1>
        <p>A modern, beautiful content management system built with PHP. Create, manage, and publish your content with style.</p>
        <div class="hero-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>/admin/content.php" class="btn btn-lg btn-gradient">Create New Post</a>
                <a href="<?php echo BASE_URL; ?>/admin/" class="btn btn-lg btn-outline">Go to Dashboard</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-lg btn-gradient">Get Started</a>
                <a href="#posts" class="btn btn-lg btn-outline">View Posts</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="main-content">
    <div class="container">
        
        <?php if (empty($posts)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">📝</div>
                <h3>No Content Yet</h3>
                <p>There are no published posts yet. Be the first to create something amazing!</p>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/content.php" class="btn btn-primary">Create Your First Post</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary">Login to Create</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Section Header -->
            <div class="section-header" id="posts">
                <h2 class="section-title">Latest Posts</h2>
                <p class="section-subtitle">Discover our latest content and updates</p>
            </div>
            
            <!-- Posts Grid -->
            <div class="grid grid-3">
                <?php foreach ($posts as $post): ?>
                    <article class="post-card fade-in">
                        <!-- Post Image/Placeholder -->
                        <div class="post-card-image">
                            <span class="post-card-badge">
                                <?php 
                                    $type = $post['type'] ?? 'post';
                                    echo ucfirst($type);
                                ?>
                            </span>
                        </div>
                        
                        <!-- Post Content -->
                        <div class="post-card-content">
                            <!-- Meta Information -->
                            <div class="post-card-meta">
                                <span>
                                    📅 <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                </span>
                                <span>
                                    ⏱️ <?php echo rand(3, 8); ?> min read
                                </span>
                            </div>
                            
                            <!-- Title -->
                            <h3 class="post-card-title">
                                <a href="<?php echo BASE_URL; ?>/post.php?slug=<?php echo urlencode($post['slug']); ?>">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h3>
                            
                            <!-- Excerpt -->
                            <p class="post-card-excerpt">
                                <?php 
                                if ($post['excerpt']) {
                                    echo htmlspecialchars(substr($post['excerpt'], 0, 120));
                                } else {
                                    echo htmlspecialchars(substr(strip_tags($post['content']), 0, 120));
                                }
                                echo '...';
                                ?>
                            </p>
                            
                            <!-- Footer with Author and Read More -->
                            <div class="post-card-footer">
                                <div class="post-author">
                                    <div class="post-author-avatar">
                                        <?php echo strtoupper(substr($post['author'], 0, 1)); ?>
                                    </div>
                                    <span class="post-author-name"><?php echo htmlspecialchars($post['author']); ?></span>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/post.php?slug=<?php echo urlencode($post['slug']); ?>" class="btn btn-sm btn-primary">Read More</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php include '../includes/footer.php'; ?>
