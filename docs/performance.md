# Performance

Ava is built for speed. Most sites load in under 10ms, and cached pages serve in under 1ms—faster than most static site generators.

## At a Glance

| Posts | Archive Page | Single Post | Cached Page |
|-------|--------------|-------------|-------------|
| 100 | 3ms | 5ms | <1ms |
| 1,000 | 3ms | 8ms | <1ms |
| 10,000 | 3ms | 15ms | <1ms |

**Archive pages stay fast at any scale** thanks to smart caching. Cached pages are served as static HTML with zero PHP processing.

---

## How Ava Stays Fast

Ava uses two caching layers:

| Layer | What it does | Speed |
|-------|--------------|-------|
| **Content Index** | Pre-parses all Markdown into a binary index | Avoids file parsing on every request |
| **Page Cache** | Stores rendered HTML | Serves pages in <1ms |

### Content Index

When you run `./ava rebuild`, Ava scans all your Markdown files and builds a fast binary index. On each request, Ava reads from this index instead of parsing files.

The index uses **tiered caching** to load only what's needed:

| Request Type | What Loads | Memory |
|--------------|------------|--------|
| Homepage / Archives | Recent cache (~51KB) | ~2MB |
| Single post | Slug lookup (~900KB for 10k posts) | ~10MB |
| Complex queries | Full index (~4.5MB for 10k posts) | ~35MB |

This means archive pages stay fast regardless of how much content you have.

### Page Cache

After a page is rendered once, Ava saves the HTML to disk. Subsequent requests serve this static file directly—no template rendering, no database queries, no PHP processing.

```php
// In app/config/ava.php
'page_cache' => [
    'enabled' => true,
    'ttl' => null,  // Cache until rebuild
],
```

The page cache is cleared automatically when you rebuild the content index.

---

## Scaling to 10,000+ Posts

The default setup handles most sites beautifully. But if you're building something big—10,000+ posts—you have options.

### The Challenge

With the default `array` backend, the full content index grows with your content:

| Posts | Index Size | Memory to Load |
|-------|------------|----------------|
| 1,000 | 450KB | 4MB |
| 10,000 | 4.5MB | 35MB |
| 100,000 | 45MB | 323MB |

Archive pages and single posts stay fast (they use the tiered cache). But operations like `->count()`, deep pagination, or complex filters need the full index—and at 100k posts, that's 323MB of memory per request.

### The Solution: SQLite Backend

For large sites, Ava offers an optional SQLite backend. Instead of loading the entire index into memory, queries run against a SQLite database file.

| Backend | How it works | Best for |
|---------|--------------|----------|
| `array` (default) | Loads index into PHP memory | Most sites (<10k posts) |
| `sqlite` | Queries a SQLite database file | Large sites (10k+ posts) |

**SQLite uses constant memory** regardless of content size—about 14MB whether you have 1,000 or 100,000 posts.

### Benchmark Comparison

Real benchmarks comparing both backends (tested on Hetzner Cloud VPS, 4 vCPU, 8GB RAM, PHP 8.3):

| Posts | Operation | Array | SQLite | Winner |
|-------|-----------|-------|--------|--------|
| **1,000** | Count all | 2.5ms | 0.6ms | SQLite |
| | Get by slug | 0.5ms | 0.5ms | Tie |
| | List recent 10 | 0.3ms | 1.4ms | Array |
| | Search | 9ms | 18ms | Array |
| **10,000** | Count all | 54ms | 1.8ms | **SQLite** |
| | Get by slug | 12ms | 0.6ms | **SQLite** |
| | List recent 10 | 0.3ms | 9ms | Array |
| | Search | 147ms | 340ms | Array |
| **50,000** | Count all | 209ms | 5ms | **SQLite** |
| | Get by slug | 59ms | 0.5ms | **SQLite** |
| | List recent 10 | 0.1ms | 27ms | Array |
| | Search | 663ms | 1646ms | Array |
| **100,000** | Count all | 418ms | 9ms | **SQLite** |
| | Get by slug | 91ms | 0.6ms | **SQLite** |
| | List recent 10 | 0.2ms | 73ms | Array |
| | Search | 1461ms | 3622ms | Array |

**Key insights:**

- **SQLite dominates** for counts, lookups, and filtered queries at 10k+ posts
- **Array wins for archives** thanks to the tiered recent cache (~51KB, instant load)
- **Search is faster on Array** because it loads everything into memory once
- **SQLite memory is constant** (~0MB overhead) while Array scales with content
- At 100k posts, Array uses ~18MB per full index load; SQLite stays near zero

### Which Should You Use?

| Your site | Recommendation |
|-----------|----------------|
| Under 5,000 posts | **Array** (default) — fast and simple |
| 5,000–10,000 posts | Either works — Array is simpler |
| 10,000+ posts | **SQLite** — faster queries, constant memory |
| 100,000+ posts | **SQLite** (required) — Array may exceed memory limits |

