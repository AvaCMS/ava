<?php
/**
 * Sitemap Plugin Admin View - Content Only
 * 
 * This file contains ONLY the main content for the sitemap admin page.
 * The admin layout (header, sidebar, footer) is provided by the core.
 * 
 * Available variables:
 * - $baseUrl: Site base URL
 * - $types: Array of content type names
 * - $stats: Array of stats per type (indexable, noindex, total)
 * - $totalUrls: Total indexable URLs
 */
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">
            <span class="material-symbols-rounded">link</span>
            Total URLs
        </div>
        <div class="stat-value"><?= $totalUrls ?></div>
        <div class="stat-meta">In sitemap</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">
            <span class="material-symbols-rounded">folder</span>
            Sitemaps
        </div>
        <div class="stat-value"><?= count($types) ?></div>
        <div class="stat-meta">Per content type</div>
    </div>
</div>

<div class="grid grid-2">
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <span class="material-symbols-rounded">list</span>
                Sitemap Files
            </span>
        </div>
        <div class="card-body">
            <div class="list-item">
                <span class="list-label">
                    <span class="material-symbols-rounded">folder_open</span>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/sitemap.xml" target="_blank">sitemap.xml</a>
                </span>
                <span class="badge badge-accent">Index</span>
            </div>
            <?php foreach ($types as $type): ?>
            <div class="list-item">
                <span class="list-label">
                    <span class="material-symbols-rounded">description</span>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/sitemap-<?= $type ?>.xml" target="_blank">sitemap-<?= $type ?>.xml</a>
                </span>
                <span class="list-value"><?= $stats[$type]['indexable'] ?> URLs</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <span class="material-symbols-rounded">analytics</span>
                Content Status
            </span>
        </div>
        <div class="card-body">
            <?php foreach ($stats as $type => $typeStat): ?>
            <div class="list-item">
                <span class="list-label"><?= ucfirst($type) ?>s</span>
                <span class="list-value">
                    <span class="text-success"><?= $typeStat['indexable'] ?></span>
                    <?php if ($typeStat['noindex'] > 0): ?>
                    / <span class="text-warning"><?= $typeStat['noindex'] ?> noindex</span>
                    <?php endif; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <span class="card-title">
            <span class="material-symbols-rounded">help</span>
            Reference
        </span>
    </div>
        <div class="table-wrap">
            <table class="table table-sm">
                <tbody>
                    <tr>
                        <td style="width: 160px;"><strong>Sitemap Index</strong></td>
                        <td><code class="text-xs">/sitemap.xml</code></td>
                    </tr>
                    <tr>
                        <td><strong>Per-type Sitemaps</strong></td>
                        <td><code class="text-xs">/sitemap-{type}.xml</code></td>
                    </tr>
                    <tr>
                        <td><strong>robots.txt</strong></td>
                        <td>URL auto-added on index rebuild</td>
                    </tr>
                    <tr>
                        <td><strong>Exclude Content</strong></td>
                        <td><code class="text-xs">noindex: true</code> in frontmatter</td>
                    </tr>
                    <tr>
                        <td><strong>Disable Plugin</strong></td>
                        <td><code class="text-xs">'sitemap' => ['enabled' => false]</code> in ava.php</td>
                    </tr>
                </tbody>
            </table>
        </div>
</div>
