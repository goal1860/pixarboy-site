<?php
require_once __DIR__ . '/config/config.php';

// Get published content
$pdo = getDBConnection();

// Get all published posts
$stmt = $pdo->query("SELECT c.*, u.username as author FROM content c 
                      LEFT JOIN users u ON c.author_id = u.id 
                      WHERE c.status = 'published' 
                      ORDER BY c.created_at DESC");
$allPosts = $stmt->fetchAll();

// Get featured post (most recent)
$featuredPost = !empty($allPosts) ? $allPosts[0] : null;

// Get remaining posts
$remainingPosts = array_slice($allPosts, 1);

// Get recent posts for sidebar
$recentPosts = array_slice($allPosts, 0, 5);

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>

<!-- Hero/Featured Section -->
<?php if ($featuredPost): ?>
<section class="featured-hero">
    <div class="container">
        <div class="featured-hero-content">
            <div class="featured-hero-image">
                <?php 
                $heroImagePath = null;
                if ($featuredPost['slug'] === 'apple-airpods-4-review') {
                    $heroImagePath = BASE_URL . '/assets/images/airpods-4-hero.svg';
                }
                
                if ($heroImagePath): ?>
                    <img src="<?php echo $heroImagePath; ?>" alt="<?php echo htmlspecialchars($featuredPost['title']); ?>">
                <?php else: ?>
                    <div class="featured-placeholder"></div>
                <?php endif; ?>
                
                <div class="featured-badge">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    Featured
                </div>
            </div>
            
            <div class="featured-hero-text">
                <span class="featured-category">Latest Review</span>
                <h1 class="featured-title">
                    <a href="<?php echo BASE_URL; ?>/post.php?slug=<?php echo urlencode($featuredPost['slug']); ?>">
                        <?php echo htmlspecialchars($featuredPost['title']); ?>
                    </a>
                </h1>
                
                <?php if ($featuredPost['excerpt']): ?>
                    <p class="featured-excerpt">
                        <?php echo htmlspecialchars($featuredPost['excerpt']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="featured-meta">
                    <div class="post-author">
                        <div class="post-author-avatar">
                            <?php echo strtoupper(substr($featuredPost['author'], 0, 1)); ?>
                        </div>
                        <div>
                            <span class="post-author-name"><?php echo htmlspecialchars($featuredPost['author']); ?></span>
                            <span class="post-date"><?php echo date('F j, Y', strtotime($featuredPost['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <a href="<?php echo BASE_URL; ?>/post.php?slug=<?php echo urlencode($featuredPost['slug']); ?>" class="btn btn-gradient">
                        Read Full Review
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-left: 5px;">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Main Content with Sidebar -->
<div class="main-content">
    <div class="container">
        <div class="content-with-sidebar">
            
            <!-- Main Content Area -->
            <div class="primary-content">
                
                <?php if (empty($allPosts)): ?>
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
                    <div class="section-header-inline">
                        <h2 class="section-title-inline">Latest Reviews & Articles</h2>
                        <div class="view-options">
                            <button class="view-btn active" data-view="grid" title="Grid View">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM13 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2h-2z"/>
                                </svg>
                            </button>
                            <button class="view-btn" data-view="list" title="List View">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Posts Grid -->
                    <div class="posts-grid grid grid-2" id="postsContainer">
                        <?php foreach ($remainingPosts as $post): ?>
                            <article class="post-card fade-in">
                                <!-- Post Image -->
                                <div class="post-card-image">
                                    <?php 
                                    $cardImagePath = null;
                                    if ($post['slug'] === 'apple-airpods-4-review') {
                                        $cardImagePath = BASE_URL . '/assets/images/airpods-4-hero.svg';
                                    }
                                    
                                    if ($cardImagePath): ?>
                                        <img src="<?php echo $cardImagePath; ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                    <?php endif; ?>
                                    
                                    <span class="post-card-badge">Review</span>
                                </div>
                                
                                <!-- Post Content -->
                                <div class="post-card-content">
                                    <div class="post-card-meta">
                                        <span>📅 <?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                                        <span>⏱️ <?php echo rand(3, 8); ?> min read</span>
                                    </div>
                                    
                                    <h3 class="post-card-title">
                                        <a href="<?php echo BASE_URL; ?>/post.php?slug=<?php echo urlencode($post['slug']); ?>">
                                            <?php echo htmlspecialchars($post['title']); ?>
                                        </a>
                                    </h3>
                                    
                                    <p class="post-card-excerpt">
                                        <?php 
                                        if ($post['excerpt']) {
                                            echo htmlspecialchars(substr($post['excerpt'], 0, 150));
                                        } else {
                                            echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150));
                                        }
                                        echo '...';
                                        ?>
                                    </p>
                                    
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
            
            <!-- Sidebar -->
            <aside class="sidebar">
                
                <!-- Search Widget -->
                <div class="widget widget-search">
                    <h3 class="widget-title">Search</h3>
                    <form class="search-form" action="#" method="get">
                        <input type="text" class="form-control" placeholder="Search articles..." name="s">
                        <button type="submit" class="search-btn">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </form>
                </div>
                
                <!-- About Widget -->
                <div class="widget widget-about">
                    <h3 class="widget-title">About <?php echo SITE_NAME; ?></h3>
                    <div class="about-content">
                        <div class="about-avatar">
                            <svg width="60" height="60" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <p>Your trusted source for honest product reviews, tech insights, and buying guides. We help you make informed decisions.</p>
                        <div class="social-links-widget">
                            <a href="#" class="social-link" title="Facebook">
                                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </a>
                            <a href="#" class="social-link" title="Twitter">
                                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                            </a>
                            <a href="#" class="social-link" title="Instagram">
                                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Posts Widget -->
                <?php if (!empty($recentPosts)): ?>
                <div class="widget widget-recent">
                    <h3 class="widget-title">Recent Posts</h3>
                    <ul class="recent-posts-list">
                        <?php foreach ($recentPosts as $recent): ?>
                        <li class="recent-post-item">
                            <div class="recent-post-thumb">
                                <?php 
                                $thumbPath = null;
                                if ($recent['slug'] === 'apple-airpods-4-review') {
                                    $thumbPath = BASE_URL . '/assets/images/airpods-4-hero.svg';
                                }
                                
                                if ($thumbPath): ?>
                                    <img src="<?php echo $thumbPath; ?>" alt="<?php echo htmlspecialchars($recent['title']); ?>">
                                <?php else: ?>
                                    <div class="recent-post-thumb-placeholder">
                                        <?php echo strtoupper(substr($recent['title'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="recent-post-content">
                                <h4>
                                    <a href="<?php echo BASE_URL; ?>/post.php?slug=<?php echo urlencode($recent['slug']); ?>">
                                        <?php echo htmlspecialchars(substr($recent['title'], 0, 50)) . (strlen($recent['title']) > 50 ? '...' : ''); ?>
                                    </a>
                                </h4>
                                <span class="recent-post-date">
                                    <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle;">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                    </svg>
                                    <?php echo date('M j, Y', strtotime($recent['created_at'])); ?>
                                </span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Newsletter Widget -->
                <div class="widget widget-newsletter">
                    <h3 class="widget-title">Newsletter</h3>
                    <p class="newsletter-desc">Subscribe to get the latest reviews and tech news!</p>
                    <form class="newsletter-form" action="#" method="post">
                        <input type="email" class="form-control" placeholder="Your email address" required>
                        <button type="submit" class="btn btn-gradient btn-block">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            Subscribe Now
                        </button>
                    </form>
                </div>
                
                <!-- Tags Widget -->
                <div class="widget widget-tags">
                    <h3 class="widget-title">Popular Tags</h3>
                    <div class="tag-cloud">
                        <a href="#" class="tag-item">Technology</a>
                        <a href="#" class="tag-item">Reviews</a>
                        <a href="#" class="tag-item">Apple</a>
                        <a href="#" class="tag-item">Audio</a>
                        <a href="#" class="tag-item">Gadgets</a>
                        <a href="#" class="tag-item">Wireless</a>
                        <a href="#" class="tag-item">Smart Home</a>
                        <a href="#" class="tag-item">Gaming</a>
                    </div>
                </div>
                
            </aside>
            
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
