./ava rebuild

# Bundled Plugins

Ava comes with a few essential plugins to handle the boring stuff for you. They are installed by default but you can turn them on or off in your config.

## Sitemap

Automatically generates an XML sitemap for search engines like Google.

- **What it does:** Creates `sitemap.xml` so search engines can find all your pages.
- **How to use:** Just enable it in `app/config/ava.php`.
- **Customization:** You can exclude pages by adding `noindex: true` to their frontmatter.

## RSS Feed

Lets people subscribe to your blog using an RSS reader.

- **What it does:** Creates `feed.xml` with your latest posts.
- **How to use:** Enable it in `app/config/ava.php`.
- **Customization:** You can choose which content types to include (like just posts, or everything).

```php
'feed' => [
    'enabled' => true,
    'items_per_feed' => 20,
    'full_content' => false,  // true = full HTML, false = excerpt only
    'types' => null,          // null = all types, or ['post'] for specific types
],
```

### Adding to Your Theme

Add the feed link to your theme's `<head>`:

```html
<link rel="alternate" type="application/rss+xml" 
      title="My Site" 
      href="/feed.xml">
```

### Output Example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title>My Ava Site</title>
  <link>https://example.com</link>
  <description>Latest content from My Ava Site</description>
  <atom:link href="https://example.com/feed.xml" rel="self" type="application/rss+xml"/>
  <item>
    <title>My Latest Post</title>
    <link>https://example.com/blog/my-latest-post</link>
    <guid isPermaLink="true">https://example.com/blog/my-latest-post</guid>
    <pubDate>Mon, 20 Jan 2025 12:00:00 +0000</pubDate>
    <description>Post excerpt or full content...</description>
  </item>
</channel>
</rss>
```

---

## Redirects

Manage custom URL redirects through the admin interface.

- **What it does:** Redirects old URLs to new ones.
- **How to use:** Add a `redirects.json` file to your `storage/` folder, or use the admin dashboard if you have the plugin enabled.

### Features

- **Admin UI** — Add and remove redirects without editing files
- **301 and 302** — Support for permanent and temporary redirects
- **High priority** — Processed before content routing
- **Persistent storage** — Saved to `storage/redirects.json`
- **Admin page** — Manage redirects under Plugins → Redirects

### Enabling

```php
// app/config/ava.php
'plugins' => [
    'redirects',
],
```

### Usage

1. Navigate to Plugins → Redirects in the admin
2. Enter the source URL (e.g., `/old-page`)
3. Enter the destination URL (e.g., `/new-page` or `https://example.com`)
4. Select redirect type (301 permanent or 302 temporary)
5. Click "Add Redirect"

### When to Use

| Redirect Type | Use Case |
|---------------|----------|
| **301 Permanent** | Content moved permanently, SEO-friendly |
| **302 Temporary** | Temporary redirect, not cached |

### Comparison with Content Redirects

Ava supports two ways to redirect:

| Method | Best For |
|--------|----------|
| **Redirects Plugin** | External URLs, legacy paths, quick fixes |
| **`redirect_from` frontmatter** | Content that's been moved/renamed |

Using `redirect_from` in content:

```yaml
---
title: New Page Location
redirect_from:
  - /old-url
  - /another-old-url
---
```

### Storage Format

Redirects are stored in `storage/redirects.json`:

```json
[
  {
    "from": "/old-page",
    "to": "/new-page",
    "code": 301,
    "created": "2025-01-20 14:30:00"
  }
]
```

---

## Enabling All Bundled Plugins

```php
// app/config/ava.php
return [
    // ...
    
    'plugins' => [
        'sitemap',
        'feed',
        'redirects',
    ],
];
```

After enabling, rebuild the cache:

```bash
./ava rebuild
```

Then access the plugin admin pages at:
- `/admin/sitemap`
- `/admin/feeds`
- `/admin/redirects`
- `/admin/feeds`
