# Ava CMS — AI Reference Sheet

> Quick reference for AI assistants working with Ava CMS.
> Use this to stay aligned with decisions and avoid re-deriving.

---

## Philosophy

Ava sits in the sweet spot between a static site generator and a full-blown CMS:

- **📂 Your Files, Your Rules** — Content is Markdown. Config is PHP. Git is source of truth.
- **✍️ Bring Your Own Editor** — VS Code, Obsidian, Notepad. No WYSIWYG.
- **🚀 No Database** — Fast PHP arrays loaded from a binary content index.
- **⚡ Edit Live** — Change a file, refresh, see it. No build steps.
- **🎨 Bespoke by Design** — Any content type without plugins or hacks.
- **🤖 AI Friendly** — Clean file structure makes it trivial for AI to help.

---

## What Ava Is / Is NOT

| IS | IS NOT |
|----|--------|
| Flat-file CMS for developers | Database-backed CMS |
| PHP 8.3+, strict types, no frameworks | Static site generator |
| Cache-first (indexes compiled to binary) | Visual builder / WYSIWYG |
| Git is source of truth | Media manager |
| For people who love the web | For non-developers |

---

## Requirements

| Requirement | Details |
|-------------|---------|
| **PHP** | 8.3 or later |
| **Required Extensions** | `mbstring`, `json`, `ctype` |
| **Optional Extensions** | `igbinary` (15× faster cache), `opcache`, `curl`, `gd` |

igbinary fallback: If not available, uses PHP `serialize`. Index files have format markers (`IG:`/`SZ:`) for auto-detection.

---

## Core Architecture

```
Request → Router → RouteMatch → Renderer → Response
             ↓
        Repository ← Content Index ← Indexer ← Content Files
```

**Two layers:**
1. **Content Index** — Binary serialized metadata (routes, frontmatter, taxonomies)
2. **Page Cache** — On-demand HTML caching for instant serving

---

## Storage Files (`storage/cache/`)

| File | Contents |
|------|----------|
| `content_index.bin` | All items by type, slug, ID, path |
| `tax_index.bin` | Taxonomy terms with counts and item refs |
| `routes.bin` | Compiled route map |
| `fingerprint.json` | Change detection (mtime, count, hashes) |
| `pages/*.html` | Cached HTML pages (page cache) |

**Content index modes (`content_index.mode`):**
- `auto` — Rebuild when fingerprint changes (default)
- `never` — Only via CLI (production)
- `always` — Rebuild every request (debugging)

**Binary format:** Uses igbinary if available, otherwise serialize. Files prefixed with `IG:` or `SZ:` marker.

---

## Page Cache Security

The page cache is secure by default:

| Attack Vector | Protection |
|---------------|------------|
| XSS via query strings | Query params bypass cache entirely |
| Cache poisoning via headers | Headers not used in cache key |
| Session data leakage | Admin sessions bypass cache |
| POST data injection | Only GET requests cached |
| Path traversal | Filenames are MD5 hashed |

**Key rule:** Any URL with query parameters (except UTM) is NOT cached.

---

## Routing Order

1. Trailing slash redirect
2. `redirect_from` redirects
3. System routes (runtime-registered)
4. Exact routes (from cache)
5. Prefix routes
6. Taxonomy routes
7. 404

---

## Content Model

```yaml
---
id: 01JGMK...        # ULID (auto-generated)
title: Page Title     # Required
slug: page-title      # Required, URL-safe
status: published     # draft | published | private
date: 2024-12-28      # For dated types
excerpt: Summary      # Optional
cache: true           # Override page cache setting
categories:           # Taxonomy terms
  - tutorials
redirect_from:        # Old URLs (301 redirect)
  - /old-path
---

Markdown content here.
```

---

## URL Types

**Hierarchical** (pages):
```
content/pages/about.md        → /about
content/pages/services/web.md → /services/web
```

**Pattern** (posts):
```
Pattern: /blog/{slug}      → /blog/hello-world
Pattern: /blog/{yyyy}/{mm} → /blog/2024/12
Tokens: {slug}, {yyyy}, {mm}, {dd}, {id}
```

---

## Key Classes

| Class | Purpose |
|-------|---------|
| `Application` | Singleton container, boot, config, services |
| `Content\Parser` | Markdown + YAML parsing |
| `Content\Indexer` | Scans files, builds binary cache |
| `Content\Repository` | Reads from cache, hydrates items |
| `Content\Query` | Fluent query builder (works on raw arrays) |
| `Content\Item` | Content value object |
| `Http\PageCache` | On-demand HTML page caching |
| `Http\Request` | HTTP request wrapper |
| `Http\Response` | HTTP response wrapper |
| `Routing\Router` | Request → RouteMatch |
| `Rendering\Engine` | Templates + Markdown |
| `Shortcodes\Engine` | Shortcode processing |
| `Plugins\Hooks` | WP-style filters/actions |

---

## Query API

```php
$query = (new Query($app))
    ->type('post')
    ->published()
    ->whereTax('categories', 'tutorials')
    ->orderBy('date', 'desc')
    ->perPage(10)
    ->page(1)
    ->get();

// Results
$query->items();      // array of Item objects
$query->total();      // total count
$query->hasMore();    // pagination
```

