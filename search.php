<?php
require_once __DIR__ . '/config/config.php';

$pdo = getDBConnection();

// Get search query from URL parameter (q or s)
$query = trim($_GET['q'] ?? $_GET['s'] ?? '');

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$results = [];
$totalResults = 0;
$searchPerformed = false;

if (!empty($query)) {
    $searchPerformed = true;
    
    // Check if FULLTEXT index exists
    $fulltextAvailable = false;
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'content' 
            AND INDEX_NAME = 'idx_fulltext_search'
        ");
        $fulltextAvailable = $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        // FULLTEXT not available, will use LIKE
    }
    
    if ($fulltextAvailable) {
        // Use FULLTEXT search (much faster and better relevance)
        $searchTerms = explode(' ', $query);
        $searchTerms = array_filter($searchTerms, function($term) {
            return strlen(trim($term)) >= 3; // MySQL FULLTEXT minimum word length
        });
        
        if (!empty($searchTerms)) {
            $fulltextQuery = implode(' ', $searchTerms);
            
            // Count total results using FULLTEXT
            $countSQL = "
                SELECT COUNT(*) as total
                FROM content c
                WHERE c.status = 'published'
                AND MATCH(title, content, excerpt) AGAINST(? IN BOOLEAN MODE)
            ";
            
            $stmt = $pdo->prepare($countSQL);
            $stmt->execute([$fulltextQuery]);
            $totalResults = $stmt->fetch()['total'];
            
            // Search query with FULLTEXT relevance
            $searchSQL = "
                SELECT c.*, u.username as author,
                       MATCH(title, content, excerpt) AGAINST(? IN BOOLEAN MODE) as relevance
                FROM content c
                LEFT JOIN users u ON c.author_id = u.id
                WHERE c.status = 'published'
                AND MATCH(title, content, excerpt) AGAINST(? IN BOOLEAN MODE)
                ORDER BY relevance DESC, c.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $stmt = $pdo->prepare($searchSQL);
            $stmt->execute([$fulltextQuery, $fulltextQuery, $perPage, $offset]);
            $results = $stmt->fetchAll();
        } else {
            // Query too short for FULLTEXT, fall back to LIKE
            $fulltextAvailable = false;
        }
    }
    
    if (!$fulltextAvailable) {
        // Fallback to LIKE search (works without FULLTEXT index)
        $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $query);
        $searchPattern = '%' . $escapedQuery . '%';
        $titleStartsPattern = $escapedQuery . '%';
        
        // Count total results
        $countSQL = "
            SELECT COUNT(*) as total
            FROM content c
            WHERE c.status = 'published'
            AND (
                c.title LIKE ? 
                OR c.content LIKE ?
                OR c.excerpt LIKE ?
            )
        ";
        
        $stmt = $pdo->prepare($countSQL);
        $stmt->execute([$searchPattern, $searchPattern, $searchPattern]);
        $totalResults = $stmt->fetch()['total'];
        
        // Search query with relevance scoring
        $searchSQL = "
            SELECT c.*, u.username as author,
                   CASE 
                       WHEN c.title LIKE ? THEN 3
                       WHEN c.title LIKE ? THEN 2
                       WHEN c.content LIKE ? THEN 1
                       ELSE 0
                   END as relevance
            FROM content c
            LEFT JOIN users u ON c.author_id = u.id
            WHERE c.status = 'published'
            AND (
                c.title LIKE ? 
                OR c.content LIKE ?
                OR c.excerpt LIKE ?
            )
            ORDER BY relevance DESC, c.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $pdo->prepare($searchSQL);
        $stmt->execute([
            $titleStartsPattern,  // Title starts with query (highest relevance)
            $searchPattern,        // Title contains query
            $searchPattern,        // Content contains query
            $searchPattern,        // WHERE title LIKE
            $searchPattern,        // WHERE content LIKE
            $searchPattern,        // WHERE excerpt LIKE
            $perPage,
            $offset
        ]);
        $results = $stmt->fetchAll();
    }
    
    // Calculate total pages
    $totalPages = ceil($totalResults / $perPage);
}

// Highlight search terms in text
function highlightSearchTerms($text, $query) {
    if (empty($query)) return $text;
    $words = explode(' ', $query);
    foreach ($words as $word) {
        if (strlen(trim($word)) > 2) {
            $text = preg_replace('/\b(' . preg_quote(trim($word), '/') . ')\b/i', '<mark>$1</mark>', $text);
        }
    }
    return $text;
}

// Generate excerpt with search term context
function generateSearchExcerpt($content, $query, $length = 200) {
    $content = strip_tags($content);
    $queryLower = strtolower($query);
    $contentLower = strtolower($content);
    
    // Find first occurrence of query
    $pos = stripos($content, $query);
    
    if ($pos !== false) {
        // Start a bit before the match for context
        $start = max(0, $pos - 50);
        $excerpt = substr($content, $start, $length);
        
        // Add ellipsis if needed
        if ($start > 0) $excerpt = '...' . $excerpt;
        if (strlen($content) > $start + $length) $excerpt .= '...';
    } else {
        // Fallback to beginning
        $excerpt = substr($content, 0, $length);
        if (strlen($content) > $length) $excerpt .= '...';
    }
    
    return highlightSearchTerms($excerpt, $query);
}

