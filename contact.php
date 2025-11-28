<?php
require_once __DIR__ . '/config/config.php';

$pdo = getDBConnection();
$error = '';
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name)) {
        $error = 'Name is required';
    } elseif (empty($email)) {
        $error = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (empty($message)) {
        $error = 'Message is required';
    } elseif (strlen($message) < 10) {
        $error = 'Message must be at least 10 characters long';
    } else {
        // Save to database
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, message, status, created_at, updated_at)
                VALUES (?, ?, ?, 'new', NOW(), NOW())
            ");
            $stmt->execute([$name, $email, $message]);
            
            $success = true;
            // Clear form data
            $name = '';
            $email = '';
            $message = '';
        } catch (PDOException $e) {
            $error = 'Sorry, there was an error submitting your message. Please try again later.';
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}

$pageTitle = 'Contact Us';
$seoData = [
    'title' => 'Contact Us - ' . SITE_NAME,
    'description' => 'Get in touch with ' . SITE_NAME . '. Have a question, suggestion, or product you\'d like us to review? We\'d love to hear from you!',
    'keywords' => 'contact us, get in touch, product review request, feedback',
    'type' => 'website',
    'url' => '/contact.php',
];

include __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/seo.php';
generateBreadcrumbStructuredData([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Contact Us', 'url' => '/contact.php']
]);
?>

<!-- Page Hero -->
<div class="page-hero">
    <div class="container">
        <div class="page-hero-content">
            <h1>Contact Us</h1>
            <p class="page-hero-description">
                Have a question, suggestion, or product you'd like us to review? We'd love to hear from you!
            </p>
        </div>
    </div>
</div>

<div class="container">
    <div style="max-width: 700px; margin: 0 auto;">
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 2rem;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink: 0; margin-right: 0.75rem;">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong>Thank you for your message!</strong>
                    <p style="margin: 0.5rem 0 0 0;">We've received your message and will get back to you as soon as possible.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 2rem;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink: 0; margin-right: 0.75rem;">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="" id="contactForm">
                <div class="form-group">
                    <label for="name">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        Name <span style="color: var(--danger-color);">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        placeholder="Your full name"
                        required
                        value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        Email <span style="color: var(--danger-color);">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="your.email@example.com"
                        required
                        value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="message">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 5px;">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                        </svg>
                        Message <span style="color: var(--danger-color);">*</span>
                    </label>
                    <textarea 
                        id="message" 
                        name="message" 
                        class="form-control" 
                        rows="6"
                        placeholder="Tell us what's on your mind..."
                        required
                        minlength="10"
                    ><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    <small style="color: var(--text-light); display: block; margin-top: 0.5rem;">
                        Minimum 10 characters required
                    </small>
                </div>
                
                <div style="display: flex; gap: 1rem; align-items: center; margin-top: 2rem;">
                    <button type="submit" class="btn btn-gradient">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        Send Message
                    </button>
                    <a href="/" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Additional Information -->
        <div class="card" style="margin-top: 2rem; background: var(--light-color);">
            <h3 style="margin-bottom: 1rem;">Other Ways to Reach Us</h3>
            <div style="display: grid; gap: 1rem;">
                <div style="display: flex; align-items: start; gap: 1rem;">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink: 0; color: var(--primary-color);">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    <div>
                        <strong>Email</strong>
                        <p style="margin: 0.25rem 0 0 0; color: var(--text-light);">
                            <a href="mailto:<?php echo ADMIN_EMAIL; ?>" style="color: var(--primary-color);">
                                <?php echo ADMIN_EMAIL; ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    const messageField = document.getElementById('message');
    
    // Real-time character count for message
    if (messageField) {
        const counter = document.createElement('small');
        counter.style.cssText = 'color: var(--text-light); display: block; margin-top: 0.5rem; text-align: right;';
        counter.textContent = '0 characters (minimum 10)';
        messageField.parentElement.appendChild(counter);
        
        messageField.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = length + ' character' + (length !== 1 ? 's' : '') + ' (minimum 10)';
            
            if (length < 10) {
                counter.style.color = 'var(--danger-color)';
            } else {
                counter.style.color = 'var(--text-light)';
            }
        });
    }
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const message = document.getElementById('message').value.trim();
        
        if (!name || !email || !message) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        if (message.length < 10) {
            e.preventDefault();
            alert('Message must be at least 10 characters long.');
            messageField.focus();
            return false;
        }
        
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            e.preventDefault();
            alert('Please enter a valid email address.');
            document.getElementById('email').focus();
            return false;
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

