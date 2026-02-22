<h2 class="mb-4">My Profile</h2>

<div class="row g-4">

  <?php /* Change display name */ ?>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><strong>Display name</strong></div>
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

        <form method="post" action="/profile/name" novalidate>
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

          <button type="submit" class="btn btn-primary">Save name</button>
        </form>

      </div>
    </div>
  </div>

  <?php /* Change password */ ?>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><strong>Change password</strong></div>
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

        <form method="post" action="/profile/password" novalidate>
          <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">

          <div class="mb-3">
            <label for="current_password" class="form-label">Current password</label>
            <input type="password"
                   class="form-control <?= isset($pwdErrors['current_password']) ? 'is-invalid' : '' ?>"
                   id="current_password" name="current_password"
                   required>
            <?php if (isset($pwdErrors['current_password'])): ?>
              <div class="invalid-feedback"><?= h($pwdErrors['current_password']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="new_password" class="form-label">New password</label>
            <input type="password"
                   class="form-control <?= isset($pwdErrors['new_password']) ? 'is-invalid' : '' ?>"
                   id="new_password" name="new_password"
                   required minlength="8">
            <?php if (isset($pwdErrors['new_password'])): ?>
              <div class="invalid-feedback"><?= h($pwdErrors['new_password']) ?></div>
            <?php endif; ?>
            <div class="form-text">Min. 8 characters, including uppercase, lowercase and numbers.</div>
          </div>

          <div class="mb-3">
            <label for="new_password_confirm" class="form-label">Confirm new password</label>
            <input type="password"
                   class="form-control <?= isset($pwdErrors['new_password_confirm']) ? 'is-invalid' : '' ?>"
                   id="new_password_confirm" name="new_password_confirm"
                   required>
            <?php if (isset($pwdErrors['new_password_confirm'])): ?>
              <div class="invalid-feedback"><?= h($pwdErrors['new_password_confirm']) ?></div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn btn-primary">Change password</button>
        </form>

      </div>
    </div>
  </div>

</div>
