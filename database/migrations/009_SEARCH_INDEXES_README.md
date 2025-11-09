# Search Performance Indexes - Migration 009

## Overview

This migration adds database indexes to significantly improve search performance on the `content` table.

## Indexes Added

### 1. FULLTEXT Index (`idx_fulltext_search`)
- **Columns**: `title`, `content`, `excerpt`
- **Purpose**: Enables fast full-text search using MySQL's `MATCH() AGAINST()` syntax
- **Benefits**:
  - **10-100x faster** than LIKE queries on large datasets
  - Better relevance scoring (MySQL calculates relevance automatically)
  - Supports boolean search operators (`+`, `-`, `*`)
  - Works with InnoDB (MySQL 5.6+)

### 2. Composite Index (`idx_status_created`)
- **Columns**: `status`, `created_at DESC`
- **Purpose**: Optimizes common query pattern: filter by status + order by date
- **Benefits**:
  - Faster queries that filter by `status = 'published'` and order by `created_at`
  - Used by homepage, category pages, and search results

### 3. Title Index (`idx_title`)
- **Columns**: `title(50)` (prefix index)
- **Purpose**: Improves LIKE queries on title when FULLTEXT is not available
- **Benefits**:
  - Faster title searches with LIKE patterns
  - Fallback when FULLTEXT index cannot be created

## Performance Impact

### Before Indexes:
- LIKE queries scan entire table (full table scan)
- Slow on large datasets (1000+ articles)
- No relevance scoring

### After Indexes:
- FULLTEXT search: **10-100x faster** on large datasets
- Composite index: **5-10x faster** for status-filtered queries
- Automatic relevance scoring with FULLTEXT

## Search Implementation

The `search.php` file automatically detects if FULLTEXT index exists:

1. **If FULLTEXT available**: Uses `MATCH() AGAINST()` for fast, relevant results
2. **If FULLTEXT not available**: Falls back to LIKE queries with relevance scoring

## Running the Migration

### Via Admin Panel:
1. Go to `/admin/migrations.php`
2. Find "Add Search Indexes" migration
3. Click "Run Migration"

### Via Command Line:
```bash
php migrate.php
```

## Requirements

- MySQL 5.6+ (for InnoDB FULLTEXT support)
- InnoDB storage engine (recommended)
- If FULLTEXT not available, prefix indexes will be created instead

## Rollback

To rollback this migration:
```bash
php migrate.php --rollback 009
```

Or via admin panel: Click "Rollback" on the migration.

## Notes

- FULLTEXT indexes require minimum word length (default: 3-4 characters)
- Short search terms (< 3 chars) automatically fall back to LIKE search
- Index creation may take time on large tables (1000+ rows)
- Indexes are automatically maintained by MySQL

