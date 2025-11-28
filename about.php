<?php
require_once __DIR__ . '/config/config.php';

$pdo = getDBConnection();

// Get some stats for the about page
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
$totalProducts = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM content WHERE status = 'published'");
$totalArticles = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$totalCategories = $stmt->fetchColumn();

$pageTitle = 'About Us';
$seoData = [
    'title' => 'About Us - ' . SITE_NAME,
    'description' => 'Learn about ' . SITE_NAME . '. We provide honest, in-depth product reviews and expert buying guides to help you make informed purchasing decisions.',
    'keywords' => 'about us, product reviews, tech reviews, buying guides, honest reviews',
    'type' => 'website',
    'url' => '/about.php',
];

include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/seo.php';
generateBreadcrumbStructuredData([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'About Us', 'url' => '/about.php']
]);
?>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container">
        <div class="page-hero-content">
            <h1>About <?php echo SITE_NAME; ?></h1>
            <p class="page-hero-description">
                Your trusted source for honest product reviews and expert buying guides
            </p>
        </div>
    </div>
</div>

<div class="container">
    <div class="post-content" style="max-width: 800px; margin: 0 auto;">
        
        <!-- Mission Section -->
        <div class="card" style="margin-bottom: 3rem;">
            <h2 style="margin-bottom: 1.5rem;">Our Mission</h2>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--text-color);">
                At <?php echo SITE_NAME; ?>, we believe that making informed purchasing decisions shouldn't be a guessing game. Our mission is to provide honest, in-depth product reviews and expert buying guides that help you find the products that truly fit your needs and budget.
            </p>
            <p style="font-size: 1.1rem; line-height: 1.8; color: var(--text-color);">
                We test products thoroughly, research extensively, and present our findings with complete transparency. If a product has flaws, we'll tell you about them. If it exceeds expectations, you'll know that too.
            </p>
        </div>

        <!-- What We Do Section -->
        <div class="card" style="margin-bottom: 3rem;">
            <h2 style="margin-bottom: 1.5rem;">What We Do</h2>
            <div style="display: grid; gap: 2rem;">
                <div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🔍</span> In-Depth Reviews
                    </h3>
                    <p style="color: var(--text-light); line-height: 1.8;">
                        We spend time with products, testing them in real-world scenarios. Our reviews cover everything from build quality and performance to value for money and long-term reliability.
                    </p>
                </div>
                
                <div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>📚</span> Expert Buying Guides
                    </h3>
                    <p style="color: var(--text-light); line-height: 1.8;">
                        Beyond individual product reviews, we create comprehensive buying guides that help you understand what to look for, compare options, and make the best choice for your specific needs.
                    </p>
                </div>
                
                <div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <span>💡</span> Honest Recommendations
                    </h3>
                    <p style="color: var(--text-light); line-height: 1.8;">
                        We're not here to sell you anything. Our recommendations are based on real testing and honest assessment. We'll tell you when a product is worth your money and when it's not.
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="card" style="margin-bottom: 3rem; background: var(--gradient-primary); color: white; padding: 2.5rem;">
            <h2 style="color: white; margin-bottom: 2rem; text-align: center;">Our Impact</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; text-align: center;">
                <div>
                    <div style="font-size: 3rem; font-weight: 800; margin-bottom: 0.5rem;">
                        <?php echo number_format($totalProducts); ?>+
                    </div>
                    <div style="font-size: 1.1rem; opacity: 0.9;">Products Reviewed</div>
                </div>
                <div>
                    <div style="font-size: 3rem; font-weight: 800; margin-bottom: 0.5rem;">
                        <?php echo number_format($totalArticles); ?>+
                    </div>
                    <div style="font-size: 1.1rem; opacity: 0.9;">Articles Published</div>
                </div>
                <div>
                    <div style="font-size: 3rem; font-weight: 800; margin-bottom: 0.5rem;">
                        <?php echo number_format($totalCategories); ?>+
                    </div>
                    <div style="font-size: 1.1rem; opacity: 0.9;">Product Categories</div>
                </div>
            </div>
        </div>

        <!-- Our Values Section -->
        <div class="card" style="margin-bottom: 3rem;">
            <h2 style="margin-bottom: 1.5rem;">Our Values</h2>
            <div style="display: grid; gap: 1.5rem;">
                <div style="display: flex; gap: 1rem; align-items: start;">
                    <div style="font-size: 2rem; flex-shrink: 0;">✅</div>
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Honesty & Transparency</h3>
                        <p style="color: var(--text-light); line-height: 1.8;">
                            We believe in complete honesty. Every review reflects our genuine experience with the product, both the good and the bad.
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; align-items: start;">
                    <div style="font-size: 2rem; flex-shrink: 0;">🎯</div>
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">User-Focused</h3>
                        <p style="color: var(--text-light); line-height: 1.8;">
                            Your needs come first. We write reviews and guides with real users in mind, helping you make decisions that matter to you.
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; align-items: start;">
                    <div style="font-size: 2rem; flex-shrink: 0;">🔬</div>
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Thorough Testing</h3>
                        <p style="color: var(--text-light); line-height: 1.8;">
                            We don't just read specs—we test products in real-world conditions to give you insights you can't get from a product page.
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; align-items: start;">
                    <div style="font-size: 2rem; flex-shrink: 0;">🤝</div>
                    <div>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Independent & Unbiased</h3>
                        <p style="color: var(--text-light); line-height: 1.8;">
                            While we may use affiliate links, our reviews are never influenced by commissions. Our opinions are our own, based on real testing and research.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- How We Review Section -->
        <div class="card" style="margin-bottom: 3rem;">
            <h2 style="margin-bottom: 1.5rem;">How We Review Products</h2>
            <p style="color: var(--text-light); line-height: 1.8; margin-bottom: 1.5rem;">
                Our review process is designed to give you the most accurate and useful information possible:
            </p>
            <ol style="padding-left: 1.5rem; color: var(--text-light); line-height: 1.8;">
                <li style="margin-bottom: 1rem;">
                    <strong style="color: var(--text-color);">Real-World Testing:</strong> We use products in everyday scenarios, not just controlled environments. This gives us insights into how products perform when you actually use them.
                </li>
                <li style="margin-bottom: 1rem;">
                    <strong style="color: var(--text-color);">Extended Use:</strong> Many of our reviews come from products we've used for weeks or months, not just initial impressions. This helps us identify long-term reliability and value.
                </li>
                <li style="margin-bottom: 1rem;">
                    <strong style="color: var(--text-color);">Comparative Analysis:</strong> We compare products against competitors in the same category, helping you understand how they stack up.
                </li>
                <li style="margin-bottom: 1rem;">
                    <strong style="color: var(--text-color);">Value Assessment:</strong> We evaluate whether a product offers good value for money, considering its price point and what you get for it.
                </li>
                <li>
                    <strong style="color: var(--text-color);">Honest Reporting:</strong> We report our findings honestly, highlighting both strengths and weaknesses so you can make an informed decision.
                </li>
            </ol>
        </div>

        <!-- Contact Section -->
        <div class="card" style="margin-bottom: 3rem;">
            <h2 style="margin-bottom: 1.5rem;">Get in Touch</h2>
            <p style="color: var(--text-light); line-height: 1.8; margin-bottom: 1.5rem;">
                Have a question, suggestion, or product you'd like us to review? We'd love to hear from you!
            </p>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="mailto:<?php echo ADMIN_EMAIL; ?>" 
                   class="btn btn-primary" 
                   style="display: inline-flex; align-items: center; gap: 0.5rem; justify-content: center; color: white !important;">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" style="color: white;">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    Email Us
                </a>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="card" style="background: var(--light-color); text-align: center; padding: 3rem 2rem;">
            <h2 style="margin-bottom: 1rem;">Ready to Find Your Next Product?</h2>
            <p style="color: var(--text-light); margin-bottom: 2rem; font-size: 1.1rem;">
                Browse our reviews and buying guides to discover products that are right for you.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="/categories.php" class="btn btn-gradient">
                    Browse Categories
                </a>
                <a href="/" class="btn btn-secondary">
                    View Latest Reviews
                </a>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

