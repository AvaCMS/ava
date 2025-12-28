<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logs · Ava Admin</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>✨</text></svg>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">
    <link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
<?php $activePage = 'logs'; ?>
<?php include __DIR__ . '/_sidebar.php'; ?>

<div class="layout">
    <main class="main">
        <div class="mobile-header">
            <button class="menu-btn" onclick="toggleSidebar()">
                <span class="material-symbols-rounded">menu</span>
            </button>
            <h1>Admin Logs</h1>
        </div>

        <div class="header">
            <h2>
                <span class="material-symbols-rounded">history</span>
                Admin Logs
            </h2>
            <div class="header-actions">
                <a href="https://adamgreenough.github.io/ava/" target="_blank" class="btn btn-secondary btn-sm">
                    <span class="material-symbols-rounded">menu_book</span>
                    <span class="hide-mobile">Docs</span>
                </a>
                <a href="<?= htmlspecialchars($site['url']) ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <span class="material-symbols-rounded">open_in_new</span>
                    <span class="hide-mobile">View Site</span>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">list_alt</span>
                    Recent Activity
                </span>
                <span class="badge badge-muted"><?= count($logs) ?> entries</span>
            </div>
            <?php if (!empty($logs)): ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Level</th>
                            <th>Message</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $levelClass = match(strtoupper($log['level'])) {
                                'ERROR' => 'badge-danger',
                                'WARNING' => 'badge-warning',
                                'INFO' => 'badge-success',
                                default => 'badge-muted',
                            };
                            // Extract user agent short name
                            $ua = $log['user_agent'] ?? '';
                            $uaShort = 'Unknown';
                            if (preg_match('/Firefox\/[\d.]+/', $ua)) $uaShort = 'Firefox';
                            elseif (preg_match('/Chrome\/[\d.]+/', $ua)) $uaShort = 'Chrome';
                            elseif (preg_match('/Safari\/[\d.]+/', $ua) && !str_contains($ua, 'Chrome')) $uaShort = 'Safari';
                            elseif (preg_match('/Edge\/[\d.]+/', $ua)) $uaShort = 'Edge';
                            elseif (!empty($ua)) $uaShort = 'Other';
                        ?>
                        <tr>
                            <td>
                                <div class="text-sm"><?= htmlspecialchars($log['timestamp']) ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $levelClass ?>"><?= htmlspecialchars($log['level']) ?></span>
                            </td>
                            <td>
                                <span class="log-message"><?= htmlspecialchars($log['message']) ?></span>
                            </td>
                            <td>
                                <code class="text-xs"><?= htmlspecialchars($log['ip'] ?? '—') ?></code>
                            </td>
                            <td>
                                <span class="text-secondary text-xs" title="<?= htmlspecialchars($ua) ?>"><?= $uaShort ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <span class="material-symbols-rounded">history</span>
                <p>No admin activity logged yet.</p>
                <span class="text-secondary text-sm">Logs are created for logins, logouts, and lint checks.</span>
            </div>
            <?php endif; ?>
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
