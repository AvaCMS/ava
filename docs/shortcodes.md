# Shortcodes

Shortcodes are simple, reusable tags you can use in your Markdown to add dynamic content or complex HTML structures without cluttering your writing.

## Why Use Them?

Markdown is great for writing, but sometimes you need more:
- **📅 Dynamic data** — Insert the current year, site name, or other live values.
- **🧩 Rich components** — Add buttons, cards, or callouts without raw HTML.
- **♻️ Reusable blocks** — Create once, use everywhere. Update in one place.

## How They Work

Shortcodes use square brackets. They can be self-closing or wrap content:

```markdown
Copyright © [year] [site_name]

[button url="/contact"]Get in Touch[/button]
```

When the page renders, Ava replaces these with the actual values.

## Built-in Shortcodes

| Shortcode | What it does | Example output |
|-----------|--------------|----------------|
| `[year]` | Current year | `2024` |
| `[site_name]` | Your site's name from config | `My Ava Site` |
| `[email]you@example.com[/email]` | Spam-protected email link | Obfuscated mailto link |
| `[button url="/path"]Text[/button]` | Styled button | `<a class="button">` element |
| `[snippet name="file"]` | Include PHP snippet | Output of `snippets/file.php` |

## The Snippet Shortcode

The `[snippet]` shortcode is where things get really powerful. It lets you create reusable PHP components and use them anywhere in your content.

### Creating a Snippet

Create a PHP file in your `snippets/` folder:

```php
<?php // snippets/cta.php ?>
<div class="cta-box" style="background: #f0f4f8; padding: 2rem; border-radius: 8px;">
    <h3><?= htmlspecialchars($heading ?? 'Ready to get started?') ?></h3>
    <p><?= $content ?></p>
    <a href="<?= htmlspecialchars($url ?? '/contact') ?>" class="button">
        <?= htmlspecialchars($button ?? 'Learn More') ?>
    </a>
</div>
```

### Using It in Content

```markdown
[snippet name="cta" heading="Join Our Newsletter" button="Subscribe" url="/subscribe"]
Get weekly tips and updates delivered straight to your inbox.
[/snippet]
```

### Variables Available in Snippets

| Variable | Description |
|----------|-------------|
| `$content` | Everything between opening and closing tags |
| `$name` | The snippet name |
| `$attrs` | Array of all attributes passed |
| `$heading`, `$url`, etc. | Individual attributes as variables |

## Practical Use Cases

### Pricing Tables

```php
<?php // snippets/pricing.php ?>
<div class="pricing-card">
    <h3><?= $ava->e($name ?? 'Plan') ?></h3>
    <div class="price"><?= $ava->e($price ?? '$0') ?><span>/month</span></div>
    <ul>
        <?php foreach (explode(',', $features ?? '') as $feature): ?>
            <li><?= $ava->e(trim($feature)) ?></li>
        <?php endforeach; ?>
    </ul>
    <a href="<?= $ava->e($url ?? '#') ?>" class="button"><?= $ava->e($cta ?? 'Get Started') ?></a>
</div>
```

Usage: `[snippet name="pricing" name="Pro" price="$29" features="10 projects, Priority support, API access" url="/signup/pro"]`

### Image Galleries

```php
<?php // snippets/gallery.php ?>
<div class="gallery" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
    <?php foreach (explode(',', $images ?? '') as $img): ?>
        <img src="<?= $ava->e(trim($img)) ?>" alt="" loading="lazy">
    <?php endforeach; ?>
</div>
```

Usage: `[snippet name="gallery" images="@uploads:photo1.jpg, @uploads:photo2.jpg, @uploads:photo3.jpg"]`

### Third-Party Widgets

```php
<?php // snippets/youtube.php ?>
<div class="video-embed" style="aspect-ratio: 16/9;">
    <iframe 
        src="https://www.youtube.com/embed/<?= $ava->e($id ?? '') ?>" 
        frameborder="0" 
        allowfullscreen 
        style="width: 100%; height: 100%;">
    </iframe>
</div>
```

Usage: `[snippet name="youtube" id="dQw4w9WgXcQ"]`

### Alert/Notice Boxes

```php
<?php // snippets/notice.php ?>
<?php
$types = ['info' => '💡', 'warning' => '⚠️', 'success' => '✅', 'error' => '❌'];
$icon = $types[$type ?? 'info'] ?? '💡';
?>
<div class="notice notice-<?= $ava->e($type ?? 'info') ?>">
    <span class="notice-icon"><?= $icon ?></span>
    <div class="notice-content"><?= $content ?></div>
</div>
```

Usage:
```markdown
[snippet name="notice" type="warning"]
This feature is experimental. Use with caution.
[/snippet]
```

## Custom Shortcodes

For simpler shortcodes that don't need a file, register them in `app/shortcodes.php`:

```php
<?php
// app/shortcodes.php

use Ava\Application;

$shortcodes = Application::getInstance()->shortcodes();

// Simple greeting
$shortcodes->register('greeting', function (array $attrs) {
    $name = $attrs['name'] ?? 'friend';
    return "Hello, " . htmlspecialchars($name) . "!";
});

// Recent posts list
$shortcodes->register('recent_posts', function (array $attrs) {
    $app = Application::getInstance();
    $count = (int) ($attrs['count'] ?? 5);
    
    $posts = $app->query()
        ->type('post')
        ->published()
        ->orderBy('date', 'desc')
        ->perPage($count)
        ->get();
    
    $html = '<ul class="recent-posts">';
    foreach ($posts as $post) {
        $url = $app->router()->urlFor('post', $post->slug());
        $html .= '<li><a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($post->title()) . '</a></li>';
    }
    $html .= '</ul>';
    
    return $html;
});
```

## How Processing Works

1. Your Markdown is converted to HTML
2. Shortcodes are processed in the HTML output
3. The final result is sent to the browser

This means shortcodes can safely output raw HTML—it won't be escaped.

## Security

- **Path safety:** Snippet names can't contain `..` or `/`—no directory traversal
- **Disable snippets:** Set `security.shortcodes.allow_php_snippets` to `false`
- **Unknown shortcodes:** Left as-is in the output (no errors thrown)
- **Escaping:** Use `htmlspecialchars()` or `$ava->e()` for user-provided values
