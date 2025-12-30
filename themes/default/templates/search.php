<?php $pageTitle = 'Search' . ($searchQuery ? ': ' . $searchQuery : '') . ' - ' . $site['name']; ?>
<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $pageTitle]) ?>

        <div class="container">
            <header class="page-header">
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
                <button type="submit" class="btn btn-primary">Search</button>
            </form>

            <?php if ($searchQuery !== ''): ?>
                <?php $items = $query->get(); ?>
                <?php $total = $query->count(); ?>

                <p class="search-results-info">
                    Found <?= $total ?> result<?= $total !== 1 ? 's' : '' ?> for "<?= $ava->e($searchQuery) ?>"
                </p>

                <?php if (empty($items)): ?>
                    <div class="search-empty">
                        <p>No results found. Try a different search term.</p>
                    </div>
                <?php else: ?>
                    <div class="archive-list">
                        <?php foreach ($items as $item): ?>
                            <article class="archive-item">
                                <h2>
                                    <a href="<?= $ava->url($item->type(), $item->slug()) ?>">
                                        <?= $ava->e($item->title()) ?>
                                    </a>
                                </h2>

                                <div class="meta">
                                    <span><?= $ava->e(ucfirst($item->type())) ?></span>
                                    <?php if ($item->date()): ?>
                                        &middot;
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
                <div class="search-empty">
                    <p>Enter a search term above to find content.</p>
                </div>
            <?php endif; ?>
        </div>

<?= $ava->partial('footer') ?>
