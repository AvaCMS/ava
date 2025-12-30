# Creating Plugins

Want to add a new feature to Ava? Plugins are the way to do it.

## What is a Plugin?

A plugin is just a folder with a PHP file in it. It lets you add code to your site without touching core files.

You can use plugins to:
- Add new [shortcodes](shortcodes.md)
- Create custom routes (like a JSON API)
- Hook into Ava's lifecycle (modify content, add template variables, etc.)
- Add pages to the admin dashboard

## Your First Plugin

1. Create a folder: `plugins/my-plugin/`
2. Create a file: `plugins/my-plugin/plugin.php`

```php
<?php
return [
    'name' => 'My First Plugin',
    'version' => '1.0',
    
    'boot' => function($app) {
        // Your code goes here!
        
        // Example: Add a custom route
        $app->router()->addRoute('/hello', function() {
            return new \Ava\Http\Response('Hello World!');
        });
    }
];
```

3. Enable it in `app/config/ava.php`:

```php
'plugins' => [
    'sitemap',
    'feed',
    'my-plugin',  // Add your plugin here
],
```

That's it! Visit `/hello` and see your plugin in action.

---

## Understanding Hooks

Hooks are the backbone of Ava's plugin system. They let your code run at specific moments during Ava's lifecycle—like when content is being parsed, when a template is about to render, or when the admin sidebar is being built.

There are two types of hooks:

### Filters

Filters let you **modify data** as it passes through Ava. You receive a value, change it, and return it:

```php
use Ava\Plugins\Hooks;

// Add a variable to every template
Hooks::addFilter('render.context', function($context) {
    $context['my_plugin_version'] = '1.0';
    return $context;  // Must return the modified value
});
```

### Actions

Actions let you **react to events** without modifying data. Useful for logging, sending notifications, or side effects:

```php
use Ava\Plugins\Hooks;

// Log when content is indexed
Hooks::addAction('content.after_index', function($items) {
    error_log('Indexed ' . count($items) . ' items');
});
```

### Hook Priority

Hooks run in priority order (lower numbers first). Default is 10:

```php
// Run early (before most other hooks)
Hooks::addFilter('render.context', $callback, 5);

// Run late (after most other hooks)
Hooks::addFilter('render.context', $callback, 100);
```

---

## Available Hooks Reference

### Content Hooks

These fire during content parsing and indexing.

| Hook | Type | Description | Parameters |
|------|------|-------------|------------|
| `content.before_parse` | Filter | Before Markdown is parsed | `$content`, `$filePath` |
| `content.after_parse` | Filter | After Item object is created | `$item` |
| `content.after_index` | Action | After all content is indexed | `$items[]` |

### Rendering Hooks

These fire during page rendering.

| Hook | Type | Description | Parameters |
|------|------|-------------|------------|
| `render.context` | Filter | Modify template variables | `$context[]` |
| `render.before` | Action | Before template renders | `$template`, `$context` |
| `render.after` | Filter | After HTML is generated | `$html` |
| `markdown.before` | Filter | Before Markdown conversion | `$markdown` |
| `markdown.after` | Filter | After Markdown conversion | `$html` |

### Routing Hooks

These fire during request handling.

| Hook | Type | Description | Parameters |
|------|------|-------------|------------|
| `router.before_match` | Action | Before route matching | `$request`, `$router` |
| `router.after_match` | Filter | After route matched | `$match`, `$request` |

### Shortcode Hooks

These fire when shortcodes are processed.

| Hook | Type | Description | Parameters |
|------|------|-------------|------------|
| `shortcode.before` | Filter | Before shortcode runs | `$name`, `$attrs`, `$content` |
| `shortcode.after` | Filter | After shortcode runs | `$output`, `$name` |

### Admin Hooks

These let you extend the admin dashboard.

| Hook | Type | Description | Parameters |
|------|------|-------------|------------|
| `admin.register_pages` | Filter | Add custom admin pages | `$pages[]` |
| `admin.sidebar_items` | Filter | Add sidebar items | `$items[]` |

---

