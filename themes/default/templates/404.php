<?= $ava->partial('header', ['request' => $request, 'pageTitle' => 'Page Not Found - ' . $site['name']]) ?>

        <div class="container">
            <div class="error-page">
                <h1>404</h1>
                <p>Sorry, the page you're looking for doesn't exist.</p>
                <p><a href="/" class="btn btn-primary">Return Home</a></p>
            </div>
        </div>

<?= $ava->partial('footer') ?>
