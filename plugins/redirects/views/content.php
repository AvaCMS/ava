<?php
/**
 * Redirects Plugin Admin View - Content Only
 * 
 * This file contains ONLY the main content for the redirects admin page.
 * The admin layout (header, sidebar, footer) is provided by the core.
 * 
 * Available variables:
 * - $redirects: Array of redirect entries
 * - $csrf: CSRF token for forms
 * - $statusCodes: Array of supported status codes
 * - $storagePath: Path to redirects.json
 * - $admin_url: Admin base URL
 * - $app: Application instance
 * - $jsonError: JSON parsing error message (if any)
 */
?>

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

// Initialize on load
document.addEventListener('DOMContentLoaded', toggleDestination);
</script>
