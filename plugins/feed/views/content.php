<?php
/**
 * RSS Feed Plugin Admin View - Content Only
 * 
 * This file contains ONLY the main content for the RSS feed admin page.
 * The admin layout (header, sidebar, footer) is provided by the core.
 * 
 * Available variables:
 * - $baseUrl: Site base URL
 * - $types: Array of content type names
 * - $feeds: Array of feed stats per type (count, total)
 * - $totalItems: Total indexable items
 * - $config: Feed configuration
 * - $app: Application instance
 */
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">
            <span class="material-symbols-rounded">article</span>
            Feed Items
        </div>
        <div class="stat-value"><?= min($totalItems, $config['items_per_feed']) ?></div>
        <div class="stat-meta">In main feed</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">
            <span class="material-symbols-rounded">folder</span>
            Type Feeds
        </div>
        <div class="stat-value"><?= count($types) ?></div>
        <div class="stat-meta">Per content type</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">
            <span class="material-symbols-rounded">numbers</span>
            Items/Feed
        </div>
        <div class="stat-value"><?= $config['items_per_feed'] ?></div>
        <div class="stat-meta">Maximum</div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <span class="material-symbols-rounded">list</span>
                Available Feeds
            </span>
        </div>
        <div class="card-body">
            <div class="list-item">
                <span class="list-label">
                    <span class="material-symbols-rounded">rss_feed</span>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/feed.xml" target="_blank">feed.xml</a>
                </span>
                <span class="badge badge-accent">Main</span>
            </div>
            <?php foreach ($types as $type): ?>
            <div class="list-item">
                <span class="list-label">
                    <span class="material-symbols-rounded">description</span>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/feed/<?= $type ?>.xml" target="_blank">feed/<?= $type ?>.xml</a>
                </span>
                <span class="list-value"><?= $feeds[$type]['count'] ?> items</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <span class="material-symbols-rounded">code</span>
                Add to Your Site
            </span>
        </div>
        <div class="card-body">
            <p class="text-secondary text-sm" style="margin-bottom: var(--sp-3);">
                Add this to your theme's <code>&lt;head&gt;</code>:
            </p>
            <pre style="background: var(--bg-surface); padding: var(--sp-3); border-radius: var(--radius-md); overflow-x: auto; font-size: var(--text-xs);">&lt;link rel="alternate" type="application/rss+xml"
      title="<?= htmlspecialchars($app->config('site.name')) ?>"
      href="<?= htmlspecialchars($baseUrl) ?>/feed.xml"&gt;</pre>
        </div>
    </div>
</div>

<div class="card mt-5">
    <div class="card-header">
        <span class="card-title">
            <span class="material-symbols-rounded">settings</span>
            Configuration
        </span>
    </div>
    <div class="card-body">
        <p class="text-secondary text-sm" style="margin-bottom: var(--sp-4);">
            Configure feed settings in <code>app/config/ava.php</code>:
        </p>
        <pre style="background: var(--bg-surface); padding: var(--sp-4); border-radius: var(--radius-md); overflow-x: auto; font-size: var(--text-sm);">'feed' => [
    'enabled' => true,
    'items_per_feed' => 20,
    'full_content' => false,  // true = full HTML, false = excerpt
    'types' => null,          // null = all, or ['post', 'page']
],</pre>
    </div>
</div>
