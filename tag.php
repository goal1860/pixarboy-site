<?php
require_once __DIR__ . '/config/config.php';

$pdo = getDBConnection();

// Get tag name or slug from URL parameter
$tagName = trim($_GET['name'] ?? $_GET['tag'] ?? '');
$tagSlug = trim($_GET['slug'] ?? '');

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$results = [];
$totalResults = 0;
$tagInfo = null;
$tagFound = false;

if (!empty($tagName) || !empty($tagSlug)) {
    // Find tag by name or slug
    if (!empty($tagSlug)) {
        $stmt = $pdo->prepare("SELECT * FROM tags WHERE slug = ?");
        $stmt->execute([$tagSlug]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tags WHERE name = ? OR slug = ?");
        $stmt->execute([$tagName, $tagName]);
    }
    
    $tagInfo = $stmt->fetch();
    
    if ($tagInfo) {
        $tagFound = true;
        
        // Count total results for this tag
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
        
        // Get content with this tag
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
}

// SEO Configuration
$pageTitle = $tagFound ? "Tag: {$tagInfo['name']}" : "Tag";
$seoData = [
    'title' => ($tagFound ? "Tag: {$tagInfo['name']}" : 'Tag') . ' - ' . SITE_NAME,
    'description' => $tagFound 
        ? "Articles tagged with \"{$tagInfo['name']}\". Found $totalResults article(s)." 
        : 'Browse articles by tag on ' . SITE_NAME,
    'type' => 'website',
    'url' => '/tag' . ($tagFound ? '?name=' . urlencode($tagInfo['name']) : '')
];

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <!-- Tag Header -->
    <?php if ($tagFound): ?>
        <div class="tag-hero">
            <div class="tag-hero-pattern"></div>
            <div class="tag-hero-content">
                <div class="tag-hero-header">
                    <div class="tag-hero-left">
                        <div class="tag-hero-icon">
                            <svg width="24" height="24" fill="white" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="tag-hero-title">
                                <?php echo htmlspecialchars($tagInfo['name']); ?>
                            </h1>
                            <?php if ($tagInfo['description']): ?>
                                <p class="tag-hero-description">
                                    <?php echo htmlspecialchars($tagInfo['description']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="tag-hero-stats">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                        </svg>
                        <span>
                            <?php echo number_format($totalResults); ?> 
                            <?php echo $totalResults == 1 ? 'Article' : 'Articles'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="tag-hero tag-hero-secondary">
            <div class="tag-hero-pattern"></div>
            <div class="tag-hero-content">
                <div class="tag-hero-center">
                    <div class="tag-hero-icon">
                        <svg width="24" height="24" fill="white" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="tag-hero-title">
                            Browse by Tag
                        </h1>
                        <p class="tag-hero-description">
                            Select a tag from below to view related articles
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($tagFound): ?>
        
        <?php if ($totalResults > 0): ?>
            
            <!-- Tag Results -->
            <div class="search-results">
                <?php foreach ($results as $result): ?>
                    <article class="card" style="margin-bottom: 1.5rem; padding: 2rem; transition: transform 0.2s, box-shadow 0.2s;" 
                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'"
                             onmouseout="this.style.transform=''; this.style.boxShadow=''">
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                            
                            <!-- Result Image (if available) -->
                            <?php if (!empty($result['hero_image_url'])): ?>
                                <div style="flex-shrink: 0; width: 200px; height: 150px; border-radius: 12px; overflow: hidden;">
                                    <a href="/post/<?php echo htmlspecialchars($result['slug']); ?>">
                                        <img src="<?php echo htmlspecialchars($result['hero_image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($result['title']); ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Result Content -->
                            <div style="flex: 1; min-width: 300px;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                                    <span class="badge badge-primary" style="font-size: 0.85rem;">
                                        Article
                                    </span>
                                    <span style="color: var(--text-light); font-size: 0.9rem;">
                                        📅 <?php echo date('F j, Y', strtotime($result['created_at'])); ?>
                                    </span>
                                    <?php if ($result['author']): ?>
                                        <span style="color: var(--text-light); font-size: 0.9rem;">
                                            👤 <?php echo htmlspecialchars($result['author']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h2 style="margin-bottom: 0.75rem; font-size: 1.5rem; line-height: 1.3;">
                                    <a href="/post/<?php echo htmlspecialchars($result['slug']); ?>" 
                                       style="color: var(--primary-color); text-decoration: none;">
                                        <?php echo htmlspecialchars($result['title']); ?>
                                    </a>
                                </h2>
                                
                                <?php if ($result['excerpt']): ?>
                                    <p style="color: var(--text-light); margin-bottom: 1rem; line-height: 1.6;">
                                        <?php echo htmlspecialchars($result['excerpt']); ?>
                                    </p>
                                <?php else: ?>
                                    <p style="color: var(--text-light); margin-bottom: 1rem; line-height: 1.6;">
                                        <?php echo htmlspecialchars(substr(strip_tags($result['content']), 0, 200)); ?>...
                                    </p>
                                <?php endif; ?>
                                
                                <a href="/post/<?php echo htmlspecialchars($result['slug']); ?>" 
                                   class="btn btn-sm btn-primary">
                                    Read More →
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 3rem; flex-wrap: wrap;">
                    <?php if ($page > 1): ?>
                        <a href="/tag?name=<?php echo urlencode($tagInfo['name']); ?>&page=<?php echo $page - 1; ?>" 
                           class="btn btn-outline">
                            ← Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="/tag?name=<?php echo urlencode($tagInfo['name']); ?>&page=<?php echo $i; ?>" 
                           class="btn <?php echo $i == $page ? 'btn-gradient' : 'btn-outline'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="/tag?name=<?php echo urlencode($tagInfo['name']); ?>&page=<?php echo $page + 1; ?>" 
                           class="btn btn-outline">
                            Next →
                        </a>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center; margin-top: 1rem; color: var(--text-light); font-size: 0.9rem;">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            
            <!-- No Results -->
            <div class="card" style="text-align: center; padding: 4rem 2rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🏷️</div>
                <h2 style="margin-bottom: 1rem;">No articles found</h2>
                <p style="color: var(--text-light); margin-bottom: 2rem;">
                    There are no published articles with this tag yet.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="/" class="btn btn-primary">Browse All Articles</a>
                    <a href="/search" class="btn btn-outline">Search Articles</a>
                </div>
            </div>
            
        <?php endif; ?>
        
    <?php else: ?>
        
        <!-- No Tag Selected - Show Popular Tags -->
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🏷️</div>
            <h2 style="margin-bottom: 1rem;">Select a Tag</h2>
            <p style="color: var(--text-light); margin-bottom: 2rem;">
                Choose a tag from the Popular Tags section in the sidebar to view related articles.
            </p>
            
            <!-- Popular Tags -->
            <?php
            $stmt = $pdo->query("SELECT t.id, t.name, t.slug, COUNT(ct.content_id) as usage_count
                                  FROM tags t
                                  INNER JOIN content_tags ct ON t.id = ct.tag_id
                                  INNER JOIN content c ON ct.content_id = c.id
                                  WHERE c.status = 'published'
                                  GROUP BY t.id, t.name, t.slug
                                  ORDER BY usage_count DESC, t.name ASC
                                  LIMIT 20");
            $popularTags = $stmt->fetchAll();
            ?>
            
            <?php if (!empty($popularTags)): ?>
                <div style="margin-top: 3rem; text-align: left;">
                    <h3 style="margin-bottom: 1.5rem; text-align: center;">Popular Tags</h3>
                    <div class="tag-cloud" style="display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center;">
                        <?php foreach ($popularTags as $tag): ?>
                            <a href="/tag?name=<?php echo urlencode($tag['name']); ?>" 
                               class="tag-item" 
                               style="display: inline-block; padding: 0.5rem 1rem; background: var(--primary-color); color: white; border-radius: 20px; text-decoration: none; font-size: 0.9rem; transition: transform 0.2s, box-shadow 0.2s;"
                               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-md)'"
                               onmouseout="this.style.transform=''; this.style.boxShadow=''">
                                <?php echo htmlspecialchars($tag['name']); ?>
                                <span style="opacity: 0.8; font-size: 0.85rem; margin-left: 0.25rem;">
                                    (<?php echo $tag['usage_count']; ?>)
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
    
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

