<?php
require_once __DIR__ . '/config/config.php';

$pdo = getDBConnection();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Find "News" tag
$stmt = $pdo->prepare("SELECT * FROM tags WHERE LOWER(name) = 'news' OR LOWER(slug) = 'news' LIMIT 1");
$stmt->execute();
$tagInfo = $stmt->fetch();

$results = [];
$totalResults = 0;
$tagFound = false;

if ($tagInfo) {
    $tagFound = true;
    
    // Count total results for News tag
    $countSQL = "
        SELECT COUNT(*) as total
        FROM content c
        INNER JOIN content_tags ct ON c.id = ct.content_id
        WHERE c.status = 'published'
        AND ct.tag_id = ?
    ";
    
    $stmt = $pdo->prepare($countSQL);
    $stmt->execute([$tagInfo['id']]);
    $totalResults = $stmt->fetch()['total'];
    
    // Get content with News tag
    $searchSQL = "
        SELECT c.*, u.username as author
        FROM content c
        INNER JOIN content_tags ct ON c.id = ct.content_id
        LEFT JOIN users u ON c.author_id = u.id
        WHERE c.status = 'published'
        AND ct.tag_id = ?
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($searchSQL);
    $stmt->execute([$tagInfo['id'], $perPage, $offset]);
    $results = $stmt->fetchAll();
    
    // Calculate total pages
    $totalPages = ceil($totalResults / $perPage);
}

// SEO Configuration
$pageTitle = 'News';
$seoData = [
    'title' => 'Tech News - ' . SITE_NAME,
    'description' => 'Stay up to date with the latest tech news, product launches, and industry updates.',
    'keywords' => 'tech news, technology news, product launches, tech updates, industry news',
    'type' => 'website',
    'url' => '/news.php',
];

include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/seo.php';
generateBreadcrumbStructuredData([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'News', 'url' => '/news.php']
]);
?>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container">
        <div class="page-hero-content">
            <h1>📰 Tech News</h1>
            <p class="page-hero-description">
                Stay up to date with the latest tech news, product launches, and industry updates
            </p>
        </div>
    </div>
</div>

<div class="container">
    <div class="content-wrapper">
        <main class="main-content">
            
            <?php if ($tagFound && $totalResults > 0): ?>
                
                <!-- Results Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="margin-bottom: 0.25rem;">Latest News</h2>
                        <p style="color: var(--text-light); margin: 0;">
                            <?php echo $totalResults; ?> article<?php echo $totalResults != 1 ? 's' : ''; ?> found
                        </p>
                    </div>
                </div>
                
                <!-- News Articles -->
                <div class="posts-grid">
                    <?php foreach ($results as $article): ?>
                        <article class="post-card">
                            <?php if (!empty($article['hero_image_url'])): ?>
                                <a href="/post/<?php echo htmlspecialchars($article['slug']); ?>" class="post-image">
                                    <img src="<?php echo htmlspecialchars($article['hero_image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['title']); ?>">
                                </a>
                            <?php endif; ?>
                            
                            <div class="post-body">
                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                                    <span class="badge badge-primary" style="font-size: 0.85rem;">
                                        📰 News
                                    </span>
                                    <span style="color: var(--text-light); font-size: 0.875rem;">
                                        <?php echo date('M j, Y', strtotime($article['created_at'])); ?>
                                    </span>
                                    <?php if ($article['author']): ?>
                                        <span style="color: var(--text-light); font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($article['author']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="post-title">
                                    <a href="/post/<?php echo htmlspecialchars($article['slug']); ?>">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h3>
                                
                                <?php if ($article['excerpt']): ?>
                                    <p class="post-excerpt">
                                        <?php echo htmlspecialchars($article['excerpt']); ?>
                                    </p>
                                <?php else: ?>
                                    <p class="post-excerpt">
                                        <?php 
                                        $content = strip_tags($article['content']);
                                        echo htmlspecialchars(substr($content, 0, 150));
                                        if (strlen($content) > 150) echo '...';
                                        ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div style="margin-top: 1rem;">
                                    <a href="/post/<?php echo htmlspecialchars($article['slug']); ?>" 
                                       class="btn btn-primary btn-sm">
                                        Read More →
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination" style="margin-top: 3rem;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">
                                ← Previous
                            </a>
                        <?php else: ?>
                            <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;">← Previous</span>
                        <?php endif; ?>
                        
                        <span style="color: var(--text-light); padding: 0.5rem 1rem;">
                            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                        </span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">
                                Next →
                            </a>
                        <?php else: ?>
                            <span class="btn btn-secondary" style="opacity: 0.5; cursor: not-allowed;">Next →</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($tagFound && $totalResults === 0): ?>
                
                <!-- No News Articles -->
                <div class="empty-state">
                    <div class="empty-state-icon">📰</div>
                    <h3>No news articles yet</h3>
                    <p>Check back soon for the latest tech news and updates!</p>
                    <a href="/" class="btn btn-gradient">Back to Home</a>
                </div>
                
            <?php else: ?>
                
                <!-- News Tag Not Found -->
                <div class="empty-state">
                    <div class="empty-state-icon">📰</div>
                    <h3>News tag not found</h3>
                    <p>The "News" tag hasn't been created yet. Articles tagged with "News" will appear here once the tag is created.</p>
                    <a href="/" class="btn btn-gradient">Back to Home</a>
                </div>
                
            <?php endif; ?>
            
        </main>
        
        <!-- Sidebar -->
        <aside class="sidebar">
            
            <!-- Popular Tags Widget -->
            <div class="widget widget-tags">
                <h3 class="widget-title">Popular Tags</h3>
                <?php
                $stmt = $pdo->query("SELECT t.id, t.name, t.slug, COUNT(ct.content_id) as usage_count
                                      FROM tags t
                                      INNER JOIN content_tags ct ON t.id = ct.tag_id
                                      INNER JOIN content c ON ct.content_id = c.id
                                      WHERE c.status = 'published'
                                      GROUP BY t.id, t.name, t.slug
                                      ORDER BY usage_count DESC, t.name ASC
                                      LIMIT 15");
                $popularTags = $stmt->fetchAll();
                ?>
                
                <?php if (!empty($popularTags)): ?>
                    <div class="tag-cloud">
                        <?php foreach ($popularTags as $tag): ?>
                            <a href="/tag?name=<?php echo urlencode($tag['name']); ?>" 
                               class="tag-item" 
                               title="<?php echo htmlspecialchars($tag['usage_count']); ?> post<?php echo $tag['usage_count'] != 1 ? 's' : ''; ?>">
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-light); font-size: 0.875rem; margin: 0;">No tags yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Recent Posts Widget -->
            <div class="widget">
                <h3 class="widget-title">Recent Posts</h3>
                <?php
                $stmt = $pdo->query("SELECT title, slug, created_at FROM content 
                                     WHERE status = 'published' 
                                     ORDER BY created_at DESC 
                                     LIMIT 5");
                $recentPosts = $stmt->fetchAll();
                ?>
                
                <?php if (!empty($recentPosts)): ?>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <?php foreach ($recentPosts as $post): ?>
                            <li style="padding: 0.75rem 0; border-bottom: 1px solid var(--border-color);">
                                <a href="/post/<?php echo htmlspecialchars($post['slug']); ?>" 
                                   style="color: var(--text-color); text-decoration: none; font-weight: 500; display: block;">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                                <span style="color: var(--text-light); font-size: 0.875rem;">
                                    <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: var(--text-light); font-size: 0.875rem; margin: 0;">No recent posts.</p>
                <?php endif; ?>
            </div>
            
        </aside>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

