    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?= date('Y') ?> <?= $ava->e($site['name']) ?>. Built with <a href="https://github.com/adamgreenough/ava">Ava CMS</a>.</p>
            </div>
        </div>
    </footer>

    <script>
    // Mobile nav toggle
    document.querySelector('.nav-toggle')?.addEventListener('click', function() {
        document.querySelector('.site-nav').classList.toggle('open');
        this.classList.toggle('open');
    });
    </script>
</body>
</html>
