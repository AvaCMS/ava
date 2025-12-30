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
    <header class="site-header">
        <div class="container">
            <a href="/" class="site-logo"><?= $ava->e($site['name']) ?></a>
            <nav class="site-nav">
                <a href="/" <?= $request->path() === '/' ? 'class="active"' : '' ?>>Home</a>
                <a href="/blog" <?= str_starts_with($request->path(), '/blog') ? 'class="active"' : '' ?>>Blog</a>
                <a href="/search" <?= $request->path() === '/search' ? 'class="active"' : '' ?>>Search</a>
            </nav>
            <button class="nav-toggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </header>

    <main class="site-main">
