<?php
/**
 * Sitemap Plugin Admin View
 */

$admin_url = $app->config('admin.path', '/admin');
$site = [
    'name' => $app->config('site.name'),
    'url' => $app->config('site.base_url'),
];

// Load content types for sidebar
$contentTypesFile = $app->path('app/config/content_types.php');
$contentTypesConfig = file_exists($contentTypesFile) ? require $contentTypesFile : [];

// Get content stats
$repository = $app->repository();
$content = [];
foreach ($repository->types() as $type) {
    $items = $repository->all($type);
    $content[$type] = [
        'total' => count($items),
        'published' => count(array_filter($items, fn($i) => $i->isPublished())),
        'draft' => count(array_filter($items, fn($i) => $i->isDraft())),
    ];
}

// Get taxonomy stats
$taxonomyFile = $app->path('app/config/taxonomies.php');
$taxonomyConfig = file_exists($taxonomyFile) ? require $taxonomyFile : [];
$taxonomies = [];
foreach (array_keys($taxonomyConfig) as $tax) {
    $taxonomies[$tax] = count($repository->terms($tax));
}

// Get custom pages for sidebar
$customPages = \Ava\Plugins\Hooks::apply('admin.register_pages', [], $app);

// Current user
$user = $_SESSION['ava_admin_user'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemap - Ava Admin</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>✨</text></svg>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">
    <link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
<div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <h1>✨ Ava <span class="version-badge">v1.0</span></h1>
        </div>
        <nav class="nav">
            <div class="nav-section">Overview</div>
            <a href="<?= $admin_url ?>" class="nav-item">
                <span class="material-symbols-rounded">dashboard</span>
                Dashboard
            </a>

            <div class="nav-section">Content</div>
            <?php foreach ($content as $type => $typeStats): ?>
            <a href="<?= $admin_url ?>/content/<?= $type ?>" class="nav-item">
                <span class="material-symbols-rounded"><?= $type === 'page' ? 'description' : 'article' ?></span>
                <?= ucfirst($type) ?>s
                <span class="nav-count"><?= $typeStats['total'] ?></span>
            </a>
            <?php endforeach; ?>

            <div class="nav-section">Taxonomies</div>
            <?php foreach ($taxonomies as $tax => $count): 
                $taxConfig = $taxonomyConfig[$tax] ?? [];
            ?>
            <a href="<?= $admin_url ?>/taxonomy/<?= $tax ?>" class="nav-item">
                <span class="material-symbols-rounded"><?= ($taxConfig['hierarchical'] ?? false) ? 'folder' : 'sell' ?></span>
                <?= htmlspecialchars($taxConfig['label'] ?? ucfirst($tax)) ?>
                <span class="nav-count"><?= $count ?></span>
            </a>
            <?php endforeach; ?>

            <div class="nav-section">Tools</div>
            <a href="<?= $admin_url ?>/lint" class="nav-item">
                <span class="material-symbols-rounded">check_circle</span>
                Lint Content
            </a>
            <a href="<?= $admin_url ?>/shortcodes" class="nav-item">
                <span class="material-symbols-rounded">code</span>
                Shortcodes
            </a>
            <a href="<?= $admin_url ?>/logs" class="nav-item">
                <span class="material-symbols-rounded">history</span>
                Admin Logs
            </a>
            <a href="<?= $admin_url ?>/system" class="nav-item">
                <span class="material-symbols-rounded">dns</span>
                System Info
            </a>

            <?php if (!empty($customPages)): ?>
            <div class="nav-section">Plugins</div>
            <?php foreach ($customPages as $slug => $page): ?>
            <a href="<?= $admin_url ?>/<?= htmlspecialchars($slug) ?>" class="nav-item<?= $slug === 'sitemap' ? ' active' : '' ?>">
                <span class="material-symbols-rounded"><?= htmlspecialchars($page['icon'] ?? 'extension') ?></span>
                <?= htmlspecialchars($page['label'] ?? ucfirst($slug)) ?>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <span class="material-symbols-rounded">person</span>
                <?= htmlspecialchars($user) ?>
            </div>
            <a href="<?= $admin_url ?>/logout">
                <span class="material-symbols-rounded">logout</span>
                Sign Out
            </a>
        </div>
    </aside>

    <main class="main">
        <div class="mobile-header">
            <button class="menu-btn" onclick="toggleSidebar()">
                <span class="material-symbols-rounded">menu</span>
            </button>
            <h1>✨ Ava</h1>
        </div>

        <div class="header">
            <h2>
                <span class="material-symbols-rounded">map</span>
                Sitemap
            </h2>
            <div class="header-actions">
                <a href="<?= htmlspecialchars($baseUrl) ?>/sitemap.xml" target="_blank" class="btn btn-primary btn-sm">
                    <span class="material-symbols-rounded">open_in_new</span>
                    View Sitemap
                </a>
            </div>
        </div>

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

        <div class="card mt-5">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">help</span>
                    Configuration
                </span>
            </div>
            <div class="card-body">
                <p class="text-secondary text-sm" style="margin-bottom: var(--sp-4);">
                    Configure sitemap settings in <code>app/config/ava.php</code>:
                </p>
                <pre style="background: var(--bg-surface); padding: var(--sp-4); border-radius: var(--radius-md); overflow-x: auto; font-size: var(--text-sm);">'sitemap' => [
    'enabled' => true,
    'changefreq' => [
        'page' => 'monthly',
        'post' => 'weekly',
    ],
    'priority' => [
        'page' => '0.8',
        'post' => '0.6',
    ],
],</pre>
                <p class="text-secondary text-sm" style="margin-top: var(--sp-4);">
                    To exclude content from the sitemap, add <code>noindex: true</code> to the frontmatter.
                </p>
            </div>
        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.querySelector('.sidebar-backdrop').classList.toggle('open');
}
</script>
</body>
</html>
