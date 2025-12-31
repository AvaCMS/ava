# Themes

Ava themes are HTML-first templates with PHP available when you need it. Start with normal HTML, then sprinkle in `<?= ?>` to output data or call helpers. There’s no custom templating language, no build step, and no new syntax to learn.

```php
<!-- Output a title -->
<h1><?= $ava->e($item->title()) ?></h1>

<!-- Render the Markdown content as HTML -->
<div class="content">
    <?= $ava->content($item) ?>
</div>

<!-- Link to a stylesheet in your theme's assets folder -->
<link rel="stylesheet" href="<?= $ava->asset('style.css') ?>">

<!-- Loop through recent posts -->
<?php foreach ($ava->recent('post', 5) as $post): ?>
    <article>
        <h2><a href="<?= $ava->url('post', $post->slug()) ?>"><?= $ava->e($post->title()) ?></a></h2>
        <time><?= $ava->date($post->date()) ?></time>
    </article>
<?php endforeach; ?>
```

You decide how much custom PHP to use: none for simple pages, or more for dynamic layouts. The helpers are there when you want them, but HTML remains the core.

## Why HTML + PHP (and not a custom templating language)?

**What you gain**
- **Familiar building blocks** — If you know HTML, you can start immediately. Output is just `<?= $variable ?>` and the `$ava` helper.
- **No build pipeline** — Save the file, refresh the browser. No compilers, watchers, or caches to clear.
- **Full power available** — Need a loop, conditional, or a custom helper? Use plain PHP—no special template language or custom syntax.
- **Easy to debug** — Standard PHP errors, standard stack traces. Nothing is hidden behind a template engine.

**What you trade off**
- Syntax is a bit noisier than `{{ }}`-style engines
- You should know (or be willing to learn) a few PHP basics: `<?= ?>`, `if`, `foreach`
- Very complex view logic should live in helpers or `theme.php` to keep templates readable

**Who this suits**
- Designers comfortable with HTML/CSS who want minimal new concepts
- Developers who want flexibility without adopting a custom template language or dealing with additional tooling
- Beginners who want to learn web fundamentals instead of framework-specific magic
- Teams that prefer transparency and portability 

## Theme Structure

A theme is just a folder in `themes/`. Here's a typical layout:

```
themes/
└── default/
    ├── templates/        # Your page layouts
    │   ├── index.php     # The default layout
    │   ├── page.php      # For standard pages
    │   ├── post.php      # For blog posts
    │   └── 404.php       # "Page not found" error
    ├── assets/           # CSS, JS, images
    │   ├── style.css
    │   └── script.js
    └── theme.php         # Optional setup code
```

## Using Assets

Ava makes it easy to include your CSS and JS files. It even handles cache-busting automatically, so your visitors always see the latest version based on the files modified time.

```php
<!-- Just ask $ava for the asset URL -->
<link rel="stylesheet" href="<?= $ava->asset('style.css') ?>">
<script src="<?= $ava->asset('script.js') ?>"></script>
```

?> This outputs a URL like `/theme/style.css?v=123456`, ensuring instant updates when you change the file without worrying about browser or CDN caching.

## Template Basics

In your template files (like `page.php`), you have access to your content variables.

```php
<!-- templates/post.php -->
<?= $ava->partial('header', ['title' => $item->title()]) ?>

<article>
    <h1><?= $ava->e($item->title()) ?></h1>
    
    <div class="content">
        <?= $ava->content($item) ?>
    </div>
    
    <?php if ($item->date()): ?>
        <time><?= $ava->date($item->date(), 'F j, Y') ?></time>
    <?php endif; ?>
</article>

<?= $ava->partial('footer') ?>
```

It's just HTML with simple tags to show your data.

## Available Variables

These variables are available in your templates:

| Variable | Type | Description |
|----------|------|-------------|
| `$item` | `Item` | The current content item (for single post/page templates). |
| `$query` | `Query` | Query object for archive/listing templates. |
| `$tax` | `array` | Taxonomy info for taxonomy templates (`name`, `term`, `items`). |
| `$site` | `array` | Site configuration (`name`, `url`, `timezone`). |
| `$theme` | `array` | Current theme info (`name`, `path`, `url`). |
| `$request` | `Request` | Current HTTP request (path, query params, etc.). |
| `$route` | `RouteMatch` | Matched route information. |
| `$ava` | `TemplateHelpers` | Helper methods for rendering, URLs, queries, and more. |

?> Not all variables are present in every template. For example, `$item` only exists on single content pages, while `$query` is for archives.

## The `$ava` Helper

Templates have access to `$ava` with these methods:

### Content Rendering

