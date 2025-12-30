<?php
$taxLabel = $tax['config']['label'] ?? ucfirst($tax['name']);
$pageTitle = $taxLabel . ' - ' . $site['name'];
?>
<?= $ava->partial('header', ['request' => $request, 'pageTitle' => $pageTitle]) ?>

        <div class="container">
            <header class="page-header">
                <h1><?= $ava->e($taxLabel) ?></h1>
            </header>

            <?php $terms = $tax['terms'] ?? []; ?>

            <?php if (empty($terms)): ?>
                <div class="search-empty">
                    <p>No terms in this taxonomy yet.</p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php 
                    $baseUrl = $tax['config']['rewrite']['base'] ?? '/' . $tax['name'];
                    foreach ($terms as $slug => $termData): 
                        $itemCount = count($termData['items'] ?? []);
                    ?>
                        <a href="<?= $ava->e($baseUrl . '/' . $slug) ?>" class="card">
                            <div class="card-title"><?= $ava->e($termData['name'] ?? $slug) ?></div>
                            <div class="card-count"><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?></div>
                            <?php if (!empty($termData['description'])): ?>
                                <p class="card-description"><?= $ava->e($termData['description']) ?></p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

<?= $ava->partial('footer') ?>
