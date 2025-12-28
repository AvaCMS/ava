<?php
/**
 * Admin Sidebar Partial
 * 
 * Required variables:
 * - $admin_url: Admin base URL
 * - $content: Content stats array
 * - $taxonomies: Taxonomy counts
 * - $taxonomyConfig: Taxonomy configuration
 * - $customPages: Plugin pages array
 * - $version: Ava version
 * - $user: Current user email
 * - $activePage: Current page identifier (e.g., 'dashboard', 'themes', 'lint')
 */
$activePage = $activePage ?? '';
?>
<div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="logo">
        <h1>✨ Ava <span class="version-badge">v<?= htmlspecialchars($version ?? '1.0') ?></span></h1>
    </div>
    <nav class="nav">
        <div class="nav-section">Overview</div>
        <a href="<?= $admin_url ?>" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">dashboard</span>
            Dashboard
        </a>

        <div class="nav-section">Content</div>
        <?php foreach ($content as $type => $stats): ?>
        <a href="<?= $admin_url ?>/content/<?= $type ?>" class="nav-item <?= $activePage === 'content-' . $type ? 'active' : '' ?>">
            <span class="material-symbols-rounded"><?= $type === 'page' ? 'description' : 'article' ?></span>
            <?= ucfirst($type) ?>s
            <span class="nav-count"><?= $stats['total'] ?></span>
        </a>
        <?php endforeach; ?>

        <div class="nav-section">Taxonomies</div>
        <?php foreach ($taxonomies as $tax => $count): 
            $taxConfig = $taxonomyConfig[$tax] ?? [];
        ?>
        <a href="<?= $admin_url ?>/taxonomy/<?= $tax ?>" class="nav-item <?= $activePage === 'taxonomy-' . $tax ? 'active' : '' ?>">
            <span class="material-symbols-rounded"><?= ($taxConfig['hierarchical'] ?? false) ? 'folder' : 'sell' ?></span>
            <?= htmlspecialchars($taxConfig['label'] ?? ucfirst($tax)) ?>
            <span class="nav-count"><?= $count ?></span>
        </a>
        <?php endforeach; ?>

        <div class="nav-section">Tools</div>
        <a href="<?= $admin_url ?>/lint" class="nav-item <?= $activePage === 'lint' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">check_circle</span>
            Lint Content
        </a>
        <a href="<?= $admin_url ?>/shortcodes" class="nav-item <?= $activePage === 'shortcodes' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">code</span>
            Shortcodes
        </a>
        <a href="<?= $admin_url ?>/logs" class="nav-item <?= $activePage === 'logs' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">history</span>
            Admin Logs
        </a>
        <a href="<?= $admin_url ?>/themes" class="nav-item <?= $activePage === 'themes' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">palette</span>
            Themes
        </a>
        <a href="<?= $admin_url ?>/system" class="nav-item <?= $activePage === 'system' ? 'active' : '' ?>">
            <span class="material-symbols-rounded">dns</span>
            System Info
        </a>

        <?php if (!empty($customPages)): ?>
        <div class="nav-section">Plugins</div>
        <?php foreach ($customPages as $slug => $page): ?>
        <a href="<?= $admin_url ?>/<?= htmlspecialchars($slug) ?>" class="nav-item <?= $activePage === $slug ? 'active' : '' ?>">
            <span class="material-symbols-rounded"><?= htmlspecialchars($page['icon'] ?? 'extension') ?></span>
            <?= htmlspecialchars($page['label'] ?? ucfirst($slug)) ?>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <span class="material-symbols-rounded">person</span>
            <?= htmlspecialchars($user ?? 'Admin') ?>
        </div>
        <a href="<?= $admin_url ?>/logout">
            <span class="material-symbols-rounded">logout</span>
            Sign Out
        </a>
    </div>
</aside>