**Performance:** Query works on raw arrays from cache, only creates Item objects for final paginated results.

---

## Template Variables

| Variable | Type | Description |
|----------|------|-------------|
| `$site` | array | name, url, timezone |
| `$page` | Item | Current content (singles) |
| `$query` | Query | Query object (archives) |
| `$tax` | array | Taxonomy info |
| `$request` | Request | HTTP request |
| `$ava` | TemplateHelpers | Helper methods |

---

## $ava Helper Methods

```php
// Content
$ava->content($page)           // Render Markdown to HTML
$ava->markdown('**bold**')     // Render Markdown string
$ava->partial('header', [...]) // Include partial template

// URLs
$ava->url('post', 'slug')      // URL for item
$ava->termUrl('tag', 'php')    // URL for taxonomy term
$ava->asset('style.css')       // Theme asset with cache-busting

// Utilities
$ava->metaTags($page)          // SEO meta tags HTML
$ava->pagination($query)       // Pagination HTML
$ava->recent('post', 5)        // Recent items
$ava->e($string)               // HTML escape
$ava->date($date, 'F j, Y')    // Format date
$ava->config('site.name')      // Config value
$ava->expand('@media:img.jpg') // Expand path alias
```

---

## Path Aliases

| Alias | Expands To |
|-------|------------|
| `@media:` | `/media/` |
| `@uploads:` | `/media/uploads/` |
| `@assets:` | `/assets/` |

Expanded during rendering via simple string replace.

---

## CLI Commands

| Command | Description |
|---------|-------------|
| `status` | Site overview, PHP version, extensions, index stats |
| `rebuild` | Rebuild content index (clears page cache too) |
| `lint` | Validate content files |
| `make <type> "Title"` | Create content with scaffolding |
| `prefix <add\|remove> [type]` | Toggle date prefixes on filenames |
| `pages:stats` | Page cache statistics |
| `pages:clear [pattern]` | Clear cached pages |
| `user:add` | Create admin user |
| `user:password` | Update user password |
| `user:remove` | Remove admin user |
| `user:list` | List all users |
| `update:check` | Check for Ava updates |
| `update:apply` | Apply available update |
| `stress:generate <type> <n>` | Generate test content |
| `stress:clean <type>` | Remove test content |

---

## Shortcodes

Processed **after** Markdown.

```markdown
[year]                              # Current year
[snippet name="cta" heading="X"]    # Load PHP snippet
[button url="/x"]Text[/button]      # Paired shortcode
```

No nested shortcodes in v1.

---

## Hooks (WP-style)

```php
// Filter (modify data)
Hooks::addFilter('hook_name', fn($value) => $modified, priority: 10);
$result = Hooks::apply('hook_name', $initialValue);

// Action (side effects)
Hooks::addAction('hook_name', fn() => doSomething(), priority: 10);
Hooks::do('hook_name');
```

**Key hooks:**
- `content.before_parse` / `content.after_parse`
- `render.before` / `render.after`
- `shortcode.{name}` — Dynamic shortcode registration
- `admin.register_pages` — Add custom admin pages
- `admin.sidebar_items` — Add sidebar items

---

## File Locations

| Path | Purpose |
|------|---------|
| `app/config/ava.php` | Main config |
| `app/config/content_types.php` | Content type definitions |
| `app/config/taxonomies.php` | Taxonomy definitions |
| `app/config/users.php` | Admin users (auto-generated) |
| `app/hooks.php` | Custom hooks |
| `app/shortcodes.php` | Custom shortcodes |
| `content/<type>/*.md` | Content files |
| `content/_taxonomies/*.yml` | Term registries |
| `themes/<name>/templates/` | Theme templates |
| `themes/<name>/assets/` | Theme assets |
| `snippets/*.php` | Shortcode snippets |
| `plugins/<name>/plugin.php` | Plugin entry points |
| `storage/cache/` | Generated caches |
| `storage/logs/` | Log files |
| `public/` | Web root |

---

## Admin (Optional)

- Disabled by default (`admin.enabled: false`)
- Read-only dashboard (stats, diagnostics)
- Safe actions only (rebuild, lint, flush pages)
- **Not an editor** — web wrapper around CLI

Dashboard shows:
- Content stats (counts, drafts)
- Cache status (content + page cache)
- Recent content
- System info (PHP, extensions checklist)
- Taxonomy terms

---

## Performance Benchmarks

Tested with 10,000 content items:

| Operation | Time |
|-----------|------|
| Index rebuild | ~2.4s |
| Index load | ~45ms |
| Archive query | ~70ms |
| Page serve (cache hit) | ~0.1ms |
| CLI status | ~175ms |
| Memory usage | ~50MB |
| Index size | ~4MB |

---

## Non-Goals (Do Not Add)

- Database support
- WYSIWYG / visual editor
- Media upload UI
- File browser in admin
- Content editing in admin
- Complex build pipelines
- Over-engineered abstractions
- Heavy frameworks or dependencies

---

## Dependencies

```json
{
  "require": {
    "php": "^8.3",
    "league/commonmark": "^2.6",
    "symfony/yaml": "^7.2"
  }
}
```

Optional: `igbinary` extension for faster caching.

That's it. No frameworks. No magic. Just files.
