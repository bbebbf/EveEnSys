<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/profile/<?= h($user->userGuid) ?>">Profil</a></li>
    <li class="breadcrumb-item active">Löschen</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white">
        <strong>Profil löschen bestätigen</strong>
      </div>
      <div class="card-body">
        <p>Möchten Sie Ihr Konto <strong><?= h($user->userName) ?></strong> wirklich dauerhaft löschen?</p>
        <p class="text-danger">Diese Aktion löscht Ihr Konto komplett und kann nicht rückgängig gemacht werden.</p>
        <ul class="text-danger">
            <li>Alle Ihre Veranstaltungen werden gelöscht.</li>
            <li>Alle Ihre Anmeldungen zu Veranstaltungen werden gelöscht.</li>
            <li>Sie verlieren den Zugriff auf alle Funktionen dieser Plattform.</li>
        </ul>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?>
                <li><?= h($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="/profile/<?= h($user->userGuid) ?>/delete" novalidate>
          <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">

          <div class="mb-3">
            <label for="password" class="form-label">Passwort zur Bestätigung</label>
            <input type="password"
                   class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                   id="password" name="password"
                   required autofocus>
            <?php if (isset($errors['password'])): ?>
              <div class="invalid-feedback"><?= h($errors['password']) ?></div>
            <?php endif; ?>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger">Ja, Profil löschen</button>
            <a href="/profile/<?= h($user->userGuid) ?>" class="btn btn-outline-secondary">Abbrechen</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
