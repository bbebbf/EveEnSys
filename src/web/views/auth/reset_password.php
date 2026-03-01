<h2 class="mb-4">Neues Passwort festlegen</h2>

<div class="row justify-content-center">
  <div class="col-md-6">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= html_out($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="/reset-password" novalidate>
      <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
      <input type="hidden" name="token" value="<?= html_out($token) ?>">

      <div class="mb-3">
        <label for="new_password" class="form-label">Neues Passwort</label>
        <input type="password"
               class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
               id="new_password" name="new_password"
               required minlength="8" autofocus>
        <?php if (isset($errors['new_password'])): ?>
          <div class="invalid-feedback"><?= html_out($errors['new_password']) ?></div>
        <?php endif; ?>
        <div class="form-text">Mind. 8 Zeichen, mit Groß- und Kleinbuchstaben sowie Zahlen.</div>
      </div>

      <div class="mb-3">
        <label for="new_password_confirm" class="form-label">Neues Passwort bestätigen</label>
        <input type="password"
               class="form-control <?= isset($errors['new_password_confirm']) ? 'is-invalid' : '' ?>"
               id="new_password_confirm" name="new_password_confirm"
               required>
        <?php if (isset($errors['new_password_confirm'])): ?>
          <div class="invalid-feedback"><?= html_out($errors['new_password_confirm']) ?></div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary w-100">Passwort festlegen</button>
    </form>

  </div>
</div>
