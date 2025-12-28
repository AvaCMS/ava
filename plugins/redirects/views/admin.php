<?php
/**
 * Redirects Plugin Admin View
 */

$admin_url = $app->config('admin.path', '/admin');
$baseUrl = rtrim($app->config('site.base_url', ''), '/');
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
    <title>Redirects - Ava Admin</title>
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
            <?php foreach ($content as $ctype => $typeStats): ?>
            <a href="<?= $admin_url ?>/content/<?= $ctype ?>" class="nav-item">
                <span class="material-symbols-rounded"><?= $ctype === 'page' ? 'description' : 'article' ?></span>
                <?= ucfirst($ctype) ?>s
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
            <a href="<?= $admin_url ?>/<?= htmlspecialchars($slug) ?>" class="nav-item<?= $slug === 'redirects' ? ' active' : '' ?>">
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

        <?php if ($message): ?>
        <div class="alert alert-success">
            <span class="material-symbols-rounded">check_circle</span>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <span class="material-symbols-rounded">error</span>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($jsonError): ?>
        <div class="alert alert-danger">
            <span class="material-symbols-rounded">warning</span>
            <div>
                <strong>Malformed redirects file</strong><br>
                <?= htmlspecialchars($jsonError) ?><br>
                <code style="font-size: var(--text-xs); opacity: 0.8;"><?= htmlspecialchars($storagePath) ?></code>
            </div>
        </div>
        <?php endif; ?>

        <div class="header">
            <h2>
                <span class="material-symbols-rounded">swap_horiz</span>
                Redirects & Status Responses
            </h2>
            <div class="header-actions">
                <span class="badge badge-muted"><?= count($redirects) ?> entr<?= count($redirects) !== 1 ? 'ies' : 'y' ?></span>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <span class="material-symbols-rounded">add</span>
                        Add Entry
                    </span>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $admin_url ?>/redirects" id="redirectForm">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div style="margin-bottom: var(--sp-4);">
                            <label class="text-sm text-secondary" style="display: block; margin-bottom: var(--sp-2);">From URL</label>
                            <input type="text" name="from" placeholder="/old-path" required
                                   style="width: 100%; padding: var(--sp-2) var(--sp-3); background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-md); color: var(--text); font-size: var(--text-sm);">
                            <span class="text-xs text-tertiary">Path relative to site root (e.g., /old-page)</span>
                        </div>

                        <div style="margin-bottom: var(--sp-4);">
                            <label class="text-sm text-secondary" style="display: block; margin-bottom: var(--sp-2);">Response Type</label>
                            <select name="code" id="codeSelect" onchange="toggleDestination()" style="width: 100%; padding: var(--sp-2) var(--sp-3); background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-md); color: var(--text); font-size: var(--text-sm);">
                                <optgroup label="Redirects (require destination)">
                                    <?php foreach ($statusCodes as $code => $info): ?>
                                        <?php if ($info['redirect']): ?>
                                        <option value="<?= $code ?>" data-redirect="1"><?= $code ?> - <?= htmlspecialchars($info['label']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Status Responses (no destination)">
                                    <?php foreach ($statusCodes as $code => $info): ?>
                                        <?php if (!$info['redirect']): ?>
                                        <option value="<?= $code ?>" data-redirect="0"><?= $code ?> - <?= htmlspecialchars($info['label']) ?></option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <span class="text-xs text-tertiary" id="codeDescription"><?= htmlspecialchars($statusCodes[301]['description']) ?></span>
                        </div>

                        <div style="margin-bottom: var(--sp-4);" id="destinationField">
                            <label class="text-sm text-secondary" style="display: block; margin-bottom: var(--sp-2);">To URL</label>
                            <input type="text" name="to" id="toInput" placeholder="/new-path or https://..."
                                   style="width: 100%; padding: var(--sp-2) var(--sp-3); background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-md); color: var(--text); font-size: var(--text-sm);">
                            <span class="text-xs text-tertiary">Internal path or full URL</span>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-rounded">add</span>
                            Add Entry
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <span class="card-title">
                        <span class="material-symbols-rounded">info</span>
                        About
                    </span>
                </div>
                <div class="card-body">
                    <div class="list-item">
                        <span class="list-label">Storage File</span>
                        <span class="list-value text-sm"><code style="font-size: var(--text-xs);"><?= htmlspecialchars(str_replace($app->path(''), '', $storagePath)) ?></code></span>
                    </div>
                    
                    <p class="text-secondary text-sm" style="margin-top: var(--sp-4); margin-bottom: var(--sp-3);">
                        <strong>Status Codes:</strong>
                    </p>
                    
                    <?php foreach ($statusCodes as $code => $info): ?>
                    <div class="list-item" style="padding: var(--sp-2) 0;">
                        <span class="list-label">
                            <span class="badge <?= $info['redirect'] ? ($code === 301 || $code === 308 ? 'badge-success' : 'badge-warning') : 'badge-danger' ?>"><?= $code ?></span>
                        </span>
                        <span class="list-value text-sm text-secondary">
                            <?= htmlspecialchars($info['label']) ?>
                            <?= $info['redirect'] ? '' : '<span class="text-xs text-tertiary">(no dest.)</span>' ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    
                    <p class="text-secondary text-sm" style="margin-top: var(--sp-4);">
                        <strong>Tip:</strong> You can also edit the JSON file directly. Use <code>redirect_from</code> in content frontmatter for content-level redirects.
                    </p>
                </div>
            </div>
        </div>

        <?php if (!empty($redirects)): ?>
        <div class="card mt-5">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">list</span>
                    Active Entries
                </span>
                <span class="badge badge-muted"><?= count($redirects) ?></span>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Response</th>
                            <th>To</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($redirects as $redirect): 
                            $code = (int) ($redirect['code'] ?? 301);
                            $codeInfo = $statusCodes[$code] ?? ['label' => 'Unknown', 'redirect' => true];
                            $isRedirect = $codeInfo['redirect'];
                            $badgeClass = $isRedirect ? ($code === 301 || $code === 308 ? 'badge-success' : 'badge-warning') : 'badge-danger';
                        ?>
                        <tr>
                            <td><code><?= htmlspecialchars($redirect['from']) ?></code></td>
                            <td>
                                <span class="badge <?= $badgeClass ?>"><?= $code ?></span>
                                <span class="text-xs text-tertiary"><?= htmlspecialchars($codeInfo['label']) ?></span>
                            </td>
                            <td>
                                <?php if ($isRedirect && !empty($redirect['to'])): ?>
                                    <?php if (str_starts_with($redirect['to'], 'http')): ?>
                                        <a href="<?= htmlspecialchars($redirect['to']) ?>" target="_blank" class="text-accent">
                                            <?= htmlspecialchars($redirect['to']) ?>
                                            <span class="material-symbols-rounded" style="font-size: 14px; vertical-align: middle;">open_in_new</span>
                                        </a>
                                    <?php else: ?>
                                        <code><?= htmlspecialchars($redirect['to']) ?></code>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-tertiary">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm text-tertiary"><?= htmlspecialchars($redirect['created'] ?? 'Unknown') ?></td>
                            <td>
                                <form method="POST" action="<?= $admin_url ?>/redirects" style="display: inline;">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="from" value="<?= htmlspecialchars($redirect['from']) ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Delete this entry?')">
                                        <span class="material-symbols-rounded">delete</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="card mt-5">
            <div class="empty-state">
                <span class="material-symbols-rounded">swap_horiz</span>
                <p>No entries configured</p>
                <span class="text-sm text-tertiary">Add your first redirect or status response above</span>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
const statusDescriptions = <?= json_encode(array_map(fn($c) => $c['description'], $statusCodes)) ?>;
const statusRedirects = <?= json_encode(array_map(fn($c) => $c['redirect'], $statusCodes)) ?>;

function toggleDestination() {
    const select = document.getElementById('codeSelect');
    const code = select.value;
    const destField = document.getElementById('destinationField');
    const toInput = document.getElementById('toInput');
    const descEl = document.getElementById('codeDescription');
    
    const isRedirect = statusRedirects[code] ?? true;
    
    destField.style.display = isRedirect ? 'block' : 'none';
    toInput.required = isRedirect;
    
    if (!isRedirect) {
        toInput.value = '';
    }
    
    if (statusDescriptions[code]) {
        descEl.textContent = statusDescriptions[code];
    }
}

function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.querySelector('.sidebar-backdrop').classList.toggle('open');
}

// Initialize on load
document.addEventListener('DOMContentLoaded', toggleDestination);
</script>
</body>
</html>
