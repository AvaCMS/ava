<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (isset($page)): ?>
        <?= $ava->metaTags($page) ?>
        <?= $ava->itemAssets($page) ?>
    <?php else: ?>
        <title><?= $ava->e($pageTitle ?? $site['name']) ?></title>
        <meta name="description" content="<?= $ava->e($pageDescription ?? '') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $ava->asset('style.css') ?>">
</head>
<body>
    <?php $currentPath = isset($request) ? $request->path() : '/'; ?>
    <header class="site-header">
        <div class="container">
            <a href="/" class="site-logo"><?= $ava->e($site['name']) ?></a>
            <nav class="site-nav">
                <a href="/"<?= $currentPath === '/' ? ' class="active"' : '' ?>>Home</a>
                <a href="/about"<?= $currentPath === '/about' ? ' class="active"' : '' ?>>About</a>
                <a href="/blog"<?= str_starts_with($currentPath, '/blog') ? ' class="active"' : '' ?>>Blog</a>
                <a href="/search"<?= $currentPath === '/search' ? ' class="active"' : '' ?>>Search</a>
            </nav>
            <button class="nav-toggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <main class="site-main">
