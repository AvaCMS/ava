<?php
/**
 * Content List - Content Only View
 * 
 * Available variables:
 * - $type: Content type slug
 * - $typeConfig: Content type configuration
 * - $items: Array of content items
 * - $pagination: Pagination data
 * - $stats: Content stats
 * - $allContent: All content stats for sidebar
 * - $taxonomyConfig: Taxonomy configuration
 * - $taxonomyTerms: Terms for each taxonomy
 * - $routes: Routes array
 * - $site: Site configuration
 * - $filters: Current filter/sort values (status, sort, dir, q)
 * - $indexStale: Whether the content index is stale
 */

// Get preview token for draft links
$previewToken = $ava->config('security.preview_token');

// Get current filters
$currentStatus = $filters['status'] ?? '';
$currentSort = $filters['sort'] ?? 'date';
$currentDir = $filters['dir'] ?? 'desc';
$currentSearch = $filters['q'] ?? '';

// Helper to build URL with current filters preserved
$buildFilterUrl = function($params = []) use ($admin_url, $type, $currentStatus, $currentSort, $currentDir, $currentSearch, $pagination) {
    $base = $admin_url . '/content/' . urlencode($type);
    $query = array_merge([
        'status' => $currentStatus,
        'sort' => $currentSort,
        'dir' => $currentDir,
        'q' => $currentSearch,
        'page' => $pagination['page'],
    ], $params);
    
    // Remove default/empty values
    if ($query['status'] === '') unset($query['status']);
    if ($query['sort'] === 'date' && $query['dir'] === 'desc') {
        unset($query['sort'], $query['dir']);
    }
    if ($query['q'] === '') unset($query['q']);
    if (($query['page'] ?? 1) === 1) unset($query['page']);
    
    return $query ? $base . '?' . http_build_query($query) : $base;
};

// Helper to generate URL path for a content item
// Mirrors the Indexer::generateUrl logic for consistent URL generation
$generateUrlPath = function($item, $typeConfig, $app) {
    $urlConfig = $typeConfig['url'] ?? [];
    $urlType = $urlConfig['type'] ?? 'pattern';
    
    if ($urlType === 'hierarchical') {
        // Derive URL from file path structure
        $contentDir = $typeConfig['content_dir'] ?? $item->type();
        $contentBase = $app->configPath('content') . '/' . $contentDir;
        $filePath = $item->filePath();
        
        // Get relative path within content type directory
        $relativePath = '';
        if (str_starts_with($filePath, $contentBase)) {
            $relativePath = substr($filePath, strlen($contentBase) + 1);
        }
        
        // Remove .md extension and handle index files
        $pathParts = [];
        $parts = explode('/', $relativePath);
        foreach ($parts as $part) {
            if (str_ends_with($part, '.md')) {
                $part = substr($part, 0, -3);
            }
            if ($part !== 'index' && $part !== '_index' && $part !== '') {
                $pathParts[] = $part;
            }
        }
        $pathKey = implode('/', $pathParts);
        
        // Build URL with base
        $urlBase = $urlConfig['base'] ?? '/';
        if ($urlBase === '/') {
            return $pathKey === '' ? '/' : '/' . ltrim($pathKey, '/');
        } elseif ($pathKey === '') {
            return rtrim($urlBase, '/');
        } else {
            return rtrim($urlBase, '/') . '/' . $pathKey;
        }
    }
    
    // Pattern-based URL
    $pattern = $urlConfig['pattern'] ?? '/{slug}';
    
    $replacements = [
        '{slug}' => $item->slug(),
        '{id}' => $item->id() ?? '',
    ];
    
    // Date-based replacements
    $date = $item->date();
    if ($date) {
        $replacements['{yyyy}'] = $date->format('Y');
        $replacements['{mm}'] = $date->format('m');
        $replacements['{dd}'] = $date->format('d');
    }
    
    return str_replace(array_keys($replacements), array_values($replacements), $pattern);
};

// Get just the URL path for an item from routes (or generate for drafts)
$getContentPath = function($item) use ($routes, $typeConfig, $type, $generateUrlPath, $app) {
    $itemType = $item->type();
    $slug = $item->slug();
    
    // First check if it's in routes (published content)
    foreach ($routes['exact'] ?? [] as $routeUrl => $routeData) {
        if (($routeData['content_type'] ?? '') === $itemType && ($routeData['slug'] ?? '') === $slug) {
            return $routeUrl;
        }
    }
    
    // For drafts/unlisted, generate URL using proper logic
    return $generateUrlPath($item, $typeConfig, $app);
};

