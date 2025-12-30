# Themes

Themes control how your site looks. In Ava, a theme is just a collection of standard HTML files with a sprinkle of PHP to pull in your content.

## Why Plain PHP?

We believe you shouldn't have to learn a complex template language just to display a title.

- **🧱 It's just HTML.** If you know HTML, you're 90% of the way there.
- **🛠️ Simple Helpers.** We provide an easy `$ava` helper to get what you need.
- **⚡ Zero Compilation.** Edit a file, refresh your browser, and see the change instantly.

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

Ava makes it easy to include your CSS and JS files. It even handles cache-busting automatically, so your visitors always see the latest version.

```php
<!-- Just ask $ava for the asset URL -->
<link rel="stylesheet" href="<?= $ava->asset('style.css') ?>">
<script src="<?= $ava->asset('script.js') ?>"></script>
```

This outputs a URL like `/theme/style.css?v=123456`, ensuring instant updates when you change the file.

## Template Basics

In your template files (like `page.php`), you have access to your content variables.

```php
<!-- templates/post.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= $page->title() ?></title>
</head>
<body>
    <h1><?= $page->title() ?></h1>
    
    <div class="content">
        <?= $page->content() ?>
    </div>
    
    <p>Published on <?= $page->date()->format('F j, Y') ?></p>
</body>
</html>
```

See? It's just HTML with simple tags to show your data.

## Available Variables

| Variable | What it is |
|----------|-------------|
| `$site` | Global site info (name, URL). |
| `$page` | The current page or post being viewed. |
| `$theme` | Info about the current theme. |
| `$request` | Details about the current URL. |
| `$ava` | TemplateHelpers instance |

## The `$ava` Helper

Templates have access to `$ava` with these methods:

### Content Rendering

```php
// Render item's Markdown body
<?= $ava->content($page) ?>

// Render Markdown string
<?= $ava->markdown('**bold**') ?>

// Render a partial
<?= $ava->partial('header', ['title' => 'Custom']) ?>

// Expand path aliases
<?= $ava->expand('@uploads:image.jpg') ?>
```

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
| `search(string)` | Full-text search | `->search('php tutorial')` |

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

### The `$page` (Item) Object

Every content item gives you access to its data through the `$page` variable (or `$item` in loops).

#### Core Properties

| Method | Returns | Description |
|--------|---------|-------------|
| `id()` | `string\|null` | Unique identifier (ULID) |
| `title()` | `string` | Title from frontmatter |
| `slug()` | `string` | URL-friendly slug |
| `status()` | `string` | `draft`, `published`, or `private` |
| `type()` | `string` | Content type (`page`, `post`, etc.) |

#### Status Helpers

| Method | Returns | Description |
|--------|---------|-------------|
| `isPublished()` | `bool` | Is status "published"? |
| `isDraft()` | `bool` | Is status "draft"? |
| `isPrivate()` | `bool` | Is status "private"? |

#### Dates

| Method | Returns | Description |
|--------|---------|-------------|
| `date()` | `DateTimeImmutable\|null` | Publication date |
| `updated()` | `DateTimeImmutable\|null` | Last updated (falls back to date) |

```php
<?php if ($page->date()): ?>
    <time datetime="<?= $page->date()->format('c') ?>">
        <?= $ava->date($page->date(), 'F j, Y') ?>
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
<?= $ava->content($page) ?>

// Or access excerpt
<p><?= $ava->e($page->excerpt()) ?></p>
```

#### Custom Fields

Access any frontmatter field using `get()`:

```php
// Get a custom field with optional default
$role = $page->get('role', 'Unknown');
$featured = $page->get('featured', false);

// Check if a field exists
if ($page->has('website')) {
    echo '<a href="' . $ava->e($page->get('website')) . '">Visit Website</a>';
}
```

#### Taxonomies

| Method | Returns | Description |
|--------|---------|-------------|
| `terms()` | `array` | All taxonomy terms |
| `terms('category')` | `array` | Terms for specific taxonomy |

```php
<?php foreach ($page->terms('category') as $term): ?>
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

### SEO

```php
// Meta tags for item
<?= $ava->metaTags($page) ?>

// Per-item CSS/JS
<?= $ava->itemAssets($page) ?>
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

// Format date
<?= $ava->date($page->date(), 'F j, Y') ?>

// Relative time
<?= $ava->ago($page->date()) ?>

// Truncate to words
<?= $ava->excerpt($text, 55) ?>

// Get config value
<?= $ava->config('site.name') ?>
```

## Example Template

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $ava->metaTags($page) ?>
    <?= $ava->itemAssets($page) ?>
    <link rel="stylesheet" href="<?= $ava->asset('style.css') ?>">
</head>
<body>
    <header>
        <a href="/"><?= $ava->e($site['name']) ?></a>
    </header>

    <main>
        <article>
            <h1><?= $ava->e($page->title()) ?></h1>
            
            <?php if ($page->date()): ?>
                <time datetime="<?= $page->date()->format('c') ?>">
                    <?= $ava->date($page->date()) ?>
                </time>
            <?php endif; ?>

            <div class="content">
                <?= $ava->content($page) ?>
            </div>
        </article>
    </main>

    <footer>
        &copy; <?= date('Y') ?> <?= $ava->e($site['name']) ?>
    </footer>
</body>
</html>
```

## Template Resolution

Templates are resolved in order:

1. Frontmatter `template` field
2. Content type's configured template
3. `single.php` fallback
4. `index.php` fallback

## Theme Bootstrap

`theme.php` can register hooks and shortcodes:

```php
<?php

use Ava\Plugins\Hooks;
use Ava\Application;

// Add theme shortcode
Application::getInstance()->shortcodes()->register('theme_version', fn() => '1.0.0');

// Modify template context
Hooks::addFilter('render.context', function (array $context) {
    $context['theme_setting'] = 'value';
    return $context;
});
```
