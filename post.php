<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Parsedown.php';

// Initialize Markdown parser
$Parsedown = new Parsedown();

// Get slug from URL
$slug = $_GET['slug'] ?? '';

if (!$slug) {
    redirect('/');
}

// Get post by slug with linked product info
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT c.*, u.username as author, u.email as author_email,
                       p.name as product_name, p.affiliate_link as product_link, p.slug as product_slug
                       FROM content c 
                       LEFT JOIN users u ON c.author_id = u.id 
                       LEFT JOIN products p ON c.product_id = p.id
                       WHERE c.slug = ? AND c.status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    redirect('/');
}

// Get related posts
$stmt = $pdo->prepare("SELECT c.*, u.username as author 
                       FROM content c 
                       LEFT JOIN users u ON c.author_id = u.id 
                       WHERE c.status = 'published' 
                       AND c.id != ? 
                       ORDER BY c.created_at DESC 
                       LIMIT 3");
$stmt->execute([$post['id']]);
$relatedPosts = $stmt->fetchAll();

$pageTitle = $post['title'];
include __DIR__ . '/includes/header.php';
?>

<!-- Post Content -->
<article class="post-single">
    <div class="container" style="max-width: 900px;">
        
        <!-- Post Header -->
        <div class="post-header" style="margin-bottom: 3rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <span class="badge badge-primary" style="font-size: 0.9rem; padding: 0.5rem 1rem;">
                    <?php echo ucfirst($post['type'] ?? 'post'); ?>
                </span>
            </div>
            
            <h1 style="font-size: 3rem; text-align: center; margin-bottom: 1.5rem; line-height: 1.2;">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>
            
            <?php if ($post['excerpt']): ?>
                <p style="font-size: 1.25rem; text-align: center; color: var(--text-light); margin-bottom: 2rem;">
                    <?php echo htmlspecialchars($post['excerpt']); ?>
                </p>
            <?php endif; ?>
            
            <div style="display: flex; align-items: center; justify-content: center; gap: 2rem; flex-wrap: wrap; padding-top: 1.5rem; border-top: 2px solid var(--border-color);">
                <div class="post-author" style="gap: 1rem;">
                    <div class="post-author-avatar" style="width: 48px; height: 48px; font-size: 1.2rem;">
                        <?php echo strtoupper(substr($post['author'], 0, 1)); ?>
                    </div>
                    <div>
                        <div class="post-author-name" style="font-size: 1rem; font-weight: 600;">
                            <?php echo htmlspecialchars($post['author']); ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-light);">
                            <?php echo htmlspecialchars($post['author_email']); ?>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 1.5rem; color: var(--text-light); font-size: 0.9rem;">
                    <span>📅 <?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                    <span>⏱️ <?php echo rand(5, 10); ?> min read</span>
                    <?php if ($post['created_at'] != $post['updated_at']): ?>
                        <span>✏️ Updated <?php echo date('M j, Y', strtotime($post['updated_at'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Featured Image -->
        <div class="post-featured-image" style="width: 100%; height: 400px; border-radius: 16px; margin-bottom: 3rem; box-shadow: var(--shadow-lg); overflow: hidden;">
            <?php 
            // Check for specific post images
            $imagePath = null;
            if ($post['slug'] === 'apple-airpods-4-review-2024') {
                $imagePath = '/assets/images/airpods-4-hero.svg';
            }
            
            if ($imagePath): ?>
                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 100%; background: var(--gradient-primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; font-weight: 800; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                    <?php echo strtoupper(substr($post['title'], 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Buy Button (if linked product has affiliate link) -->
        <?php if (!empty($post['product_link'])): ?>
            <div style="text-align: center; margin: 2rem 0;">
                <a href="<?php echo htmlspecialchars($post['product_link']); ?>" 
                   class="btn btn-gradient" 
                   target="_blank" 
                   rel="nofollow noopener"
                   style="display: inline-flex; align-items: center; gap: 0.75rem; font-size: 1.125rem; padding: 1rem 2rem;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                    </svg>
                    Buy on Amazon
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/>
                        <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/>
                    </svg>
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Post Content -->
        <div class="post-content card" style="font-size: 1.125rem; line-height: 1.8;">
            <?php echo $Parsedown->text($post['content']); ?>
        </div>
        
        <!-- Post Footer -->
        <div class="card" style="background: var(--light-color); border: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h4 style="margin-bottom: 0.5rem;">Share this post</h4>
                    <div style="display: flex; gap: 0.75rem;">
                        <?php 
                        $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/post/" . $post['slug'];
                        ?>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($fullUrl); ?>&text=<?php echo urlencode($post['title']); ?>" target="_blank" class="btn btn-sm btn-primary">Twitter</a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($fullUrl); ?>" target="_blank" class="btn btn-sm btn-secondary">Facebook</a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode($fullUrl); ?>&title=<?php echo urlencode($post['title']); ?>" target="_blank" class="btn btn-sm btn-secondary">LinkedIn</a>
                    </div>
                </div>
                
                <?php if (isLoggedIn() && (isAdmin() || $_SESSION['user_id'] == $post['author_id'])): ?>
                    <div>
                        <a href="/admin/content.php?edit=<?php echo $post['id']; ?>" class="btn btn-sm btn-secondary">Edit Post</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Related Posts -->
        <?php if (!empty($relatedPosts)): ?>
            <div style="margin-top: 4rem;">
                <h2 style="text-align: center; margin-bottom: 2.5rem;">Related Posts</h2>
                <div class="grid grid-3">
                    <?php foreach ($relatedPosts as $relatedPost): ?>
                        <article class="post-card">
                            <div class="post-card-image">
                                <?php 
                                // Check for specific post images
                                $relatedImagePath = null;
                                if ($relatedPost['slug'] === 'apple-airpods-4-review-2024') {
                                    $relatedImagePath = '/assets/images/airpods-4-hero.svg';
                                }
                                
                                if ($relatedImagePath): ?>
                                    <img src="<?php echo $relatedImagePath; ?>" alt="<?php echo htmlspecialchars($relatedPost['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php endif; ?>
                                
                                <span class="post-card-badge">
                                    <?php echo ucfirst($relatedPost['type'] ?? 'post'); ?>
                                </span>
                            </div>
                            <div class="post-card-content">
                                <div class="post-card-meta">
                                    <span>📅 <?php echo date('M j, Y', strtotime($relatedPost['created_at'])); ?></span>
                                </div>
                                <h3 class="post-card-title">
                                    <a href="/post/<?php echo urlencode($relatedPost['slug']); ?>">
                                        <?php echo htmlspecialchars($relatedPost['title']); ?>
                                    </a>
                                </h3>
                                <p class="post-card-excerpt">
                                    <?php 
                                    if ($relatedPost['excerpt']) {
                                        echo htmlspecialchars(substr($relatedPost['excerpt'], 0, 100));
                                    } else {
                                        echo htmlspecialchars(substr(strip_tags($relatedPost['content']), 0, 100));
                                    }
                                    echo '...';
                                    ?>
                                </p>
                                <div class="post-card-footer">
                                    <div class="post-author">
                                        <div class="post-author-avatar">
                                            <?php echo strtoupper(substr($relatedPost['author'], 0, 1)); ?>
                                        </div>
                                        <span class="post-author-name"><?php echo htmlspecialchars($relatedPost['author']); ?></span>
                                    </div>
                                        <a href="/post/<?php echo urlencode($relatedPost['slug']); ?>" class="btn btn-sm btn-primary">Read More</a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Back to Home -->
        <div style="text-align: center; margin-top: 3rem;">
            <a href="/" class="btn btn-outline">← Back to Home</a>
        </div>
        
    </div>
</article>

<?php include __DIR__ . '/includes/footer.php'; ?>

