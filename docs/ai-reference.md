# Ava CMS — AI Reference Sheet

> Quick reference for AI assistants working with Ava CMS.
> Use this to stay aligned with decisions and avoid re-deriving.

## What Ava Is

- **Flat-file CMS** for developers
- **PHP 8.4+**, strict types, no frameworks
- **Markdown + YAML frontmatter** for content
- **Cache-first** — indexes compiled to PHP, not a static generator
- **Git is source of truth** — files are files

## What Ava Is NOT

- Not a database-backed CMS
- Not a static site generator
- Not a visual builder / WYSIWYG
- Not a media manager
- Not for non-developers

## Core Architecture

```
Request → Router → RouteMatch → Renderer → Response
             ↓
        Repository ← Cache Files ← Indexer ← Content Files
```

## Cache Files (storage/cache/)

| File | Contents |
|------|----------|
| `content_index.php` | All items by type, slug, ID, path |
| `tax_index.php` | Taxonomy terms with counts and item refs |
| `routes.php` | Compiled route map |
| `fingerprint.json` | Change detection (mtime, count, hashes) |

**Cache modes:**
- `auto` — Rebuild when fingerprint changes
- `always` — Rebuild every request (dev)
- `never` — Only via CLI (prod)

## Routing Order

1. Trailing slash redirect
2. `redirect_from` redirects
3. System routes (runtime-registered)
4. Exact routes (from cache)
5. Prefix routes
6. Taxonomy routes
7. 404

## Content Model

```yaml
---
id: 01JGMK...        # ULID (auto-generated)
title: Page Title     # Required
slug: page-title      # Required, URL-safe
status: published     # draft | published | private
date: 2024-12-28      # For dated types
excerpt: Summary      # Optional
categories:           # Taxonomy terms
  - tutorials
redirect_from:        # Old URLs (301 redirect)
  - /old-path
---

Markdown content here.
```

## Path Aliases

| Alias | Expands To |
|-------|------------|
| `@media:` | `/media/` |
| `@uploads:` | `/media/uploads/` |
| `@assets:` | `/assets/` |

Expanded during rendering (simple string replace).

## URL Types

**Hierarchical** (pages):
- `content/pages/about.md` → `/about`
- `content/pages/services/web.md` → `/services/web`

**Pattern** (posts):
- Pattern: `/blog/{slug}` → `/blog/hello-world`
- Tokens: `{slug}`, `{yyyy}`, `{mm}`, `{dd}`, `{id}`

## Key Classes

| Class | Purpose |
|-------|---------|
| `Application` | Singleton container, boot, config |
| `Content\Parser` | Markdown + YAML parsing |
| `Content\Indexer` | Scans files, builds cache |
| `Content\Repository` | Reads from cache |
| `Content\Query` | Fluent query builder |
| `Content\Item` | Content value object |
| `Routing\Router` | Request → RouteMatch |
| `Rendering\Engine` | Templates + Markdown |
| `Shortcodes\Engine` | Shortcode processing |
| `Plugins\Hooks` | WP-style filters/actions |

## Query API (WP-style)

```php
$query = (new Query($app))
    ->type('post')
    ->published()
    ->whereTax('categories', 'tutorials')
    ->orderBy('date', 'desc')
    ->perPage(10)
    ->page(1)
    ->get();
```

**Params:** `type`, `status`, `orderby`, `order`, `per_page`, `paged`, `tax_<taxonomy>=term`

## Template Variables

| Variable | Type | Description |
|----------|------|-------------|
| `$site` | array | name, url, timezone |
| `$page` | Item | Current content (singles) |
| `$query` | Query | Query object (archives) |
| `$tax` | array | Taxonomy info |
| `$request` | Request | HTTP request |
| `$ava` | TemplateHelpers | Helper methods |

## $ava Helper Methods

```php
$ava->content($page)           // Render Markdown to HTML
$ava->url('post', 'slug')      // URL for item
$ava->termUrl('tag', 'php')    // URL for term
$ava->metaTags($page)          // SEO meta tags
$ava->pagination($query)       // Pagination HTML
$ava->recent('post', 5)        // Recent items
$ava->e($string)               // HTML escape
$ava->date($date, 'F j, Y')    // Format date
$ava->config('site.name')      // Config value
```

## CLI Commands

```bash
php bin/ava status          # Show site status
php bin/ava rebuild         # Rebuild cache
php bin/ava lint            # Validate content
php bin/ava make <type> "X" # Create content of any type
```

Examples:
```bash
php bin/ava make page "About Us"
php bin/ava make post "Hello World"
```

## Non-Goals (Do Not Add)

- Database support
- WYSIWYG / visual editor
- Media upload UI
- File browser in admin
- Content editing in admin
- Complex build pipelines
- Over-engineered abstractions

## Admin (Optional)

- Disabled by default (`admin.enabled: false`)
- Read-only dashboard (stats, diagnostics)
- Safe actions only (rebuild, lint)
- **Not an editor** — web wrapper around CLI

## Shortcodes

Processed **after** Markdown.

```markdown
[year]                              # Current year
[snippet name="cta" heading="X"]    # Load PHP snippet
[button url="/x"]Text[/button]      # Paired shortcode
```

No nested shortcodes in v1.

## File Locations

| Path | Purpose |
|------|---------|
| `app/config/ava.php` | Main config |
| `app/config/content_types.php` | CPT definitions |
| `app/config/taxonomies.php` | Taxonomy definitions |
| `content/<type>/*.md` | Content files |
| `content/_taxonomies/*.yml` | Term registries |
| `themes/<name>/templates/` | Templates |
| `snippets/*.php` | Shortcode snippets |
| `storage/cache/` | Generated caches |

## Dependencies

```json
{
  "require": {
    "php": "^8.4",
    "league/commonmark": "^2.6",
    "symfony/yaml": "^7.2"
  }
}
```

That's it. No frameworks. No magic.
