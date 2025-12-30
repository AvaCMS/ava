<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $site['name'], 'pageDescription' => 'Welcome to ' . $site['name']]) ?>

        <div class="container">
            <?php if (isset($page)): ?>
                <article class="entry">
                    <header class="entry-header">
                        <h1><?= $ava->e($page->title()) ?></h1>
                        <?php if ($page->date()): ?>
                            <div class="entry-meta">
                                <time datetime="<?= $page->date()->format('c') ?>">
                                    <?= $ava->date($page->date()) ?>
                                </time>
                            </div>
                        <?php endif; ?>
                    </header>

                    <div class="entry-content">
                        <?= $ava->content($page) ?>
                    </div>
                </article>
            <?php elseif (isset($query)): ?>
                <?php $items = $query->get(); ?>
                <?php if (empty($items)): ?>
                    <div class="search-empty">
                        <p>No content found.</p>
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
                                <?php if ($item->excerpt()): ?>
                                    <p class="excerpt"><?= $ava->e($item->excerpt()) ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?= $ava->pagination($query) ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="page-header">
                    <h1>Welcome to <?= $ava->e($site['name']) ?></h1>
                    <p class="subtitle">A site powered by Ava CMS</p>
                </div>
            <?php endif; ?>
        </div>

<?= $ava->partial('footer') ?>
