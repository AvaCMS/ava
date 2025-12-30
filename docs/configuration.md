# Configuration

Ava's configuration is simple and transparent. All settings live in `app/config/` as plain PHP files.

## Why PHP Configs?

We use PHP arrays instead of YAML or JSON because:
1. **It's Readable:** You can add comments to explain *why* you changed a setting.
2. **It's Powerful:** You can use constants, logic, or helper functions right in your config.
3. **It's Standard:** No special parsers or hidden `.env` files to debug.

## The Config Files

| File | What it controls |
|------|------------------|
| `ava.php` | Main site settings (name, URL, cache). |
| `content_types.php` | Defines your content (Pages, Posts, etc.). |
| `taxonomies.php` | Defines how you group content (Categories, Tags). |
| `users.php` | Admin users (generated automatically). |

## Main Settings (`ava.php`)

This is where you set up your site's identity.

```php
return [
    'site' => [
        'name' => 'My Awesome Site',
        'base_url' => 'https://example.com',
        'timezone' => 'Europe/London',
        'locale' => 'en_GB',
    ],
    // ...
];
```

### Key Options

| Option | Description |
|--------|-------------|
| `site.name` | Your site's display name (used in templates, feeds, etc.) |
| `site.base_url` | Full URL where your site lives (no trailing slash). Used for sitemaps and absolute links. |
| `site.timezone` | Timezone for dates. Use a [PHP timezone identifier](https://www.php.net/manual/en/timezones.php). |
| `site.locale` | Locale for formatting (e.g., `en_GB`, `en_US`, `de_DE`). |
| `paths` | Where Ava finds content, themes, plugins. Usually no need to change. |

#### Timezone Examples

| Region | Timezone Identifier |
|--------|---------------------|
| UTC (default) | `UTC` |
| London | `Europe/London` |
| New York | `America/New_York` |
| Los Angeles | `America/Los_Angeles` |
| Tokyo | `Asia/Tokyo` |
| Sydney | `Australia/Sydney` |
| Paris | `Europe/Paris` |
| Berlin | `Europe/Berlin` |

See the [full list of PHP timezones](https://www.php.net/manual/en/timezones.php) for all options.

### Content Index

The content index is a binary snapshot of all your content metadata—used to avoid parsing Markdown on every request.

```php
'content_index' => [
    'mode' => 'auto',
    'backend' => 'array',
],
```

| Option | Values | Description |
|--------|--------|-------------|
| `mode` | `auto`, `never`, `always` | When to rebuild the index |
| `backend` | `array`, `sqlite` | Storage backend for the index |

**Mode options:**

| Mode | Behavior |
|------|----------|
| `auto` | Rebuilds when content files change. Best for development. |
| `never` | Only rebuilds via `./ava rebuild`. Best for production. |
| `always` | Rebuilds every request. For debugging only. |

**Backend options:**

| Backend | Behavior |
|---------|----------|
| `array` | Binary serialized PHP arrays. Works everywhere. **This is the default.** |
| `sqlite` | SQLite database file. Opt-in for large sites (10k+ items). Requires `pdo_sqlite`. |

<div class="beginner-box">

**Which backend should I use?**

Stick with `array` — it works great for most sites. Only switch to `sqlite` if you have 10,000+ posts and notice slow queries or memory issues.

See [Performance - Scaling](performance.md#scaling-to-10000-posts) for detailed benchmarks.

</div>

### Page Cache

The page cache stores rendered HTML for instant serving.

```php
'page_cache' => [
    'enabled' => true,
    'ttl' => null,
    'exclude' => [
        '/api/*',
        '/preview/*',
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `true` | Enable HTML page caching |
| `ttl` | int\|null | `null` | Lifetime in seconds. `null` = until rebuild |
| `exclude` | array | `[]` | URL patterns to never cache |

**How it works:**
- First visit: Page rendered and saved to `storage/cache/pages/`
- Subsequent visits: Cached HTML served (~0.1ms vs ~30ms)
- On `./ava rebuild`: Page cache is cleared
- On content change (with `content_index.mode = 'auto'`): Page cache is cleared
- Logged-in admin users bypass the cache

**Per-page override:**

```yaml
---
title: My Dynamic Page
cache: false
---
```

**CLI commands:**
- `./ava pages:stats` - View cache statistics
- `./ava pages:clear` - Clear all cached pages
- `./ava pages:clear /blog/*` - Clear matching pattern

For details, see [Performance](performance.md).

### Routing

```php
'routing' => [
    'trailing_slash' => false,
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `trailing_slash` | bool | `false` | If `true`, URLs end with `/`. Mismatched requests get 301 redirected |

### Content Parsing

```php
'content' => [
    'frontmatter' => [
        'format' => 'yaml',     // Only YAML supported currently
    ],
    'markdown' => [
        'allow_html' => true,   // Allow raw HTML in markdown
    ],
    'id' => [
        'type' => 'ulid',       // ulid or uuid7
    ],
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `frontmatter.format` | string | `'yaml'` | Frontmatter parser (only YAML supported) |
| `markdown.allow_html` | bool | `true` | Allow raw HTML in markdown content |
| `id.type` | string | `'ulid'` | ID format for new content: `'ulid'` (sortable) or `'uuid7'` |

### Security

```php
'security' => [
    'shortcodes' => [
        'allow_php_snippets' => true,
    ],
    'preview_token' => null,
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `shortcodes.allow_php_snippets` | bool | `true` | Enable `[snippet]` shortcode for PHP includes |
| `preview_token` | string\|null | `null` | Secret token for previewing draft content via `?preview=1&token=xxx` |

### Admin

```php
'admin' => [
    'enabled' => false,
    'path' => '/admin',
],
```

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | bool | `false` | Enable the admin dashboard |
| `path` | string | `'/admin'` | URL path for admin (e.g., `/admin`, `/dashboard`, `/_ava`) |

!> **Important**: Create admin users with `./ava user:create` before enabling.

### Plugins

```php
'plugins' => [
    'sitemap',
    'feed',
    'reading-time',
],
```

Array of plugin folder names to activate. Plugins load in the order listed.

---

## Content Types: `content_types.php`

Define what kinds of content your site has.

```php
<?php
return [
    'page' => [
        'label' => 'Pages',
        'content_dir' => 'pages',
        'url' => [
            'type' => 'hierarchical',
            'base' => '/',
        ],
        'templates' => [
            'single' => 'page.php',
        ],
        'taxonomies' => [],
        'sorting' => 'manual',
    ],

    'post' => [
        'label' => 'Posts',
        'content_dir' => 'posts',
        'url' => [
            'type' => 'pattern',
            'pattern' => '/blog/{slug}',
            'archive' => '/blog',
        ],
        'templates' => [
            'single' => 'post.php',
            'archive' => 'archive.php',
        ],
        'taxonomies' => ['category', 'tag'],
        'sorting' => 'date_desc',
    ],
];
```

### Content Type Options

| Option | Type | Description |
|--------|------|-------------|
| `label` | string | Human-readable name for admin UI |
| `content_dir` | string | Folder inside `content/` for this type |
| `url.type` | string | `'hierarchical'` or `'pattern'` |
| `url.base` | string | URL prefix for hierarchical types |
| `url.pattern` | string | URL template with placeholders |
| `url.archive` | string | Archive page URL (for pattern types) |
| `templates.single` | string | Template for single items |
| `templates.archive` | string | Template for archive/listing pages |
| `taxonomies` | array | Which taxonomies apply to this type |
| `sorting` | string | Default sort: `'date_desc'`, `'date_asc'`, `'title'`, `'manual'` |

### URL Types

**Hierarchical** — URL mirrors file path:
```
content/pages/about.md        → /about
content/pages/about/team.md   → /about/team
content/pages/services/web.md → /services/web
```

**Pattern** — URL from template with placeholders:
```php
'pattern' => '/blog/{slug}'           // → /blog/my-post
'pattern' => '/blog/{yyyy}/{slug}'    // → /blog/2024/my-post
'pattern' => '/{category}/{slug}'     // → /tutorials/my-post
```

| Placeholder | Description |
|-------------|-------------|
| `{slug}` | Item's slug |
| `{id}` | Item's unique ID |
| `{yyyy}` | 4-digit year |
| `{mm}` | 2-digit month |
| `{dd}` | 2-digit day |

---

## Taxonomies: `taxonomies.php`

Define ways to categorize content.

```php
<?php
return [
    'category' => [
        'label' => 'Categories',
        'plural' => 'Categories',
        'hierarchical' => true,
        'url' => [
            'base' => '/category',
        ],
    ],

    'tag' => [
        'label' => 'Tag',
        'plural' => 'Tags',
        'hierarchical' => false,
        'url' => [
            'base' => '/tag',
        ],
    ],
];
```

### Taxonomy Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `label` | string | required | Singular name |
| `plural` | string | label + 's' | Plural name for UI |
| `hierarchical` | bool | `false` | Support parent/child relationships |
| `url.base` | string | `'/{name}'` | URL prefix for term archives |

### Using Taxonomies in Content

```yaml
---
title: My Post
category: Tutorials
tag:
  - php
  - cms
  - beginner
---
```

---

## Environment-Specific Config

Override settings per environment:

```php
// app/config/ava.php

$config = [
    'site' => [
        'name' => 'My Site',
        'base_url' => 'https://example.com',
    ],
    'cache' => [
        'mode' => 'never',
    ],
];

// Override for local development
if (getenv('APP_ENV') === 'development') {
    $config['site']['base_url'] = 'http://localhost:8000';
    $config['cache']['mode'] = 'always';
    $config['admin']['enabled'] = true;
}

return $config;
```

---

## Complete Example

```php
<?php
// app/config/ava.php

return [
    'site' => [
        'name' => 'Acme Corp',
        'base_url' => 'https://acme.com',
        'timezone' => 'America/New_York',
        'locale' => 'en_US',
    ],

    'paths' => [
        'content' => 'content',
        'themes' => 'themes',
        'plugins' => 'plugins',
        'snippets' => 'snippets',
        'storage' => 'storage',
        'aliases' => [
            '@media:' => '/media/',
            '@cdn:' => 'https://cdn.acme.com/',
        ],
    ],

    'theme' => 'acme-theme',

    'cache' => [
        'mode' => 'never',  // Production
    ],

    'routing' => [
        'trailing_slash' => false,
    ],

    'content' => [
        'markdown' => [
            'allow_html' => true,
        ],
        'id' => [
            'type' => 'ulid',
        ],
    ],

    'security' => [
        'shortcodes' => [
            'allow_php_snippets' => true,
        ],
        'preview_token' => getenv('PREVIEW_TOKEN') ?: null,
    ],

    'admin' => [
        'enabled' => true,
        'path' => '/_admin',
    ],

    'plugins' => [
        'sitemap',
        'feed',
        'seo',
    ],
];
```