**Most sites should stick with the default.** Only switch to SQLite if you're hitting performance issues with large content.

### Enabling SQLite

1. Check that `pdo_sqlite` is installed:
   ```bash
   php -m | grep -i sqlite
   ```

2. Update your config:
   ```php
   // app/config/ava.php
   'content_index' => [
       'mode' => 'auto',
       'backend' => 'sqlite',
   ],
   ```

3. Rebuild the index:
   ```bash
   ./ava rebuild
   ```

That's it. Your theme code doesn't change—the Query API works identically with both backends.

---

## Optimizing Further

### Install igbinary

The `igbinary` extension makes the Array backend significantly faster:

| Metric | With igbinary | Without | Improvement |
|--------|---------------|---------|-------------|
| Index size (10k posts) | 4.5MB | 42MB | **9× smaller** |
| Load time | 61ms | 422ms | **7× faster** |
| Memory usage | 35MB | 229MB | **6.5× less** |

Install via your package manager:
```bash
# Ubuntu/Debian
apt install php-igbinary

# macOS
pecl install igbinary
```

Ava auto-detects igbinary and uses it automatically.

### Production Settings

For production sites, use these settings:

```php
'content_index' => [
    'mode' => 'never',  // Only rebuild via CLI
],

'page_cache' => [
    'enabled' => true,
    'ttl' => null,      // Cache forever until rebuild
],
```

Then rebuild after each deploy:
```bash
./ava rebuild
```

---

## Page Cache Details

### What Gets Cached

- ✅ Single pages and posts
- ✅ Archive pages (paginated)
- ✅ Taxonomy pages
- ❌ Admin pages
- ❌ URLs with query parameters
- ❌ Logged-in admin users
- ❌ POST requests

### Per-Page Override

Disable caching for specific pages:

```yaml
---
title: Contact Form
cache: false
---
```

### CLI Commands

```bash
./ava status           # Shows cache status
./ava pages:stats      # Detailed cache statistics
./ava pages:clear      # Clear all cached pages
./ava pages:clear /blog/*  # Clear matching pattern
```

### Cache Invalidation

The page cache is cleared when:
- You run `./ava rebuild`
- Content changes (with `mode: 'auto'`)
- You click "Rebuild Now" or "Flush Pages" in admin

---

## Content Index Configuration

### Rebuild Mode

```php
'content_index' => [
    'mode' => 'auto',
],
```

| Mode | Behavior | Best for |
|------|----------|----------|
| `auto` | Rebuilds when files change | Development |
| `never` | Only via `./ava rebuild` | Production |
| `always` | Every request | Debugging only |

### Backend

```php
'content_index' => [
    'backend' => 'array',  // or 'sqlite'
],
```

| Backend | Storage | Best for |
|---------|---------|----------|
| `array` | Binary PHP files | Most sites (default) |
| `sqlite` | SQLite database | Large sites (10k+) |

---

## Running Your Own Benchmarks

Test performance with generated content:

```bash
# Generate 10,000 test posts
./ava stress:generate post 10000

# Compare backends
./ava stress:benchmark

# Clean up
./ava stress:clean post
```

### Full Benchmark Reference

| Posts | Rebuild | Index Size | SQLite Size | Array Memory |
|-------|---------|------------|-------------|--------------|
| 1,000 | 244ms | 594KB | 1.2MB | 2MB |
| 10,000 | 2.6s | 5.2MB | 11.4MB | 6MB |
| 50,000 | 14s | 26.9MB | 57.8MB | 10MB |
| 100,000 | 28s | 54MB | 116MB | 18MB |

**Cache file sizes include:** `content_index.bin`, `slug_lookup.bin`, and `recent_cache.bin`.

*Tested on Hetzner Cloud VPS (4 vCPU, 8GB RAM), PHP 8.3, igbinary enabled.*

---

## Troubleshooting

### Pages not being cached

1. Run `./ava status` to check if caching is enabled
2. Log out of admin (admin users bypass cache)
3. Check if URL has query parameters
4. Check `exclude` patterns in config

### Content changes not appearing

1. If `mode: 'never'`, run `./ava rebuild`
2. Delete `storage/cache/fingerprint.json` to force rebuild
3. Run `./ava rebuild`

### SQLite errors

If you set `backend: 'sqlite'` but get errors:

1. Check if `pdo_sqlite` is installed: `php -m | grep -i sqlite`
2. Install it: `apt install php-sqlite3` (Linux) or `pecl install pdo_sqlite` (macOS)
3. Or switch back to `backend: 'array'`

### Out of memory errors

If you're hitting memory limits with large content:

1. Switch to `backend: 'sqlite'` (constant memory usage)
2. Or install `igbinary` to reduce Array backend memory
3. Or increase PHP's `memory_limit`
