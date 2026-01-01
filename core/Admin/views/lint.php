<!DOCTYPE html>
<html lang="en" data-accent="<?= htmlspecialchars($adminTheme ?? 'cyan') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lint Content · Ava Admin</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>✨</text></svg>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">
    <link rel="stylesheet" href="/assets/admin.css">
    <?php include __DIR__ . '/_theme.php'; ?>
</head>
<body>
<?php $activePage = 'lint'; ?>
<?php include __DIR__ . '/_sidebar.php'; ?>

<div class="layout">
    <main class="main">
        <div class="mobile-header">
            <button class="menu-btn" onclick="toggleSidebar()">
                <span class="material-symbols-rounded">menu</span>
            </button>
            <h1>Lint Content</h1>
        </div>

        <div class="header">
            <h2>
                <span class="material-symbols-rounded">check_circle</span>
                Lint Content
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
                    <span class="material-symbols-rounded">verified</span>
                    Content Validation
                </span>
                <?php if ($valid): ?>
                <span class="badge badge-success">All Valid</span>
                <?php else: ?>
                <span class="badge badge-danger"><?= count($errors) ?> Error<?= count($errors) !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($valid): ?>
                <div class="lint-success">
                    <span class="material-symbols-rounded">verified</span>
                    <div>
                        <strong>All content files are valid</strong>
                        <p>No YAML or Markdown errors found across all content files.</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="lint-error-summary">
                    Found <?= count($errors) ?> error<?= count($errors) !== 1 ? 's' : '' ?> in content files:
                </div>
                <div class="lint-errors">
                    <?php foreach ($errors as $error): ?>
                    <div class="lint-error-item">
                        <span class="material-symbols-rounded">error</span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <span class="card-title">
                    <span class="material-symbols-rounded">help</span>
                    About Linting
                </span>
            </div>
            <div class="card-body">
                <p class="text-secondary text-sm" style="margin-bottom: var(--sp-3);">
                    The linter checks all content files for:
                </p>
                <div class="list-item"><span class="list-label">YAML Syntax</span><span class="text-secondary text-sm">Valid frontmatter structure</span></div>
                <div class="list-item"><span class="list-label">Required Fields</span><span class="text-secondary text-sm">Title, date, status presence</span></div>
                <div class="list-item"><span class="list-label">Date Format</span><span class="text-secondary text-sm">ISO 8601 date format</span></div>
                <div class="list-item"><span class="list-label">Taxonomy Terms</span><span class="text-secondary text-sm">Valid taxonomy references</span></div>
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
