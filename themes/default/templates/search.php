<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search<?= $searchQuery ? ': ' . $ava->e($searchQuery) : '' ?> - <?= $ava->e($site['name']) ?></title>
    <link rel="stylesheet" href="<?= $ava->asset('style.css') ?>">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="/" class="site-title"><?= $ava->e($site['name']) ?></a>
            <nav class="main-nav">
                <a href="/">Home</a>
                <a href="/blog">Blog</a>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <div class="container">
            <header class="archive-header">
                <h1>Search</h1>
            </header>

            <form class="search-form" action="/search" method="get">
                <input 
                    type="search" 
                    name="q" 
                    class="search-input" 
                    placeholder="Search content..." 
                    value="<?= $ava->e($searchQuery) ?>"
                    autofocus
                >
                <button type="submit" class="search-button">Search</button>
            </form>

            <?php if ($searchQuery !== ''): ?>
                <?php $items = $query->get(); ?>
                <?php $total = $query->count(); ?>

                <p class="search-results-count">
                    Found <?= $total ?> result<?= $total !== 1 ? 's' : '' ?> for "<?= $ava->e($searchQuery) ?>"
                </p>

                <?php if (empty($items)): ?>
                    <p class="search-no-results">No results found. Try a different search term.</p>
                <?php else: ?>
                    <div class="archive-list">
                        <?php foreach ($items as $item): ?>
                            <article class="archive-item">
                                <h2>
                                    <a href="<?= $ava->url($item->type(), $item->slug()) ?>">
                                        <?= $ava->e($item->title()) ?>
                                    </a>
                                </h2>

                                <div class="entry-meta">
                                    <span class="content-type"><?= $ava->e(ucfirst($item->type())) ?></span>
                                    <?php if ($item->date()): ?>
                                        <time datetime="<?= $item->date()->format('c') ?>">
                                            <?= $ava->date($item->date()) ?>
                                        </time>
                                    <?php endif; ?>
                                </div>

                                <?php if ($item->excerpt()): ?>
                                    <p class="excerpt"><?= $ava->e($item->excerpt()) ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?= $ava->pagination($query, '/search?q=' . urlencode($searchQuery)) ?>
                <?php endif; ?>
            <?php else: ?>
                <p class="search-prompt">Enter a search term above to find content.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= $ava->e($site['name']) ?></p>
        </div>
    </footer>
</body>
</html>
