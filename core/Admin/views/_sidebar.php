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
<script>
    (function() {
        const theme = document.cookie.split('; ').find(row => row.startsWith('theme='))?.split('=')[1];
        if (theme && theme !== 'system') {
            document.documentElement.setAttribute('data-theme', theme);
        }
    })();
</script>

<aside class="app-sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
            <span class="material-symbols-rounded toggle-icon-open">left_panel_close</span>
            <span class="material-symbols-rounded toggle-icon-closed">left_panel_open</span>
        </button>
        <span class="sidebar-brand">Ava CMS</span>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-group">
            <a href="<?= $admin_url ?>" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>" data-tooltip="Dashboard" title="Dashboard">
                <span class="material-symbols-rounded">dashboard</span>
                <span class="nav-item-label">Dashboard</span>
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-group-label">Content</div>
            <?php foreach ($content as $type => $stats):
                $typeConfig = $contentTypes[$type] ?? [];
                $typeLabel = $typeConfig['label'] ?? ucfirst($type) . 's';
                $typeIcon = $typeConfig['icon'] ?? 'description';
            ?>
            <a href="<?= $admin_url ?>/content/<?= $type ?>" class="nav-item <?= $activePage === 'content-' . $type ? 'active' : '' ?>" data-tooltip="<?= htmlspecialchars($typeLabel) ?>" title="<?= htmlspecialchars($typeLabel) ?>">
                <span class="material-symbols-rounded"><?= htmlspecialchars($typeIcon) ?></span>
                <span class="nav-item-label"><?= htmlspecialchars($typeLabel) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="nav-group">
            <div class="nav-group-label">Taxonomies</div>
            <?php foreach ($taxonomies as $tax => $count): 
                $taxConfig = $taxonomyConfig[$tax] ?? [];
                $taxLabel = $taxConfig['label'] ?? ucfirst($tax);
                $taxIcon = $taxConfig['icon'] ?? 'tag';
            ?>
            <a href="<?= $admin_url ?>/taxonomy/<?= $tax ?>" class="nav-item <?= $activePage === 'taxonomy-' . $tax ? 'active' : '' ?>" data-tooltip="<?= htmlspecialchars($taxLabel) ?>" title="<?= htmlspecialchars($taxLabel) ?>">
                <span class="material-symbols-rounded"><?= htmlspecialchars($taxIcon) ?></span>
                <span class="nav-item-label"><?= htmlspecialchars($taxLabel) ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="nav-group">
            <div class="nav-group-label">Tools</div>
            <a href="<?= $admin_url ?>/media" class="nav-item <?= $activePage === 'media' ? 'active' : '' ?>" data-tooltip="Media" title="Media">
                <span class="material-symbols-rounded">image</span>
                <span class="nav-item-label">Media</span>
            </a>
            <a href="<?= $admin_url ?>/lint" class="nav-item <?= $activePage === 'lint' ? 'active' : '' ?>" data-tooltip="Lint Content" title="Lint Content">
                <span class="material-symbols-rounded">check_circle</span>
                <span class="nav-item-label">Lint Content</span>
            </a>
            <a href="<?= $admin_url ?>/logs" class="nav-item <?= $activePage === 'logs' ? 'active' : '' ?>" data-tooltip="Admin Logs" title="Admin Logs">
                <span class="material-symbols-rounded">history</span>
                <span class="nav-item-label">Admin Logs</span>
            </a>
            <a href="<?= $admin_url ?>/theme" class="nav-item <?= $activePage === 'theme' ? 'active' : '' ?>" data-tooltip="Theme" title="Theme">
                <span class="material-symbols-rounded">palette</span>
                <span class="nav-item-label">Theme</span>
            </a>
            <a href="<?= $admin_url ?>/system" class="nav-item <?= $activePage === 'system' ? 'active' : '' ?>" data-tooltip="System Info" title="System Info">
                <span class="material-symbols-rounded">dns</span>
                <span class="nav-item-label">System Info</span>
            </a>
        </div>

        <?php if (!empty($customPages)): ?>
        <div class="nav-group">
            <div class="nav-group-label">Plugins</div>
            <?php foreach ($customPages as $slug => $page): ?>
            <a href="<?= $admin_url ?>/<?= htmlspecialchars($slug) ?>" class="nav-item <?= $activePage === $slug ? 'active' : '' ?>" data-tooltip="<?= htmlspecialchars($page['label'] ?? ucfirst($slug)) ?>" title="<?= htmlspecialchars($page['label'] ?? ucfirst($slug)) ?>">
                <span class="material-symbols-rounded"><?= htmlspecialchars($page['icon'] ?? 'extension') ?></span>
                <span class="nav-item-label"><?= htmlspecialchars($page['label'] ?? ucfirst($slug)) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </nav>
</aside>
