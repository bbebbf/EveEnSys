<h2 class="mb-4">Anmelden</h2>

<?php if (isset($errors['general'])): ?>
  <div class="alert alert-danger"><?= html_out($errors['general']) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <form method="post" action="/login" novalidate>
      <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">

      <div class="mb-3">
        <label for="email" class="form-label">E-Mail-Adresse</label>
        <input type="email"
               class="form-control"
               id="email" name="email"
               value="<?= html_out($old['email'] ?? '') ?>"
               required autofocus>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Passwort</label>
        <input type="password"
               class="form-control"
               id="password" name="password"
               required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Anmelden</button>
    </form>

    <p class="mt-3 text-center">
      <a href="/forgot-password">Passwort vergessen?</a>
    </p>
    <p class="text-center">
      Noch kein Konto? <a href="/register">Registrieren</a>
    </p>

    <?php if (!empty($oidcProviderInfos)): ?>
      <div class="d-grid mt-4 gap-2">
        <?php foreach ($oidcProviderInfos as $provider): ?>
          <a href="/auth/oidc/<?= html_out($provider->providerKey) ?>/login" class="btn btn-outline-secondary">
            <?php if (!is_null($provider->imageSvg)): ?>
              <img src="data:image/svg+xml;base64,<?= base64_encode($provider->imageSvg) ?>" alt="<?= html_out($provider->label) ?>" style="height: 1.5em; vertical-align: middle;">
            <?php endif; ?>
            Mit <?= html_out($provider->label) ?> anmelden
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
