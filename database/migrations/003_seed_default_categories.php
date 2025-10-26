<?php
/**
 * Migration: Seed Default Product Categories
 * 
 * Populates the categories table with default hierarchical product categories
 * for an affiliate review site
 */
class Migration_003_seed_default_categories extends Migration {
    
    public function getName() {
        return 'Seed Default Product Categories';
    }
    
    public function up() {
        // Only seed if categories table is empty
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM categories");
        if ($stmt->fetchColumn() > 0) {
            echo "(Categories already exist, skipping seed) ";
            return;
        }
        
        // Default top-level product categories
        $defaultCategories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronic devices and gadgets', 'parent_id' => null, 'order' => 1],
            ['name' => 'Computers', 'slug' => 'computers', 'description' => 'Computers and accessories', 'parent_id' => null, 'order' => 2],
            ['name' => 'Smart Home', 'slug' => 'smart-home', 'description' => 'Smart home devices and automation', 'parent_id' => null, 'order' => 3],
            ['name' => 'Gaming', 'slug' => 'gaming', 'description' => 'Gaming consoles, accessories, and peripherals', 'parent_id' => null, 'order' => 4],
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
        
        // Electronics subcategories
        $electronicsId = $insertedIds['electronics'];
        $electronicsSubCategories = [
            ['name' => 'Audio', 'slug' => 'audio', 'description' => 'Headphones, earbuds, speakers, and audio equipment', 'parent_id' => $electronicsId, 'order' => 1],
            ['name' => 'Mobile Devices', 'slug' => 'mobile-devices', 'description' => 'Smartphones, tablets, and mobile accessories', 'parent_id' => $electronicsId, 'order' => 2],
            ['name' => 'Wearables', 'slug' => 'wearables', 'description' => 'Smartwatches, fitness trackers, and wearable tech', 'parent_id' => $electronicsId, 'order' => 3],
            ['name' => 'Cameras', 'slug' => 'cameras', 'description' => 'Digital cameras, action cams, and photography gear', 'parent_id' => $electronicsId, 'order' => 4],
        ];
        
        foreach ($electronicsSubCategories as $cat) {
            $stmt->execute([
                $cat['name'], 
                $cat['slug'], 
                $cat['description'], 
                $cat['parent_id'], 
                $cat['order']
            ]);
        }
        
        // Computers subcategories
        $computersId = $insertedIds['computers'];
        $computersSubCategories = [
            ['name' => 'Laptops', 'slug' => 'laptops', 'description' => 'Laptops, notebooks, and portable computers', 'parent_id' => $computersId, 'order' => 1],
            ['name' => 'Desktops', 'slug' => 'desktops', 'description' => 'Desktop computers and workstations', 'parent_id' => $computersId, 'order' => 2],
            ['name' => 'Monitors', 'slug' => 'monitors', 'description' => 'Computer monitors and displays', 'parent_id' => $computersId, 'order' => 3],
            ['name' => 'Accessories', 'slug' => 'computer-accessories', 'description' => 'Keyboards, mice, and computer peripherals', 'parent_id' => $computersId, 'order' => 4],
        ];
        
        foreach ($computersSubCategories as $cat) {
            $stmt->execute([
                $cat['name'], 
                $cat['slug'], 
                $cat['description'], 
                $cat['parent_id'], 
                $cat['order']
            ]);
        }
        
        // Smart Home subcategories
        $smartHomeId = $insertedIds['smart-home'];
        $smartHomeSubCategories = [
            ['name' => 'Smart Speakers', 'slug' => 'smart-speakers', 'description' => 'Smart speakers and voice assistants', 'parent_id' => $smartHomeId, 'order' => 1],
            ['name' => 'Security', 'slug' => 'home-security', 'description' => 'Smart locks, cameras, and security systems', 'parent_id' => $smartHomeId, 'order' => 2],
            ['name' => 'Lighting', 'slug' => 'smart-lighting', 'description' => 'Smart bulbs and lighting systems', 'parent_id' => $smartHomeId, 'order' => 3],
        ];
        
        foreach ($smartHomeSubCategories as $cat) {
            $stmt->execute([
                $cat['name'], 
                $cat['slug'], 
                $cat['description'], 
                $cat['parent_id'], 
                $cat['order']
            ]);
        }
        
        // Gaming subcategories
        $gamingId = $insertedIds['gaming'];
        $gamingSubCategories = [
            ['name' => 'Consoles', 'slug' => 'gaming-consoles', 'description' => 'PlayStation, Xbox, Nintendo, and gaming consoles', 'parent_id' => $gamingId, 'order' => 1],
            ['name' => 'PC Gaming', 'slug' => 'pc-gaming', 'description' => 'Gaming PCs, graphics cards, and components', 'parent_id' => $gamingId, 'order' => 2],
            ['name' => 'Controllers', 'slug' => 'gaming-controllers', 'description' => 'Gaming controllers and input devices', 'parent_id' => $gamingId, 'order' => 3],
            ['name' => 'Headsets', 'slug' => 'gaming-headsets', 'description' => 'Gaming headsets and audio', 'parent_id' => $gamingId, 'order' => 4],
        ];
        
        foreach ($gamingSubCategories as $cat) {
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
            'electronics', 'computers', 'smart-home', 'gaming',
            // Electronics subcategories
            'audio', 'mobile-devices', 'wearables', 'cameras',
            // Computers subcategories
            'laptops', 'desktops', 'monitors', 'computer-accessories',
            // Smart Home subcategories
            'smart-speakers', 'home-security', 'smart-lighting',
            // Gaming subcategories
            'gaming-consoles', 'pc-gaming', 'gaming-controllers', 'gaming-headsets'
        ];
        
        $placeholders = str_repeat('?,', count($slugsToRemove) - 1) . '?';
        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE slug IN ($placeholders)");
        $stmt->execute($slugsToRemove);
    }
}

