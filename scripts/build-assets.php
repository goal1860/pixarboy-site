<?php
/**
 * Asset Build Script
 * Minifies CSS and JS files for production
 * 
 * Usage: php scripts/build-assets.php
 */

require_once __DIR__ . '/../config/config.php';

// Simple CSS minifier
function minifyCSS($css) {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    // Remove whitespace
    $css = preg_replace('/\s+/', ' ', $css);
    // Remove spaces around brackets and operators
    $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
    // Remove last semicolon before closing brace
    $css = preg_replace('/;}/', '}', $css);
    // Remove leading/trailing whitespace
    $css = trim($css);
    return $css;
}

// Simple JS minifier
function minifyJS($js) {
    // Remove single-line comments (but not URLs)
    $js = preg_replace('/(?<!:)\/\/.*$/m', '', $js);
    // Remove multi-line comments
    $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
    // Remove whitespace around operators and brackets
    $js = preg_replace('/\s*([=+\-*\/%<>!&|,;:{}()\[\]])\s*/', '$1', $js);
    // Remove multiple spaces
    $js = preg_replace('/\s+/', ' ', $js);
    // Remove leading/trailing whitespace
    $js = trim($js);
    return $js;
}

// Create dist directories
$cssDistDir = __DIR__ . '/../assets/css/dist';
$jsDistDir = __DIR__ . '/../assets/js/dist';

if (!is_dir($cssDistDir)) {
    mkdir($cssDistDir, 0755, true);
}

if (!is_dir($jsDistDir)) {
    mkdir($jsDistDir, 0755, true);
}

echo "Building assets...\n\n";

// Minify CSS
$cssFiles = [
    'style' => __DIR__ . '/../assets/css/style.css'
];

foreach ($cssFiles as $name => $file) {
    if (!file_exists($file)) {
        echo "⚠️  Warning: CSS file not found: $file\n";
        continue;
    }
    
    $css = file_get_contents($file);
    $minified = minifyCSS($css);
    
    $outputFile = $cssDistDir . '/' . $name . '.min.css';
    file_put_contents($outputFile, $minified);
    
    $originalSize = strlen($css);
    $minifiedSize = strlen($minified);
    $savings = round((1 - $minifiedSize / $originalSize) * 100, 1);
    
    echo "✓ Minified CSS: $name.css\n";
    echo "  Original: " . number_format($originalSize) . " bytes\n";
    echo "  Minified: " . number_format($minifiedSize) . " bytes\n";
    echo "  Savings: $savings%\n\n";
}

// Minify JS
$jsFiles = [
    'main' => __DIR__ . '/../assets/js/main.js'
];

foreach ($jsFiles as $name => $file) {
    if (!file_exists($file)) {
        echo "⚠️  Warning: JS file not found: $file\n";
        continue;
    }
    
    $js = file_get_contents($file);
    $minified = minifyJS($js);
    
    $outputFile = $jsDistDir . '/' . $name . '.min.js';
    file_put_contents($outputFile, $minified);
    
    $originalSize = strlen($js);
    $minifiedSize = strlen($minified);
    $savings = round((1 - $minifiedSize / $originalSize) * 100, 1);
    
    echo "✓ Minified JS: $name.js\n";
    echo "  Original: " . number_format($originalSize) . " bytes\n";
    echo "  Minified: " . number_format($minifiedSize) . " bytes\n";
    echo "  Savings: $savings%\n\n";
}

echo "✅ Build complete!\n";
echo "Minified files are in:\n";
echo "  - assets/css/dist/\n";
echo "  - assets/js/dist/\n";

