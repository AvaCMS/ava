<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $page->title() . ' - ' . $site['name']]) ?>

        <div class="container">
            <article class="entry">
                <header class="entry-header">
                    <h1><?= $ava->e($page->title()) ?></h1>
                </header>

                <div class="entry-content">
                    <?= $ava->content($page) ?>
                </div>
            </article>
        </div>

<?= $ava->partial('footer') ?>