```php
// Render item's Markdown body
<?= $ava->content($item) ?>

// Render Markdown string
<?= $ava->markdown('**bold**') ?>

// Expand path aliases
<?= $ava->expand('@media:image.jpg') ?>
```

### Partials

Partials are reusable template fragments stored in `themes/{theme}/partials/`. Use them to avoid repeating common elements like headers, footers, or sidebars.

```php
// Render a partial
<?= $ava->partial('header') ?>

// Pass data to a partial
<?= $ava->partial('card', ['title' => 'Hello', 'link' => '/about']) ?>
```

Inside a partial, passed data becomes local variables:

```php
<!-- partials/card.php -->
<div class="card">
    <h3><?= $ava->e($title) ?></h3>
    <a href="<?= $link ?>">Read more</a>
</div>
```

Partials inherit the same helper methods (`$ava`, `$site`, `$theme`) as templates.

### URLs

```php
// URL for content item
<?= $ava->url('post', 'hello-world') ?>

// URL for taxonomy term
<?= $ava->termUrl('category', 'tutorials') ?>

// Theme asset URL with cache busting (no leading slash)
<?= $ava->asset('style.css') ?>
<?= $ava->asset('js/app.js') ?>

// Public asset URL (leading slash = public directory)
<?= $ava->asset('/uploads/image.jpg') ?>

// Full URL
<?= $ava->fullUrl('/about') ?>
```

### Queries

The `$ava->query()` method returns a fluent query builder for fetching content. All queries are immutable—each method returns a new query instance.

```php
// Get the 5 most recent published posts
$posts = $ava->query()
    ->type('post')
    ->published()
    ->orderBy('date', 'desc')
    ->perPage(5)
    ->get();

// Loop through results
foreach ($posts as $post) {
    echo $post->title();
}
```

#### Query Methods Reference

| Method | Description | Example |
|--------|-------------|---------|
| `type(string)` | Filter by content type | `->type('post')` |
| `status(string)` | Filter by status | `->status('published')` |
| `published()` | Shortcut for `status('published')` | `->published()` |
| `whereTax(tax, term)` | Filter by taxonomy term | `->whereTax('category', 'tutorials')` |
| `where(field, value, op)` | Filter by field value | `->where('featured', true)` |
| `orderBy(field, dir)` | Sort results | `->orderBy('date', 'desc')` |
| `perPage(int)` | Items per page (max 100) | `->perPage(10)` |
| `page(int)` | Current page number | `->page(2)` |
| `search(string)` | Full-text search with relevance scoring | `->search('php tutorial')` |
| `searchWeights(array)` | Override search weights | `->searchWeights(['title_phrase' => 100])` |

#### Query Result Methods

| Method | Returns | Description |
|--------|---------|-------------|
| `get()` | `Item[]` | Execute query, get items |
| `first()` | `Item\|null` | Get first matching item |
| `count()` | `int` | Total items (before pagination) |
| `totalPages()` | `int` | Number of pages |
| `currentPage()` | `int` | Current page number |
| `hasMore()` | `bool` | Are there more pages? |
| `hasPrevious()` | `bool` | Are there previous pages? |
| `isEmpty()` | `bool` | No results? |
| `pagination()` | `array` | Full pagination info |

#### Helper Shortcuts

```php
// Recent items shortcut
$recent = $ava->recent('post', 5);

// Get specific item by slug
$about = $ava->get('page', 'about');

// Get taxonomy terms
$categories = $ava->terms('category');
```

### The `$item` Object

Every content item gives you access to its data through the `$item` variable.

#### Core Properties

| Method | Returns | Description |
|--------|---------|-------------|
| `id()` | `string\|null` | Unique identifier (ULID) |
| `title()` | `string` | Title from frontmatter |
| `slug()` | `string` | URL-friendly slug |
| `status()` | `string` | `draft`, `published`, or `unlisted` |
| `type()` | `string` | Content type (`page`, `post`, etc.) |

#### Status Helpers

| Method | Returns | Description |
|--------|---------|-------------|
| `isPublished()` | `bool` | Is status "published"? |
| `isDraft()` | `bool` | Is status "draft"? |
| `isUnlisted()` | `bool` | Is status "unlisted"? |

#### Dates

| Method | Returns | Description |
|--------|---------|-------------|
| `date()` | `DateTimeImmutable\|null` | Publication date |
| `updated()` | `DateTimeImmutable\|null` | Last updated (falls back to date) |

```php
<?php if ($item->date()): ?>
    <time datetime="<?= $item->date()->format('c') ?>">
        <?= $ava->date($item->date(), 'F j, Y') ?>
    </time>
<?php endif; ?>
```

#### Content

| Method | Returns | Description |
|--------|---------|-------------|
| `rawContent()` | `string` | Raw Markdown body |
| `html()` | `string\|null` | Rendered HTML (after processing) |
| `excerpt()` | `string\|null` | Excerpt from frontmatter |

