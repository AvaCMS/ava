# Performance

Ava is designed to be fast by default. Your content lives in Markdown files, but Ava builds a pre-processed index so it never parses those files on each request.

This page explains how Ava's performance works, compares the available backends, and helps you choose the right setup for your site.

---

## How Ava's Cache Works

When you run `./ava rebuild` (or when Ava auto-detects changes), it:

1. **Scans** all your Markdown files
2. **Parses** frontmatter and extracts metadata
3. **Builds** an optimized index for fast lookups
4. **Stores** the index in your chosen backend format

When someone visits your site, Ava reads from this pre-built index. It never re-parses your content files on a request.

### The "Recent Cache" Optimization

Ava creates a special, small cache file (`recent_cache.bin`) containing just the latest 200 items of each content type.

- **Homepage & Recent Archives:** Ava loads *only* this small file (~50KB). This makes your most visited pages extremely fast (sub-millisecond) regardless of how many total posts you have.
- **Deep Archives & Search:** If a user navigates to page 50 or searches for a term, Ava loads the full content index. This is where your choice of backend matters.

---

## Backend Options

Ava supports three index configurations. The best choice depends on your content size and server setup.

### 1. Array + igbinary (Recommended)

The default and best option for most sites. It uses [igbinary](https://github.com/igbinary/igbinary), a PHP extension that serializes data ~5× faster and ~9× smaller than standard PHP serialization.

- **Pros:** Fastest for almost all operations. Compact cache files.
- **Cons:** Loads the full index into memory for deep archives or search.
- **Requirement:** `igbinary` PHP extension (standard on most quality hosts).

### 2. Array + serialize (Fallback)

If `igbinary` isn't installed, Ava falls back to standard PHP `serialize()`.

- **Pros:** Works everywhere with zero configuration.
- **Cons:** Much larger cache files. Slower to load. Higher memory usage.
- **Best for:** Local development or small sites (< 1,000 posts) where you can't install extensions.

### 3. SQLite

Stores the index in a local SQLite database file. Instead of loading the whole index into memory, Ava runs SQL queries.

- **Pros:** Constant memory usage (~2MB) regardless of site size. Instant counts.
- **Cons:** Slightly slower for complex pages. Requires `pdo_sqlite` extension.
- **Best for:** Very large sites (10,000+ posts) or memory-constrained environments.

---

## Benchmark Comparison

We tested all three backends with realistic content on a standard server.

### 1,000 Posts

| Operation | Array + igbinary | Array + serialize | SQLite |
|-----------|------------------|-------------------|--------|
| **Count all posts** | 2.5ms | 25ms | 0.6ms |
| **Homepage** (Recent) | 0.1ms | 0.3ms | 1.5ms |
| **Deep Archive** (Page 50) | 8ms | 33ms | 24ms |
| **Sort by date** | 10ms | 29ms | 22ms |
| **Sort by title** | 11ms | 33ms | 21ms |
| **Search** | 8ms | 28ms | 29ms |
| **Cache Size** | 0.6 MB | 4.4 MB | 1.2 MB |

### 10,000 Posts

| Operation | Array + igbinary | Array + serialize | SQLite |
|-----------|------------------|-------------------|--------|
| **Count all posts** | 37ms | 262ms | 1ms |
| **Homepage** (Recent) | 0.2ms | 0.3ms | 6ms |
| **Deep Archive** (Page 50) | 124ms | 350ms | 287ms |
| **Sort by date** | 119ms | 349ms | 287ms |
| **Sort by title** | 151ms | 363ms | 285ms |
| **Search** | 106ms | 322ms | 266ms |
| **Cache Size** | 5.3 MB | 43 MB | 12 MB |

### 100,000 Posts

| Operation | Array + igbinary | Array + serialize | SQLite |
|-----------|------------------|-------------------|--------|
| **Count all posts** | 448ms | 2.6s | 8ms |
| **Homepage** (Recent) | 0.2ms | 0.5ms | 51ms |
| **Deep Archive** (Page 50) | 1.7s | 4.1s | 4.2s |
| **Sort by date** | 1.7s | 4.0s | 4.3s |
| **Sort by title** | 2.1s | 4.1s | 4.3s |
| **Search** | 1.5s | 3.7s | 4.5s |
| **Cache Size** | 54 MB | 432 MB | 116 MB |

<details>
<summary><strong>Benchmark Environment & Methodology</strong></summary>

**Environment:**
- **OS:** Linux x86_64 (Ubuntu)
- **PHP:** 8.3.29 (CLI) with OPcache
- **Hardware:** Standard cloud instance

**Methodology:**
Benchmarks were run using the built-in Ava CLI tools.
1. Content generated via `./ava stress:generate post <count>`
2. Benchmarks run via `./ava benchmark --compare`
3. Each test iterated 5 times, average result shown.
</details>

### Analysis

- **Homepage:** Array backends are instant because they use the "Recent Cache" optimization. SQLite is slightly slower as it must query the database.
- **Counts:** SQLite is the clear winner for counting items (e.g. `{{ count('post') }}`).
- **Deep Archives:** Array + igbinary remains faster than SQLite even at 100k posts, *provided you have enough RAM* to load the 54MB index.
- **Memory:** SQLite wins on memory. It stays at ~2MB usage, while Array backends load the full index size into RAM.

### Why isn't SQLite faster?

You might expect a database to be faster than a file-based array, but PHP's arrays are incredibly optimized in-memory structures.

- **Array Backend:** Loads the *entire* dataset into RAM. Sorting and filtering happen instantly in memory.
- **SQLite:** Must read from disk (or OS cache) and parse records.

**The Catch:** The Array backend consumes memory *per concurrent request*.
- 100k posts = ~54MB RAM.
- 10 concurrent visitors = 540MB RAM.
- 100 concurrent visitors = 5.4GB RAM.

**SQLite** uses constant memory (~2MB). It won't be as fast for a single user, but it won't crash your server under load. A significant optimisation for high-traffic sites, sites that don't make extensive use of page caching, or memory-constrained servers.

---

## Which Should You Choose?

### Small to Medium Sites (< 10,000 posts, low concurrent traffic)
**Use Array + igbinary.** It's the fastest option. If your host doesn't have igbinary, ask them to enable it, or use the standard Array backend (it's still fine for smaller sites).

### Large Sites (10,000 - 50,000 posts, moderate concurrent traffic)
**Use Array + igbinary** if you have plenty of RAM (e.g. 512MB+) and low traffic with few concurrent users.
**Use SQLite** if you are on a constrained server (e.g. 128MB RAM limit) or have high traffic with many concurrent users or searches.

### Very Large Sites (50,000+ posts, high concurrent traffic)
**Use SQLite + Page Caching.**

1.  **Use SQLite** to ensure your server memory stays stable (avoiding Out-Of-Memory crashes).
2.  **Enable Page Caching** to mask the slightly slower database queries.

With Page Caching, the backend performance only matters for the *first* visitor to a page. Everyone else gets a static HTML file served in ~1ms, bypassing the database entirely.

Without page caching, while Array + igbinary can technically be faster, loading a 50MB+ file on every request (for deep pages) will cause memory pressure on your server. SQLite's constant memory usage is safer and more stable in this case.

---

## Configuration

Configure your backend in `app/config/ava.php`:

```php
'content_index' => [
    // 'auto'   - Rebuild when content changes (recommended)
    // 'never'  - Only rebuild via CLI (production)
    'mode' => 'auto',

    // 'array'  - Default, fastest for most sites
    // 'sqlite' - For large sites / low memory
    'backend' => 'array',

    // Enable igbinary optimization (highly recommended)
    'use_igbinary' => true,
],
```

---

## Page Caching

For the ultimate performance, enable Ava's full page cache. This saves a static HTML copy of every page after the first visit.

- **First visit:** ~50ms (renders template)
- **Subsequent visits:** ~1ms (serves static file)

```php
// app/config/ava.php
'page_cache' => [
    'enabled' => true,
    'ttl' => null, // Cache until next rebuild
],
```

This bypasses the content index entirely for most visitors.

### Per-Page Control

You can override the global setting for individual pages using the `cache` field in your frontmatter:

```yaml
---
title: Dynamic Page
cache: false
---
```

- `cache: false` — Disables caching for this page, even if globally enabled. Useful for pages with random content or dynamic logic.
- `cache: true` — Forces caching for this page (if global caching is enabled), even if it would normally be excluded (e.g. by an exclude pattern).

---

## Running Your Own Benchmarks

You can test performance on your own server using the built-in CLI command.

1. **Generate test content:**
   ```bash
   ./ava stress:generate post 5000
   ```

2. **Run the benchmark:**
   ```bash
   ./ava benchmark --compare
   ```

3. **Clean up:**
   ```bash
   ./ava stress:clean post
   ```
