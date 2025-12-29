# Creating Plugins

Want to add a new feature to Ava? Plugins are the way to do it.

## What is a Plugin?

A plugin is just a folder with a PHP file in it. It lets you add code to your site without messing up the core files.

You can use plugins to:
- Add new shortcodes.
- Create custom routes (like a JSON API).
- Hook into Ava's events (like "after a page is saved").

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
            return 'Hello World!';
        });
    }
];
```

3. Enable it in `app/config/ava.php`.

That's it! You've extended Ava.
```php
$context = Hooks::apply('render.context', $context);
```

To trigger actions:
```php
Hooks::doAction('content.after_index', $items);
```

### Available Hooks

#### Content Hooks

| Hook | Description | Parameters |
|------|-------------|------------|
| `content.before_parse` | Before Markdown is parsed | `$content`, `$filePath` |
| `content.after_parse` | After Item is created | `$item` |
| `content.after_index` | After all content indexed | `$items[]` |

#### Rendering Hooks

| Hook | Description | Parameters |
|------|-------------|------------|
| `render.context` | Modify template context | `$context[]` |
| `render.before` | Before template renders | `$template`, `$context` |
| `render.after` | After HTML generated | `$html` |
| `markdown.before` | Before Markdown conversion | `$markdown` |
| `markdown.after` | After Markdown conversion | `$html` |

#### Routing Hooks

| Hook | Description | Parameters |
|------|-------------|------------|
| `router.before_match` | Before route matching | `$request`, `$router` |
| `router.after_match` | After route matched | `$match`, `$request` |

#### Shortcode Hooks

| Hook | Description | Parameters |
|------|-------------|------------|
| `shortcode.before` | Before shortcode processed | `$name`, `$attrs`, `$content` |
| `shortcode.after` | After shortcode processed | `$output`, `$name` |

#### Admin Hooks

| Hook | Description | Parameters |
|------|-------------|------------|
| `admin.register_pages` | Register custom admin pages | `$pages[]`, `$app` |
| `admin.sidebar_items` | Add custom sidebar items | `$items[]`, `$app` |

### Hook Priority

Add a priority (lower runs first):

```php
// Run early (priority 5)
Hooks::add('render.context', $callback, 5);

// Run late (priority 100)
Hooks::add('render.context', $callback, 100);

// Default priority is 10
```

## Adding Routes

Plugins can register custom routes that return Response objects directly:

```php
use Ava\Http\Request;
use Ava\Http\Response;

'boot' => function($app) {
    $router = $app->router();
    
    // Route returning Response directly
    $router->addRoute('/api/posts', function(Request $request) use ($app) {
        $posts = $app->repository()->published('post');
        
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
            return new Response('Not found', 404);
        }
        return Response::json(['title' => $post->title()]);
    });
    
    // Prefix route (matches anything under /api/*)
    $router->addPrefixRoute('/api/', function(Request $request) {
        // Handle all /api/* requests
        return Response::json(['error' => 'Unknown endpoint'], 404);
    });
}
```

## Adding Admin Pages

Plugins can add pages to the admin interface:

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
                // Your admin page logic
                ob_start();
                include __DIR__ . '/views/admin.php';
                $html = ob_get_clean();
                
                return Response::html($html);
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
        $href = $attrs['href'] ?? '#';
        $class = $attrs['class'] ?? 'btn';
        return "<a href=\"{$href}\" class=\"{$class}\">{$content}</a>";
    });
}
```

Usage in content:
```markdown
[button href="/contact" class="btn-primary"]Get in Touch[/button]
```

## Enabling Plugins

Add plugins to your `app/config/ava.php`:

```php
return [
    // ...
    
    'plugins' => [
        'sitemap',
        'feed',
        'redirects',
        'my-plugin',
    ],
];
```

Plugins are loaded in the order listed.

## Example Plugin: Reading Time

A complete example that adds reading time to posts:

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
        // Add reading_time to template context
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

Usage in templates:
```php
<?php if (isset($reading_time)): ?>
    <span class="reading-time"><?= $reading_time ?> min read</span>
<?php endif; ?>
```

## Plugin Assets

To include CSS or JS from your plugin:

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
