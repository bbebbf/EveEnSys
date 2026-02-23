<h2 class="mb-4">Profil</h2>

<div class="row g-4">

  <div class="col-md-6">
    <?php /* Anzeigename ändern */ ?>
    <div class="card">
      <div class="card-header"><strong>Anzeigename</strong></div>
      <div class="card-body">

        <?php if (!empty($nameErrors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($nameErrors as $e): ?>
                <li><?= h($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="/profile/<?= h($user->userGuid) ?>/name" novalidate>
          <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">

          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text"
                   class="form-control <?= isset($nameErrors['name']) ? 'is-invalid' : '' ?>"
                   id="name" name="name"
                   value="<?= h($user->userName) ?>"
                   required maxlength="100">
            <?php if (isset($nameErrors['name'])): ?>
              <div class="invalid-feedback"><?= h($nameErrors['name']) ?></div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn btn-primary">Name speichern</button>
        </form>

      </div>
    </div>
  </div>

  <div class="col-md-6">
    <?php /* Passwort ändern */ ?>
    <div class="card">
      <div class="card-header"><strong>Passwort ändern</strong></div>
      <div class="card-body">

        <?php if (!empty($pwdErrors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($pwdErrors as $e): ?>
                <li><?= h($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="/profile/<?= h($user->userGuid) ?>/password" novalidate>
          <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">

          <div class="mb-3">
            <label for="current_password" class="form-label">Aktuelles Passwort</label>
            <input type="password"
                   class="form-control <?= isset($pwdErrors['current_password']) ? 'is-invalid' : '' ?>"
                   id="current_password" name="current_password"
                   required>
            <?php if (isset($pwdErrors['current_password'])): ?>
              <div class="invalid-feedback"><?= h($pwdErrors['current_password']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="new_password" class="form-label">Neues Passwort</label>
            <input type="password"
                   class="form-control <?= isset($pwdErrors['new_password']) ? 'is-invalid' : '' ?>"
                   id="new_password" name="new_password"
                   required minlength="8">
            <?php if (isset($pwdErrors['new_password'])): ?>
              <div class="invalid-feedback"><?= h($pwdErrors['new_password']) ?></div>
            <?php endif; ?>
            <div class="form-text">Mind. 8 Zeichen, mit Groß- und Kleinbuchstaben sowie Zahlen.</div>
          </div>

          <div class="mb-3">
            <label for="new_password_confirm" class="form-label">Neues Passwort bestätigen</label>
            <input type="password"
                   class="form-control <?= isset($pwdErrors['new_password_confirm']) ? 'is-invalid' : '' ?>"
                   id="new_password_confirm" name="new_password_confirm"
                   required>
            <?php if (isset($pwdErrors['new_password_confirm'])): ?>
              <div class="invalid-feedback"><?= h($pwdErrors['new_password_confirm']) ?></div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn btn-primary">Passwort ändern</button>
        </form>

      </div>
    </div>

    <?php /* Profil löschen */ ?>
    <div class="card border-danger mt-3">
      <div class="card-header bg-danger text-white"><strong>Profil löschen</strong></div>
      <div class="card-body">
        <p class="text-danger small mb-2">Diese Aktion löscht Ihr Konto sowie alle Ihre Veranstaltungen dauerhaft und kann nicht rückgängig gemacht werden.</p>
        <a href="/profile/<?= h($user->userGuid) ?>/delete" class="btn btn-outline-danger btn-sm">Profil löschen</a>
      </div>
    </div>
  </div>
</div>