## Adding Routes

Plugins can register custom routes:

```php
use Ava\Http\Request;
use Ava\Http\Response;

'boot' => function($app) {
    $router = $app->router();
    
    // Simple route
    $router->addRoute('/api/posts', function(Request $request) use ($app) {
        $posts = $app->query()
            ->type('post')
            ->published()
            ->get();
        
        return Response::json(
            array_map(fn($p) => [
                'title' => $p->title(),
                'slug' => $p->slug(),
            ], $posts)
        );
    });
    
    // Route with parameters
    $router->addRoute('/api/posts/{slug}', function(Request $request, array $params) use ($app) {
        $post = $app->repository()->get('post', $params['slug']);
        if (!$post) {
            return Response::json(['error' => 'Not found'], 404);
        }
        return Response::json(['title' => $post->title()]);
    });
    
    // Prefix route (catch-all for /api/*)
    $router->addPrefixRoute('/api/', function(Request $request) {
        return Response::json(['error' => 'Unknown endpoint'], 404);
    });
}
```

## Adding Admin Pages

```php
use Ava\Plugins\Hooks;
use Ava\Http\Request;
use Ava\Http\Response;
use Ava\Application;

'boot' => function($app) {
    Hooks::addFilter('admin.register_pages', function(array $pages) {
        $pages['my-plugin'] = [
            'label' => 'My Plugin',           // Sidebar label
            'icon' => 'extension',            // Material icon name
            'section' => 'Plugins',           // Sidebar section
            'handler' => function(Request $request, Application $app, $controller) {
                ob_start();
                include __DIR__ . '/views/admin.php';
                return Response::html(ob_get_clean());
            },
        ];
        return $pages;
    });
}
```

## Registering Shortcodes

```php
'boot' => function($app) {
    $shortcodes = $app->shortcodes();
    
    $shortcodes->register('button', function($attrs, $content) {
        $href = htmlspecialchars($attrs['href'] ?? '#');
        $class = htmlspecialchars($attrs['class'] ?? 'btn');
        return "<a href=\"{$href}\" class=\"{$class}\">{$content}</a>";
    });
}
```

Usage: `[button href="/contact" class="btn-primary"]Get in Touch[/button]`

See [Shortcodes](shortcodes.md) for more details.

## Enabling Plugins

Add plugins to `app/config/ava.php`:

```php
'plugins' => [
    'sitemap',
    'feed',
    'redirects',
    'my-plugin',
],
```

Plugins load in the order listed.

---

## Complete Example: Reading Time

A full plugin that adds reading time estimates to posts:

```php
<?php
// plugins/reading-time/plugin.php

use Ava\Plugins\Hooks;

return [
    'name' => 'Reading Time',
    'version' => '1.0.0',
    'description' => 'Adds estimated reading time to content items',
    'author' => 'Ava CMS',
    
    'boot' => function($app) {
        Hooks::addFilter('render.context', function($context) {
            if (isset($context['page']) && $context['page'] instanceof \Ava\Content\Item) {
                $content = $context['page']->rawContent();
                $wordCount = str_word_count(strip_tags($content));
                $minutes = max(1, ceil($wordCount / 200));
                $context['reading_time'] = $minutes;
            }
            return $context;
        });
    }
];
```

In your template:
```php
<?php if (isset($reading_time)): ?>
    <span class="reading-time"><?= $reading_time ?> min read</span>
<?php endif; ?>
```

## Plugin Assets

Include CSS or JS from your plugin:

```php
'boot' => function($app) {
    Hooks::addFilter('render.context', function($context) {
        $context['plugin_assets'][] = '/plugins/my-plugin/assets/style.css';
        return $context;
    });
}
```

Then in your theme's `<head>`:
```php
<?php foreach ($plugin_assets ?? [] as $asset): ?>
    <?php if (str_ends_with($asset, '.css')): ?>
        <link rel="stylesheet" href="<?= $asset ?>">
    <?php else: ?>
        <script src="<?= $asset ?>"></script>
    <?php endif; ?>
<?php endforeach; ?>
```
