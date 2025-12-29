# Routing

In Ava, you don't need to write complex route files. URLs are generated automatically based on your content.

## How It Works

Ava looks at your `content/` folder and your configuration to decide what URL each file gets.

1. **💾 You save a file.**
2. **👀 Ava sees it.**
3. **✨ The URL works.**

## URL Styles

You can control how URLs look in `app/config/content_types.php`.

### 1. Folder Style (Hierarchical)

Great for standard pages. The URL matches the folder structure.

- `content/pages/about.md` → `/about`
- `content/pages/services/web.md` → `/services/web`

```php
'page' => [
    'url' => [
        'type' => 'hierarchical',
        'base' => '/',
    ],
]
```

### 2. Pattern Style (Blog Posts)

Great for blogs, where you want a consistent structure like `/blog/{slug}` or `/2024/{slug}`.

- `content/posts/hello-world.md` → `/blog/hello-world`

```php
'post' => [
    'url' => [
        'type' => 'pattern',
        'pattern' => '/blog/{slug}', // You can use {year}, {month}, {day} too!
    ],
]
```

## Redirects

Need to move a page? Just add `redirect_from` to the file's frontmatter. Ava handles the 301 redirect for you.

For more complex routing needs, check out the [Configuration guide](configuration.md).

```yaml
---
title: New Page
slug: new-page
redirect_from:
  - /old-page
  - /legacy/page
---
```

Requests to `/old-page` redirect 301 to the new URL.

## Trailing Slash

Configure in `ava.php`:

```php
'routing' => [
    'trailing_slash' => false,  // /about (not /about/)
]
```

Non-canonical URLs redirect to canonical form.

## Route Caching

Routes are compiled to `storage/cache/routes.php`:

```php
return [
    'redirects' => [
        '/old-url' => ['to' => '/new-url', 'code' => 301],
    ],
    'exact' => [
        '/' => ['type' => 'single', 'content_type' => 'page', 'slug' => 'index', ...],
        '/about' => ['type' => 'single', 'content_type' => 'page', 'slug' => 'about', ...],
        '/blog' => ['type' => 'archive', 'content_type' => 'post', ...],
        '/blog/hello-world' => ['type' => 'single', 'content_type' => 'post', ...],
    ],
    'taxonomy' => [
        'category' => ['base' => '/category', 'hierarchical' => true],
        'tag' => ['base' => '/tag', 'hierarchical' => false],
    ],
];
```

## Preview Mode

Drafts and private content accessible with token:

```
/blog/draft-post?preview=1&token=YOUR_TOKEN
```

Configure token in `ava.php`:

```php
'security' => [
    'preview_token' => 'your-secret-token',
]
```

## Adding Custom Routes

In plugins or `app/hooks.php`:

```php
use Ava\Application;

$router = Application::getInstance()->router();

// Exact route
$router->addRoute('/api/search', function ($request) {
    // Return RouteMatch or handle directly
});

// Prefix route
$router->addPrefixRoute('/api/', function ($request) {
    // Handles all /api/* requests
});
```
