# SEO Implementation Guide

## Overview

Your PixarBoy CMS now has a comprehensive SEO system with:
- ✅ **Meta Tags** (title, description, keywords)
- ✅ **Open Graph** (Facebook sharing)
- ✅ **Twitter Cards** (Twitter sharing)
- ✅ **Structured Data** (Google Rich Results)
- ✅ **Breadcrumbs** (Navigation schema)

---

## What's Implemented

### 1. Homepage SEO (`index.php`)
- **Meta Tags**: Optimized title, description, keywords
- **Structured Data**: WebSite schema with search action
- **Social Sharing**: Open Graph and Twitter Cards

### 2. Product Pages SEO (`product.php`)
- **Meta Tags**: Product-specific title, description from product content
- **Product Schema**: Full product structured data including:
  - Product name, description, image
  - Price and currency
  - Rating and reviews
  - Availability status
  - Affiliate link as offer URL
- **Breadcrumbs**: Category hierarchy navigation
- **Social Sharing**: Product images and descriptions for Facebook/Twitter

---

## Files Created

### `/includes/seo.php`
Main SEO helper functions:

```php
// Generate meta tags
generateSEOTags($data);

// Generate product structured data
generateProductStructuredData($product, $reviews);

// Generate website schema
generateWebsiteStructuredData();

// Generate breadcrumb schema
generateBreadcrumbStructuredData($breadcrumbs);
```

---

## How to Use

### Adding SEO to a New Page

1. **Before including header**, set SEO data:

```php
$seoData = [
    'title' => 'Your Page Title | ' . SITE_NAME,
    'description' => 'Page description here (max 160 chars)',
    'keywords' => 'keyword1, keyword2, keyword3',
    'type' => 'article', // or 'website', 'product'
    'url' => '/your-page-url',
    'image' => '/path/to/image.jpg',
];

include __DIR__ . '/includes/header.php';
```

2. **After header**, add structured data:

```php
require_once __DIR__ . '/includes/seo.php';

// For articles/posts
generateBreadcrumbStructuredData([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Category', 'url' => '/category'],
    ['name' => 'Article Title', 'url' => '/article']
]);
```

---

## SEO Data Parameters

### Meta Tags (`$seoData` array):

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `title` | string | Page title | "Product Name - Review" |
| `description` | string | Meta description (160 chars) | "Honest review of..." |
| `keywords` | string | Comma-separated keywords | "review, tech, gadget" |
| `image` | string | Social sharing image | "/assets/images/og.jpg" |
| `url` | string | Canonical URL | "/product/airpods" |
| `type` | string | Content type | "product", "article", "website" |
| `author` | string | Content author | "PixarBoy" |
| `twitter_card` | string | Card type | "summary_large_image" |
| `twitter_site` | string | Twitter handle | "@pixarboy" |

---

## Generated Meta Tags

### Standard Meta Tags
```html
<meta name="title" content="...">
<meta name="description" content="...">
<meta name="keywords" content="...">
<meta name="author" content="...">
<link rel="canonical" href="...">
```

### Open Graph (Facebook)
```html
<meta property="og:type" content="product">
<meta property="og:url" content="https://...">
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:image" content="https://...">
<meta property="og:site_name" content="PixarBoy">
```

### Twitter Cards
```html
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="https://...">
<meta property="twitter:title" content="...">
<meta property="twitter:description" content="...">
<meta property="twitter:image" content="https://...">
<meta property="twitter:site" content="@pixarboy">
```

---

## Structured Data (JSON-LD)

### Product Schema (for Product Pages)

```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "Product Name",
  "description": "Product description",
  "image": "https://...",
  "sku": "123",
  "offers": {
    "@type": "Offer",
    "url": "https://affiliate-link.com",
    "priceCurrency": "USD",
    "price": "299.99",
    "availability": "https://schema.org/InStock"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.5",
    "bestRating": "5",
    "ratingCount": "10"
  }
}
```

### Website Schema (for Homepage)

```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "PixarBoy",
  "url": "https://yoursite.com",
  "description": "Honest product reviews...",
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://yoursite.com/search?q={search_term_string}"
    }
  }
}
```

### Breadcrumb Schema

```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Home",
      "item": "https://yoursite.com/"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Category",
      "item": "https://yoursite.com/category"
    }
  ]
}
```

---

## Testing Your SEO

### 1. Meta Tags
- View page source (Ctrl+U)
- Look for `<meta>` tags in `<head>`

### 2. Open Graph (Facebook)
- Test with: https://developers.facebook.com/tools/debug/
- Paste your page URL

### 3. Twitter Cards
- Test with: https://cards-dev.twitter.com/validator
- Paste your page URL

### 4. Structured Data (Google)
- Test with: https://search.google.com/test/rich-results
- Paste your page URL
- Check for Product, Breadcrumb schemas

---

## Best Practices

### Title Tags
- **Length**: 50-60 characters
- **Format**: "Primary Keyword - Secondary | Brand"
- **Unique**: Every page should have unique title

### Meta Descriptions
- **Length**: 150-160 characters
- **Action-oriented**: Include call-to-action
- **Keywords**: Include target keywords naturally
- **Unique**: Don't duplicate across pages

### Images for Social Sharing
- **Size**: 1200x630 pixels (Facebook)
- **Format**: JPG or PNG
- **Location**: `/assets/images/`
- **Alt text**: Always provide

### Keywords
- **Number**: 5-10 relevant keywords
- **Format**: Comma-separated
- **Relevance**: Match page content

---

## Next Steps

### Additional Pages to Optimize:

1. **Category Pages** (`category.php`)
```php
$seoData = [
    'title' => $category['name'] . ' Products | ' . SITE_NAME,
    'description' => 'Browse ' . $category['name'] . ' products and reviews',
    'type' => 'website',
];
```

2. **Post/Article Pages** (`post.php`)
```php
$seoData = [
    'title' => $post['title'] . ' | ' . SITE_NAME,
    'description' => $post['excerpt'],
    'type' => 'article',
    'image' => $post['featured_image'] ?? '/assets/images/og-default.jpg',
];
```

3. **Create Default OG Image**
- Create: `/assets/images/og-default.jpg`
- Size: 1200x630px
- Include your logo and tagline

---

## Monitoring & Analytics

### Recommended Tools:
1. **Google Search Console**: Monitor search performance
2. **Google Analytics**: Track traffic sources
3. **Schema Markup Validator**: Test structured data
4. **PageSpeed Insights**: Check page speed

---

## Support

For questions or issues with SEO implementation:
- Check browser console for errors
- Validate structured data with Google's tool
- Test social sharing with Facebook/Twitter debuggers
- Review this guide for configuration examples

---

**Last Updated**: October 26, 2025
**Version**: 1.0

