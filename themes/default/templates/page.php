<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $item->title() . ' - ' . $site['name']]) ?>

        <div class="container">
            <article class="entry">
                <header class="entry-header">
                    <h1><?= $ava->e($item->title()) ?></h1>
                </header>

                <div class="entry-content">
                    <?= $ava->content($item) ?>
                </div>
            </article>
        </div>

<?= $ava->partial('footer') ?>