```php
// Render the Markdown body to HTML
<?= $ava->content($item) ?>

// Or access excerpt
<p><?= $ava->e($item->excerpt()) ?></p>
```

#### Custom Fields

Access any frontmatter field using `get()`:

```php
// Get a custom field with optional default
$role = $item->get('role', 'Unknown');
$featured = $item->get('featured', false);

// Check if a field exists
if ($item->has('website')) {
    echo '<a href="' . $ava->e($item->get('website')) . '">Visit Website</a>';
}
```

#### Taxonomies

| Method | Returns | Description |
|--------|---------|-------------|
| `terms()` | `array` | All taxonomy terms |
| `terms('category')` | `array` | Terms for specific taxonomy |

```php
<?php foreach ($item->terms('category') as $term): ?>
    <a href="<?= $ava->termUrl('category', $term) ?>"><?= $ava->e($term) ?></a>
<?php endforeach; ?>
```

#### SEO Fields

| Method | Returns | Description |
|--------|---------|-------------|
| `metaTitle()` | `string\|null` | Custom meta title |
| `metaDescription()` | `string\|null` | Meta description |
| `noindex()` | `bool` | Should search engines skip this? |
| `canonical()` | `string\|null` | Canonical URL |
| `ogImage()` | `string\|null` | Open Graph image URL |

#### Assets & Hierarchy

| Method | Returns | Description |
|--------|---------|-------------|
| `css()` | `array` | Per-item CSS files |
| `js()` | `array` | Per-item JS files |
| `template()` | `string\|null` | Custom template name |
| `parent()` | `string\|null` | Parent page slug |
| `order()` | `int` | Manual sort order |
| `redirectFrom()` | `array` | Old URLs that redirect here |
| `filePath()` | `string` | Path to the Markdown file |

### Advanced Query Examples

```php
// Posts in a category
$tutorials = $ava->query()
    ->type('post')
    ->published()
    ->whereTax('category', 'tutorials')
    ->orderBy('date', 'desc')
    ->perPage(10)
    ->get();

// Featured items (custom field)
$featured = $ava->query()
    ->type('post')
    ->published()
    ->where('featured', true)
    ->get();

// Search results
$results = $ava->query()
    ->type('post')
    ->published()
    ->search($request->query('q'))
    ->perPage(20)
    ->page($request->query('page', 1))
    ->get();

// All items ordered by title
$alphabetical = $ava->query()
    ->type('page')
    ->published()
    ->orderBy('title', 'asc')
    ->get();
```

### Head

```php
// Meta tags for item
<?= $ava->metaTags($item) ?>

// Per-item CSS/JS
<?= $ava->itemAssets($item) ?>
```

### Pagination

```php
// Pagination HTML
<?= $ava->pagination($query, $request->path()) ?>
```

### Utilities

```php
// Escape HTML
<?= $ava->e($value) ?>

// Format date (see PHP date() format codes)
<?= $ava->date($item->date(), 'F j, Y') ?>    // December 31, 2025
<?= $ava->date($item->date(), 'Y-m-d') ?>     // 2025-12-31
<?= $ava->date($item->date(), 'M j') ?>       // Dec 31
<?= $ava->date($item->date(), 'l, F jS') ?>   // Wednesday, December 31st

// Relative time
<?= $ava->ago($item->date()) ?>  // "2 hours ago", "3 days ago"

// Truncate to words
<?= $ava->excerpt($text, 55) ?>

// Get config value
<?= $ava->config('site.name') ?>
```

