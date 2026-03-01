<h2 class="mb-4">Registrieren</h2>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?= html_out($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <form method="post" action="/register" novalidate>
      <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">

      <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text"
               class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
               id="name" name="name"
               value="<?= html_out($old['name'] ?? '') ?>"
               required maxlength="100" autofocus>
        <?php if (isset($errors['name'])): ?>
          <div class="invalid-feedback"><?= html_out($errors['name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">E-Mail-Adresse</label>
        <input type="email"
               class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
               id="email" name="email"
               value="<?= html_out($old['email'] ?? '') ?>"
               required maxlength="100">
        <?php if (isset($errors['email'])): ?>
          <div class="invalid-feedback"><?= html_out($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Passwort</label>
        <input type="password"
               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
               id="password" name="password"
               required minlength="8">
        <?php if (isset($errors['password'])): ?>
          <div class="invalid-feedback"><?= html_out($errors['password']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="password_confirm" class="form-label">Passwort bestätigen</label>
        <input type="password"
               class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
               id="password_confirm" name="password_confirm"
               required>
        <?php if (isset($errors['password_confirm'])): ?>
          <div class="invalid-feedback"><?= html_out($errors['password_confirm']) ?></div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary w-100">Registrieren</button>
    </form>

    <p class="mt-3 text-center">
      Bereits ein Konto? <a href="/login">Anmelden</a>
    </p>
  </div>
</div>
