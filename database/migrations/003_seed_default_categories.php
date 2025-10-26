<?php
/**
 * Migration: Seed Default Categories
 * 
 * Populates the categories table with default hierarchical categories
 */
class Migration_003_seed_default_categories extends Migration {
    
    public function getName() {
        return 'Seed Default Categories';
    }
    
    public function up() {
        // Only seed if categories table is empty
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categories");
        if ($stmt->fetchColumn() > 0) {
            echo "(Categories already exist, skipping seed) ";
            return;
        }
        
        // Default top-level categories
        $defaultCategories = [
            ['name' => 'Reviews', 'slug' => 'reviews', 'description' => 'Product reviews and comparisons', 'parent_id' => null, 'order' => 1],
            ['name' => 'Tech', 'slug' => 'tech', 'description' => 'Technology news and updates', 'parent_id' => null, 'order' => 2],
            ['name' => 'Guides', 'slug' => 'guides', 'description' => 'How-to guides and tutorials', 'parent_id' => null, 'order' => 3],
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT INTO categories (name, slug, description, parent_id, display_order) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $insertedIds = [];
        
        // Insert top-level categories
        foreach ($defaultCategories as $cat) {
            $stmt->execute([
                $cat['name'], 
                $cat['slug'], 
                $cat['description'], 
                $cat['parent_id'], 
                $cat['order']
            ]);
            $insertedIds[$cat['slug']] = $this->pdo->lastInsertId();
        }
        
        // Add subcategories under Reviews
        $reviewsId = $insertedIds['reviews'];
        $subCategories = [
            ['name' => 'Audio', 'slug' => 'audio', 'description' => 'Headphones, speakers, and audio equipment reviews', 'parent_id' => $reviewsId, 'order' => 1],
            ['name' => 'Mobile', 'slug' => 'mobile', 'description' => 'Smartphones and mobile device reviews', 'parent_id' => $reviewsId, 'order' => 2],
            ['name' => 'Laptops', 'slug' => 'laptops', 'description' => 'Laptop and notebook reviews', 'parent_id' => $reviewsId, 'order' => 3],
            ['name' => 'Wearables', 'slug' => 'wearables', 'description' => 'Smartwatches and wearable tech reviews', 'parent_id' => $reviewsId, 'order' => 4],
        ];
        
        foreach ($subCategories as $cat) {
            $stmt->execute([
                $cat['name'], 
                $cat['slug'], 
                $cat['description'], 
                $cat['parent_id'], 
                $cat['order']
            ]);
        }
        
        // Add subcategories under Tech
        $techId = $insertedIds['tech'];
        $techSubCategories = [
            ['name' => 'News', 'slug' => 'tech-news', 'description' => 'Latest technology news and announcements', 'parent_id' => $techId, 'order' => 1],
            ['name' => 'Industry', 'slug' => 'tech-industry', 'description' => 'Tech industry insights and analysis', 'parent_id' => $techId, 'order' => 2],
        ];
        
        foreach ($techSubCategories as $cat) {
            $stmt->execute([
                $cat['name'], 
                $cat['slug'], 
                $cat['description'], 
                $cat['parent_id'], 
                $cat['order']
            ]);
        }
        
        // Add subcategories under Guides
        $guidesId = $insertedIds['guides'];
        $guidesSubCategories = [
            ['name' => 'Tutorials', 'slug' => 'tutorials', 'description' => 'Step-by-step tutorials and how-tos', 'parent_id' => $guidesId, 'order' => 1],
            ['name' => 'Tips & Tricks', 'slug' => 'tips-tricks', 'description' => 'Helpful tips and tricks', 'parent_id' => $guidesId, 'order' => 2],
            ['name' => 'Buying Guides', 'slug' => 'buying-guides', 'description' => 'Product buying guides and recommendations', 'parent_id' => $guidesId, 'order' => 3],
        ];
        
        foreach ($guidesSubCategories as $cat) {
            $stmt->execute([
                $cat['name'], 
                $cat['slug'], 
                $cat['description'], 
                $cat['parent_id'], 
                $cat['order']
            ]);
        }
    }
    
    public function down() {
        // Remove all seeded categories
        // This will only remove the categories we added, not user-created ones
        $slugsToRemove = [
            // Top level
            'reviews', 'tech', 'guides',
            // Reviews subcategories
            'audio', 'mobile', 'laptops', 'wearables',
            // Tech subcategories
            'tech-news', 'tech-industry',
            // Guides subcategories
            'tutorials', 'tips-tricks', 'buying-guides'
        ];
        
        $placeholders = str_repeat('?,', count($slugsToRemove) - 1) . '?';
        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE slug IN ($placeholders)");
        $stmt->execute($slugsToRemove);
    }
}

