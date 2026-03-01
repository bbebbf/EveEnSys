
</div><!-- /container -->

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mt-4">
  <div class="container">
    <div class="navbar-nav mx-auto align-items-center">
      <?php if (strlen(APP_CONFIG->getAppImpressUrl()) > 0): ?>
        <a class="nav-link" href="<?= APP_CONFIG->getAppImpressUrl() ?>">Impressum</a>
      <?php else: ?>
        <a class="nav-link" href="#">Kein Impressum</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