?> Date formats use [PHP's date() format codes](https://www.php.net/manual/en/datetime.format.php). Common: `Y` (year), `m` (month), `d` (day), `F` (full month name), `M` (short month), `j` (day without zero).

## Template Resolution

When a content item is requested, Ava looks for a template in this order:

1. **Frontmatter `template` field** — If the item specifies `template: landing`, use `templates/landing.php`
2. **Content type's template** — From `content_types.php`, e.g., posts use `post.php`
3. **`single.php` fallback** — A generic single-item template
4. **`index.php` fallback** — The ultimate default

For archives and taxonomy pages, Ava uses:
- `archive.php` for content type listings
- `taxonomy.php` for taxonomy term pages
- Falls back to `index.php` if not found

## Partials

Partials are reusable template fragments stored in `themes/{theme}/partials/`. Use them for headers, footers, sidebars, and other repeated elements:

```php
<!-- Render a partial -->
<?= $ava->partial('header') ?>

<!-- Pass data to a partial -->
<?= $ava->partial('header', ['title' => $item->title(), 'showNav' => true]) ?>
```

In the partial file, passed data becomes local variables:

```php
<!-- partials/header.php -->
<header>
    <h1><?= $ava->e($title ?? $site['name']) ?></h1>
    <?php if ($showNav ?? true): ?>
        <nav>...</nav>
    <?php endif; ?>
</header>
```

Partials automatically inherit `$site`, `$theme`, `$request`, and `$ava`.

## Example Templates

Here's a complete example showing how partials, templates, and layouts work together.

### Header Partial

```php
<!-- partials/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $ava->e($title ?? $site['name']) ?></title>
    <?php if (isset($item)): ?>
        <?= $ava->metaTags($item) ?>
        <?= $ava->itemAssets($item) ?>
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $ava->asset('style.css') ?>">
</head>
<body>
    <header class="site-header">
        <a href="/" class="logo"><?= $ava->e($site['name']) ?></a>
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/blog">Blog</a>
        </nav>
    </header>
    <main>
```

### Footer Partial

```php
<!-- partials/footer.php -->
    </main>
    <footer class="site-footer">
        <p>&copy; <?= date('Y') ?> <?= $ava->e($site['name']) ?></p>
    </footer>
    <script src="<?= $ava->asset('script.js') ?>"></script>
</body>
</html>
```

### Page Template

```php
<!-- templates/page.php -->
<?= $ava->partial('header', ['title' => $item->title(), 'item' => $item]) ?>

<article class="page">
    <h1><?= $ava->e($item->title()) ?></h1>
    
    <div class="content">
        <?= $ava->content($item) ?>
    </div>
</article>

<?= $ava->partial('footer') ?>
```

### Post Template

```php
<!-- templates/post.php -->
<?= $ava->partial('header', ['title' => $item->title(), 'item' => $item]) ?>

<article class="post">
    <header class="post-header">
        <h1><?= $ava->e($item->title()) ?></h1>
        
        <?php if ($item->date()): ?>
            <time datetime="<?= $item->date()->format('c') ?>">
                <?= $ava->date($item->date(), 'F j, Y') ?>
            </time>
        <?php endif; ?>
        
        <?php if ($categories = $item->terms('category')): ?>
            <div class="categories">
                <?php foreach ($categories as $term): ?>
                    <a href="<?= $ava->termUrl('category', $term) ?>"><?= $ava->e($term) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </header>
    
    <div class="content">
        <?= $ava->content($item) ?>
    </div>
</article>

<?= $ava->partial('footer') ?>
```

### Archive Template

```php
<!-- templates/archive.php -->
<?= $ava->partial('header', ['title' => 'Blog']) ?>

<h1>Blog</h1>

<?php foreach ($query->get() as $post): ?>
    <article class="post-summary">
        <h2><a href="<?= $ava->url('post', $post->slug()) ?>"><?= $ava->e($post->title()) ?></a></h2>
        <time><?= $ava->date($post->date(), 'M j, Y') ?></time>
        <?php if ($post->excerpt()): ?>
            <p><?= $ava->e($post->excerpt()) ?></p>
        <?php endif; ?>
    </article>
<?php endforeach; ?>

<?= $ava->pagination($query, $request->path()) ?>

<?= $ava->partial('footer') ?>
```

## Theme Bootstrap

`theme.php` runs when your theme loads. Use it for hooks, shortcodes, and custom routes:

```php
<?php
// themes/yourtheme/theme.php

use Ava\Application;
use Ava\Plugins\Hooks;

$app = Application::getInstance();

// Register shortcodes
$app->shortcodes()->register('theme_version', fn() => '1.0.0');

// Add data to all templates
Hooks::addFilter('render.context', function (array $context) {
    $context['social_links'] = [
        'twitter' => 'https://twitter.com/yoursite',
        'github' => 'https://github.com/yoursite',
    ];
    return $context;
});

// Custom route
$app->router()->addRoute('/search', function ($request) use ($app) {
    // Handle search...
});
```

### Organizing Larger Themes

If your `theme.php` grows unwieldy, split it into multiple files:

```php
<?php
// themes/yourtheme/theme.php

require __DIR__ . '/inc/shortcodes.php';
require __DIR__ . '/inc/hooks.php';
require __DIR__ . '/inc/routes.php';
```

```php
<?php
// themes/yourtheme/inc/shortcodes.php

use Ava\Application;

$shortcodes = Application::getInstance()->shortcodes();

$shortcodes->register('button', function (array $attrs, ?string $content) {
    $url = $attrs['url'] ?? '#';
    $class = $attrs['class'] ?? 'btn';
    return '<a href="' . htmlspecialchars($url) . '" class="' . htmlspecialchars($class) . '">' . htmlspecialchars($content ?? 'Click') . '</a>';
});
```

This keeps your theme organized while maintaining portability—everything travels with your theme folder.
