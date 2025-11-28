// ============================================
// Pixarboy CMS - Main JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // Mobile Menu Toggle
    // ============================================
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            
            // Animate hamburger icon
            const spans = this.querySelectorAll('span');
            if (navMenu.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translateY(8px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translateY(-8px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!mobileMenuToggle.contains(event.target) && !navMenu.contains(event.target)) {
                navMenu.classList.remove('active');
                const spans = mobileMenuToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
        
        // Close menu when window is resized to desktop size
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                navMenu.classList.remove('active');
                const spans = mobileMenuToggle.querySelectorAll('span');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }
    
    // ============================================
    // Dropdown Menu Toggle
    // ============================================
    const dropdownToggles = document.querySelectorAll('.nav-dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.closest('.nav-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('active');
                
                // Close other dropdowns
                document.querySelectorAll('.nav-dropdown').forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('active');
                    }
                });
            }
        });
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.nav-dropdown')) {
            document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
    
    // ============================================
    // Navbar Scroll Effect
    // ============================================
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
    
    // ============================================
    // Smooth Scroll for Anchor Links
    // ============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    
                    // Close mobile menu if open
                    if (navMenu) {
                        navMenu.classList.remove('active');
                    }
                }
            }
        });
    });
    
    // ============================================
    // Auto-dismiss Alerts
    // ============================================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Add close button
        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '×';
        closeBtn.style.cssText = 'cursor: pointer; font-size: 1.5rem; line-height: 1; margin-left: auto; padding-left: 1rem;';
        closeBtn.addEventListener('click', function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        });
        alert.appendChild(closeBtn);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            alert.style.transition = 'all 0.3s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // ============================================
    // Form Validation Enhancement
    // ============================================
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('.form-control');
        
        inputs.forEach(input => {
            // Add focus/blur effects
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'none';
            });
        });
    });
    
    // ============================================
    // Confirm Delete Actions
    // ============================================
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // ============================================
    // Table Row Actions
    // ============================================
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'none';
        });
    });
    
    // ============================================
    // Animate Elements on Scroll - DISABLED
    // ============================================
    // Animation disabled for better performance and compatibility
    
    // ============================================
    // Character Counter for Textareas
    // ============================================
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        
        // Create counter element
        const counter = document.createElement('div');
        counter.style.cssText = 'text-align: right; color: #999; font-size: 0.875rem; margin-top: 0.25rem;';
        counter.textContent = `0 / ${maxLength}`;
        textarea.parentElement.appendChild(counter);
        
        // Update counter on input
        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            counter.textContent = `${currentLength} / ${maxLength}`;
            
            if (currentLength > maxLength * 0.9) {
                counter.style.color = '#ff6b6b';
            } else {
                counter.style.color = '#999';
            }
        });
    });
    
    // ============================================
    // Loading State for Forms
    // ============================================
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.hasAttribute('data-no-loading')) {
                submitBtn.disabled = true;
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Loading...';
                submitBtn.style.opacity = '0.7';
                
                // Re-enable after 3 seconds (safety fallback)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    submitBtn.style.opacity = '1';
                }, 3000);
            }
        });
    });
    
    // ============================================
    // View Switching (Grid/List)
    // ============================================
    const viewButtons = document.querySelectorAll('.view-btn');
    const postsContainer = document.getElementById('postsContainer');
    
    if (viewButtons.length > 0 && postsContainer) {
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const view = this.getAttribute('data-view');
                
                // Update active button
                viewButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Update container classes
                if (view === 'list') {
                    postsContainer.classList.remove('grid', 'grid-2');
                    postsContainer.classList.add('list-view');
                } else {
                    postsContainer.classList.remove('list-view');
                    postsContainer.classList.add('grid', 'grid-2');
                }
                
                // Store preference in localStorage
                localStorage.setItem('postsView', view);
            });
        });
        
        // Restore saved view preference
        const savedView = localStorage.getItem('postsView');
        if (savedView) {
            const targetButton = document.querySelector(`[data-view="${savedView}"]`);
            if (targetButton) {
                targetButton.click();
            }
        }
    }
});
