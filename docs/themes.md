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

```php
// New query
$posts = $ava->query()
    ->type('post')
    ->published()
    ->orderBy('date', 'desc')
    ->perPage(5)
    ->get();

// Recent items shortcut
$recent = $ava->recent('post', 5);

// Get specific item
$about = $ava->get('page', 'about');

// Get taxonomy terms
$categories = $ava->terms('category');
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
