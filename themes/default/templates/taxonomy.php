<?php
$termName = $tax['term']['name'] ?? 'Unknown';
$pageTitle = $termName . ' - ' . $site['name'];
?>
<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $pageTitle]) ?>

        <div class="container">
            <header class="page-header">
                <h1><?= $ava->e($termName) ?></h1>
                <?php if (!empty($tax['term']['description'])): ?>
                    <p class="subtitle"><?= $ava->e($tax['term']['description']) ?></p>
                <?php endif; ?>
            </header>

            <?php $items = $query->get(); ?>

            <?php if (empty($items)): ?>
                <div class="search-empty">
                    <p>No content found in this category.</p>
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

                            <?php if ($item->date()): ?>
                                <div class="meta">
                                    <time datetime="<?= $item->date()->format('c') ?>">
                                        <?= $ava->date($item->date()) ?>
                                    </time>
                                </div>
                            <?php endif; ?>

                            <?php if ($item->excerpt()): ?>
                                <p class="excerpt"><?= $ava->e($item->excerpt()) ?></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?= $ava->pagination($query, $request->path()) ?>
            <?php endif; ?>
        </div>

<?= $ava->partial('footer') ?>