// SEO Configuration
$pageTitle = $searchPerformed ? "Search Results for \"$query\"" : "Search";
$seoData = [
    'title' => ($searchPerformed ? "Search: $query" : 'Search') . ' - ' . SITE_NAME,
    'description' => $searchPerformed 
        ? "Search results for \"$query\". Found $totalResults article(s)." 
        : 'Search articles and reviews on ' . SITE_NAME,
    'type' => 'website',
    'url' => '/search' . ($searchPerformed ? '?q=' . urlencode($query) : '')
];

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 1200px;">
    
    <!-- Search Header -->
    <div class="card" style="margin-bottom: 2rem;">
        <div style="text-align: center; padding: 2rem;">
            <h1 style="margin-bottom: 1.5rem; font-size: 2.5rem;">
                <?php if ($searchPerformed): ?>
                    Search Results
                <?php else: ?>
                    Search Articles
                <?php endif; ?>
            </h1>
            
            <!-- Search Form -->
            <form class="search-form" action="/search" method="get" style="max-width: 600px; margin: 0 auto;">
                <div style="display: flex; gap: 0.5rem;">
                    <input 
                        type="text" 
                        class="form-control" 
                        placeholder="Search articles, reviews, and content..." 
                        name="q" 
                        value="<?php echo htmlspecialchars($query); ?>"
                        style="flex: 1; font-size: 1.1rem; padding: 1rem 1.5rem;"
                        autofocus
                    >
                    <button type="submit" class="btn btn-gradient" style="padding: 1rem 2rem;">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if ($searchPerformed): ?>
        
        <!-- Results Summary -->
        <div style="margin-bottom: 1.5rem; color: var(--text-light);">
            <?php if ($totalResults > 0): ?>
                <p>
                    Found <strong><?php echo number_format($totalResults); ?></strong> 
                    result<?php echo $totalResults != 1 ? 's' : ''; ?> for 
                    <strong>"<?php echo htmlspecialchars($query); ?>"</strong>
                </p>
            <?php else: ?>
                <p>
                    No results found for <strong>"<?php echo htmlspecialchars($query); ?>"</strong>
                </p>
            <?php endif; ?>
        </div>
        
        <?php if ($totalResults > 0): ?>
            
            <!-- Search Results -->
            <div class="search-results">
                <?php foreach ($results as $result): ?>
                    <article class="card" style="margin-bottom: 1.5rem; padding: 2rem; transition: transform 0.2s, box-shadow 0.2s;" 
                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='var(--shadow-lg)'"
                             onmouseout="this.style.transform=''; this.style.boxShadow=''">
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                            
                            <!-- Result Image (if available) -->
                            <?php if (!empty($result['hero_image_url'])): ?>
                                <div style="flex-shrink: 0; width: 200px; height: 150px; border-radius: 12px; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($result['hero_image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($result['title']); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            
                            <!-- Result Content -->
                            <div style="flex: 1; min-width: 300px;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
                                    <span class="badge badge-primary" style="font-size: 0.85rem;">
                                        <?php echo ucfirst($result['type'] ?? 'article'); ?>
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
                                        <?php echo highlightSearchTerms(htmlspecialchars($result['title']), $query); ?>
                                    </a>
                                </h2>
                                
                                <?php if ($result['excerpt']): ?>
                                    <p style="color: var(--text-light); margin-bottom: 1rem; line-height: 1.6;">
                                        <?php echo generateSearchExcerpt($result['excerpt'], $query); ?>
                                    </p>
                                <?php else: ?>
                                    <p style="color: var(--text-light); margin-bottom: 1rem; line-height: 1.6;">
                                        <?php echo generateSearchExcerpt($result['content'], $query); ?>
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
                        <a href="/search?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>" 
                           class="btn btn-outline">
                            ← Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="/search?q=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>" 
                           class="btn <?php echo $i == $page ? 'btn-gradient' : 'btn-outline'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="/search?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>" 
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
                <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
                <h2 style="margin-bottom: 1rem;">No results found</h2>
                <p style="color: var(--text-light); margin-bottom: 2rem;">
                    Try different keywords or check your spelling.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="/" class="btn btn-primary">Browse All Articles</a>
                    <a href="/search" class="btn btn-outline">New Search</a>
                </div>
            </div>
            
        <?php endif; ?>
        
    <?php else: ?>
        
        <!-- No Search Query - Show Popular/Recent Articles -->
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
            <h2 style="margin-bottom: 1rem;">Start Your Search</h2>
            <p style="color: var(--text-light); margin-bottom: 2rem;">
                Enter keywords above to search through all articles, reviews, and content.
            </p>
            
            <!-- Recent Articles -->
            <?php
            $stmt = $pdo->query("
                SELECT c.*, u.username as author 
                FROM content c 
                LEFT JOIN users u ON c.author_id = u.id 
                WHERE c.status = 'published' 
                ORDER BY c.created_at DESC 
                LIMIT 5
            ");
            $recentArticles = $stmt->fetchAll();
            ?>
            
            <?php if (!empty($recentArticles)): ?>
                <div style="margin-top: 3rem; text-align: left;">
                    <h3 style="margin-bottom: 1.5rem; text-align: center;">Recent Articles</h3>
                    <div class="grid grid-2" style="gap: 1.5rem;">
                        <?php foreach ($recentArticles as $article): ?>
                            <article class="card" style="padding: 1.5rem;">
                                <h4 style="margin-bottom: 0.5rem;">
                                    <a href="/post/<?php echo htmlspecialchars($article['slug']); ?>" 
                                       style="color: var(--primary-color); text-decoration: none;">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                </h4>
                                <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <?php echo htmlspecialchars($article['excerpt'] ?: substr(strip_tags($article['content']), 0, 100) . '...'); ?>
                                </p>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                    <span style="color: var(--text-light); font-size: 0.85rem;">
                                        📅 <?php echo date('M j, Y', strtotime($article['created_at'])); ?>
                                    </span>
                                    <a href="/post/<?php echo htmlspecialchars($article['slug']); ?>" 
                                       class="btn btn-sm btn-primary">
                                        Read →
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
    
</div>

<style>
mark {
    background: #ffeb3b;
    padding: 2px 4px;
    border-radius: 3px;
    font-weight: 600;
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

