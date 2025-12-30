<?= $ava->partial('header', ['request' => $request]) ?>

        <div class="container">
            <article class="entry">
                <header class="entry-header">
                    <h1><?= $ava->e($page->title()) ?></h1>
                    
                    <div class="entry-meta">
                        <?php if ($page->date()): ?>
                            <time datetime="<?= $page->date()->format('c') ?>">
                                <?= $ava->date($page->date()) ?>
                            </time>
                        <?php endif; ?>

                        <?php $categories = $page->terms('category'); ?>
                        <?php if (!empty($categories)): ?>
                            <span>
                                in
                                <?php foreach ($categories as $i => $cat): ?>
                                    <a href="<?= $ava->termUrl('category', $cat) ?>"><?= $ava->e($cat) ?></a><?= $i < count($categories) - 1 ? ', ' : '' ?>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="entry-content">
                    <?= $ava->content($page) ?>
                </div>

                <?php $tags = $page->terms('tag'); ?>
                <?php if (!empty($tags)): ?>
                    <footer class="entry-footer">
                        <div class="entry-tags">
                            <?php foreach ($tags as $tag): ?>
                                <a href="<?= $ava->termUrl('tag', $tag) ?>" class="tag">#<?= $ava->e($tag) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </footer>
                <?php endif; ?>
            </article>
        </div>

<?= $ava->partial('footer') ?>