// Build full URL for content item (with preview support for drafts)
$getContentUrl = function($item, $forcePreview = false) use ($site, $previewToken, $getContentPath) {
    $path = $getContentPath($item);
    if (!$path) {
        return null;
    }
    
    $url = rtrim($site['url'], '/') . $path;
    
    // For drafts, add preview token if available
    if (($item->isDraft() || $forcePreview) && $previewToken) {
        $url .= '?preview=1&token=' . urlencode($previewToken);
    }
    
    return $url;
};

$urlType = $typeConfig['url']['type'] ?? 'pattern';
$urlPattern = $typeConfig['url']['pattern'] ?? ($urlType === 'hierarchical' ? '/{slug}' : '/' . $type . '/{slug}');
$archiveUrl = $typeConfig['url']['archive'] ?? null;
$contentDir = $typeConfig['content_dir'] ?? $type . 's';
$taxonomiesForType = $typeConfig['taxonomies'] ?? [];

$formatBytes = function($bytes) {
    $bytes = $bytes ?? 0;
    $units = ['B', 'KB', 'MB'];
    $i = 0;
    while ($bytes >= 1024 && $i < 2) { $bytes /= 1024; $i++; }
    return round($bytes, 1) . ' ' . $units[$i];
};

// Check if any filters are active
$hasActiveFilters = $currentStatus !== '' || $currentSearch !== '' || $currentSort !== 'date' || $currentDir !== 'desc';
?>

<?php if ($indexStale): ?>
<div class="content-stale-notice">
    <span class="material-symbols-rounded">sync_problem</span>
    <span>Content index is out of date – <a href="<?= htmlspecialchars($admin_url) ?>">rebuild</a> for the most accurate results</span>
</div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="content-filter-bar">
    <form method="get" action="<?= htmlspecialchars($admin_url . '/content/' . urlencode($type)) ?>" class="content-filter-form">
        <!-- Search -->
        <div class="filter-search">
            <span class="material-symbols-rounded filter-search-icon">search</span>
            <input type="text" name="q" value="<?= htmlspecialchars($currentSearch) ?>" placeholder="Search <?= htmlspecialchars(strtolower($typeConfig['label'] ?? $type)) ?>..." class="form-control form-control-sm">
        </div>
        
        <!-- Status Filter -->
        <div class="filter-group">
            <label for="status-filter" class="filter-label">Status</label>
            <select name="status" id="status-filter" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="published" <?= $currentStatus === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $currentStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="unlisted" <?= $currentStatus === 'unlisted' ? 'selected' : '' ?>>Unlisted</option>
            </select>
        </div>
        
        <!-- Sort -->
        <div class="filter-group">
            <label for="sort-filter" class="filter-label">Sort by</label>
            <select name="sort" id="sort-filter" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="date" <?= $currentSort === 'date' ? 'selected' : '' ?>>Date</option>
                <option value="updated" <?= $currentSort === 'updated' ? 'selected' : '' ?>>Updated</option>
                <option value="title" <?= $currentSort === 'title' ? 'selected' : '' ?>>Title</option>
                <option value="status" <?= $currentSort === 'status' ? 'selected' : '' ?>>Status</option>
            </select>
        </div>
        
        <!-- Direction -->
        <div class="filter-group">
            <label for="dir-filter" class="filter-label">Order</label>
            <select name="dir" id="dir-filter" class="form-control form-control-sm" onchange="this.form.submit()">
                <?php if (in_array($currentSort, ['date', 'updated'], true)): ?>
                <option value="desc" <?= $currentDir === 'desc' ? 'selected' : '' ?>>Newest first</option>
                <option value="asc" <?= $currentDir === 'asc' ? 'selected' : '' ?>>Oldest first</option>
                <?php elseif ($currentSort === 'title'): ?>
                <option value="asc" <?= $currentDir === 'asc' ? 'selected' : '' ?>>A → Z</option>
                <option value="desc" <?= $currentDir === 'desc' ? 'selected' : '' ?>>Z → A</option>
                <?php else: ?>
                <option value="asc" <?= $currentDir === 'asc' ? 'selected' : '' ?>>Ascending</option>
                <option value="desc" <?= $currentDir === 'desc' ? 'selected' : '' ?>>Descending</option>
                <?php endif; ?>
            </select>
        </div>
        
        <!-- Submit for search (hidden, triggered by enter in search field) -->
        <button type="submit" class="btn btn-secondary btn-sm filter-submit">
            <span class="material-symbols-rounded">filter_list</span>
            <span class="btn-label">Filter</span>
        </button>
        
        <?php if ($hasActiveFilters): ?>
        <a href="<?= htmlspecialchars($admin_url . '/content/' . urlencode($type)) ?>" class="btn btn-secondary btn-sm filter-clear" title="Clear all filters">
            <span class="material-symbols-rounded">close</span>
            <span class="btn-label">Clear</span>
        </a>
        <?php endif; ?>
    </form>
    
    <div class="filter-results">
        <?= number_format($pagination['totalItems']) ?> <?= $pagination['totalItems'] === 1 ? 'item' : 'items' ?>
        <?php if ($hasActiveFilters): ?><span class="text-tertiary">(filtered)</span><?php endif; ?>
    </div>
</div>

<div class="content-layout">
    <!-- Content List -->
    <div class="card content-main">
        <?php if (!empty($items)): ?>
        <div class="table-wrap">
            <table class="table table-responsive">
                <thead>
                    <tr>
                        <th><a href="<?= htmlspecialchars($buildFilterUrl(['sort' => 'title', 'dir' => ($currentSort === 'title' && $currentDir === 'asc') ? 'desc' : 'asc', 'page' => 1])) ?>" class="th-sortable <?= $currentSort === 'title' ? 'active' : '' ?>">Title <?php if ($currentSort === 'title'): ?><span class="material-symbols-rounded th-sort-icon"><?= $currentDir === 'asc' ? 'arrow_upward' : 'arrow_downward' ?></span><?php endif; ?></a></th>
                        <th>File</th>
                        <th>URL</th>
                        <th><a href="<?= htmlspecialchars($buildFilterUrl(['sort' => 'date', 'dir' => ($currentSort === 'date' && $currentDir === 'desc') ? 'asc' : 'desc', 'page' => 1])) ?>" class="th-sortable <?= $currentSort === 'date' ? 'active' : '' ?>">Date <?php if ($currentSort === 'date'): ?><span class="material-symbols-rounded th-sort-icon"><?= $currentDir === 'asc' ? 'arrow_upward' : 'arrow_downward' ?></span><?php endif; ?></a></th>
                        <th>Size</th>
                        <th><a href="<?= htmlspecialchars($buildFilterUrl(['sort' => 'status', 'dir' => ($currentSort === 'status' && $currentDir === 'asc') ? 'desc' : 'asc', 'page' => 1])) ?>" class="th-sortable <?= $currentSort === 'status' ? 'active' : '' ?>">Status <?php if ($currentSort === 'status'): ?><span class="material-symbols-rounded th-sort-icon"><?= $currentDir === 'asc' ? 'arrow_upward' : 'arrow_downward' ?></span><?php endif; ?></a></th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): 
                        $itemUrl = $getContentUrl($item);
                        $itemPath = $getContentPath($item);
                        $fileSize = file_exists($item->filePath()) ? filesize($item->filePath()) : 0;
                        $isDraft = $item->isDraft();
                        
                        // Compute edit URL early so we can link the title
                        $fullPath = $item->filePath();
                        $typeDir = $typeConfig['content_dir'] ?? $type . 's';
                        
                        // Find the type directory in the path and extract everything after it
                        $marker = '/' . $typeDir . '/';
                        $pos = strrpos($fullPath, $marker);
                        if ($pos !== false) {
                            $relPath = substr($fullPath, $pos + strlen($marker));
                        } else {
                            $relPath = basename($fullPath);
                        }
                        
                        // File param is just the path within the type dir, without .md
                        // Use pipe as separator for cleaner URLs (no encoding needed)
                        $editFile = str_replace('/', '|', preg_replace('/\.md$/', '', $relPath));
                        $editUrl = $admin_url . '/content/' . htmlspecialchars($type) . '/edit?file=' . htmlspecialchars($editFile);
                    ?>
                    <tr>
                        <td data-label="Title">
                            <?php 
                            $title = $item->title();
                            $truncatedTitle = mb_strlen($title) > 40 ? mb_substr($title, 0, 40) . '…' : $title;
                            ?>
                            <a href="<?= $editUrl ?>" class="table-title-link" <?= mb_strlen($title) > 40 ? 'title="' . htmlspecialchars($title) . '"' : '' ?>><?= htmlspecialchars($truncatedTitle) ?></a>
                            <div class="table-mobile-meta">
                                <span class="mobile-meta-status badge <?= $item->isPublished() ? 'badge-success' : ($item->status() === 'draft' ? 'badge-warning' : 'badge-muted') ?>"><?= $item->status() ?></span>
                                <?php if ($item->date()): ?>
                                <span class="mobile-meta-date"><?= $item->date()->format('M j, Y') ?></span>
                                <?php endif; ?>
                                <code class="mobile-meta-path"><?= $itemPath ? htmlspecialchars($itemPath) : $relPath ?></code>
                            </div>
                        </td>
                        <td data-label="File">
                            <code class="text-xs"><?= htmlspecialchars($relPath) ?></code>
                        </td>
                        <td data-label="URL">
                            <code class="text-xs <?= $isDraft ? 'text-tertiary' : '' ?>"><?= $itemPath ? htmlspecialchars($itemPath) : '—' ?></code>
                        </td>
                        <td data-label="Date">
                            <?php if ($item->date()): ?>
                            <div class="text-sm"><?= $item->date()->format('M j, Y') ?></div>
                            <div class="text-xs text-tertiary"><?= $item->date()->format('H:i') ?></div>
                            <?php else: ?>
                            <span class="text-tertiary">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Size">
                            <div class="text-sm"><?= $formatBytes($fileSize) ?></div>
                        </td>
                        <td data-label="Status">
                            <span class="badge <?= $item->isPublished() ? 'badge-success' : ($item->status() === 'draft' ? 'badge-warning' : 'badge-muted') ?>">
                                <?= $item->status() ?>
                            </span>
                        </td>
                        <td data-label="Action" class="mobile-actions">
                            <div class="btn-group">
                                <a href="<?= $editUrl ?>" class="btn btn-xs btn-secondary" title="Edit">
                                    <span class="material-symbols-rounded">edit</span>
                                    <span class="btn-label">Edit</span>
                                </a>
                                <?php if ($itemUrl): ?>
                                <a href="<?= htmlspecialchars($itemUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-xs btn-secondary" title="<?= $isDraft ? 'Preview' : 'View' ?>">
                                    <span class="material-symbols-rounded"><?= $isDraft ? 'visibility' : 'open_in_new' ?></span>
                                    <span class="btn-label"><?= $isDraft ? 'Preview' : 'View' ?></span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="pagination">
            <div class="pagination-info">
                Showing <?= (($pagination['page'] - 1) * $pagination['perPage']) + 1 ?>–<?= min($pagination['page'] * $pagination['perPage'], $pagination['totalItems']) ?> of <?= number_format($pagination['totalItems']) ?>
            </div>
            <div class="pagination-controls">
                <?php if ($pagination['hasPrev']): ?>
                <a href="<?= htmlspecialchars($buildFilterUrl(['page' => 1])) ?>" class="btn btn-xs btn-secondary" title="First page">
                    <span class="material-symbols-rounded">first_page</span>
                </a>
                <a href="<?= htmlspecialchars($buildFilterUrl(['page' => $pagination['page'] - 1])) ?>" class="btn btn-xs btn-secondary" title="Previous page">
                    <span class="material-symbols-rounded">chevron_left</span>
                </a>
                <?php else: ?>
                <span class="btn btn-xs btn-secondary btn-disabled"><span class="material-symbols-rounded">first_page</span></span>
                <span class="btn btn-xs btn-secondary btn-disabled"><span class="material-symbols-rounded">chevron_left</span></span>
                <?php endif; ?>

                <span class="pagination-current">Page <?= $pagination['page'] ?> of <?= $pagination['totalPages'] ?></span>

                <?php if ($pagination['hasMore']): ?>
                <a href="<?= htmlspecialchars($buildFilterUrl(['page' => $pagination['page'] + 1])) ?>" class="btn btn-xs btn-secondary" title="Next page">
                    <span class="material-symbols-rounded">chevron_right</span>
                </a>
                <a href="<?= htmlspecialchars($buildFilterUrl(['page' => $pagination['totalPages']])) ?>" class="btn btn-xs btn-secondary" title="Last page">
                    <span class="material-symbols-rounded">last_page</span>
                </a>
                <?php else: ?>
                <span class="btn btn-xs btn-secondary btn-disabled"><span class="material-symbols-rounded">chevron_right</span></span>
                <span class="btn btn-xs btn-secondary btn-disabled"><span class="material-symbols-rounded">last_page</span></span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <?php $label = $typeConfig['label'] ?? ucfirst($type); ?>
        <div class="empty-state">
            <span class="material-symbols-rounded"><?= $type === 'page' ? 'description' : 'article' ?></span>
            <?php if ($hasActiveFilters): ?>
            <p>No matching content found</p>
            <p class="text-xs text-tertiary mt-2">Try adjusting your filters above</p>
            <?php else: ?>
            <p>No content yet</p>
            <a href="<?= htmlspecialchars($admin_url) ?>/content/<?= htmlspecialchars($type) ?>/create" class="btn btn-primary btn-sm mt-3">
                <span class="material-symbols-rounded">add</span>
                Create <?= htmlspecialchars($label) ?>
            </a>
            <p class="text-xs text-tertiary mt-3">or via CLI: <code>./ava make <?= $type ?> "Your Title"</code></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="config-sidebar">
        <!-- Stats -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">analytics</span>
                    Statistics
                </span>
            </div>
            <div class="card-body">
                <div class="list-item"><span class="list-label">Total</span><span class="list-value"><?= $allContent[$type]['total'] ?? 0 ?></span></div>
                <div class="list-item"><span class="list-label">Published</span><span class="list-value text-success"><?= $allContent[$type]['published'] ?? 0 ?></span></div>
                <div class="list-item"><span class="list-label">Drafts</span><span class="list-value text-warning"><?= $allContent[$type]['draft'] ?? 0 ?></span></div>
                <div class="list-item"><span class="list-label">Total Size</span><span class="list-value"><?= $formatBytes($stats['totalSize'] ?? 0) ?></span></div>
            </div>
        </div>

        <!-- Configuration -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">settings</span>
                    Configuration
                </span>
            </div>
            <div class="card-body">
                <div class="list-item"><span class="list-label">Directory</span><code>content/<?= htmlspecialchars($contentDir) ?>/</code></div>
                <div class="list-item"><span class="list-label">URL Type</span><span class="badge badge-muted"><?= htmlspecialchars($urlType) ?></span></div>
                <div class="list-item"><span class="list-label">Pattern</span><code class="text-xs"><?= htmlspecialchars($urlPattern) ?></code></div>
                <?php if ($archiveUrl): ?>
                <div class="list-item"><span class="list-label">Archive</span><code class="text-xs"><?= htmlspecialchars($archiveUrl) ?></code></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Templates -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">code</span>
                    Templates
                </span>
            </div>
            <div class="card-body">
                <div class="list-item"><span class="list-label">Single</span><code class="text-xs"><?= htmlspecialchars($typeConfig['templates']['single'] ?? $type . '.php') ?></code></div>
                <?php if (isset($typeConfig['templates']['archive'])): ?>
                <div class="list-item"><span class="list-label">Archive</span><code class="text-xs"><?= htmlspecialchars($typeConfig['templates']['archive']) ?></code></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($taxonomiesForType)): ?>
        <!-- Taxonomies -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">sell</span>
                    Taxonomies
                </span>
            </div>
            <div class="card-body">
                <?php foreach ($taxonomiesForType as $tax): 
                    $tc = $taxonomyConfig[$tax] ?? [];
                    $termCount = count($taxonomyTerms[$tax] ?? []);
                ?>
                <div class="list-item">
                    <span class="list-label">
                        <span class="material-symbols-rounded icon-xs"><?= ($tc['hierarchical'] ?? false) ? 'folder' : 'label' ?></span>
                        <?= htmlspecialchars($tc['label'] ?? ucfirst($tax)) ?>
                    </span>
                    <span class="badge badge-muted"><?= $termCount ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Options -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">tune</span>
                    Options
                </span>
            </div>
            <div class="card-body">
                <div class="list-item"><span class="list-label">Sorting</span><code class="text-xs"><?= htmlspecialchars($typeConfig['sorting'] ?? 'date_desc') ?></code></div>
                <?php if (isset($typeConfig['search'])): ?>
                <div class="list-item">
                    <span class="list-label">Searchable</span>
                    <span class="badge <?= ($typeConfig['search']['enabled'] ?? false) ? 'badge-success' : 'badge-muted' ?>">
                        <?= ($typeConfig['search']['enabled'] ?? false) ? 'Yes' : 'No' ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

