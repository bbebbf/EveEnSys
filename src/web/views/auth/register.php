<h2 class="mb-4">Register</h2>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?= h($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <form method="post" action="/register" novalidate>
      <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">

      <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text"
               class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
               id="name" name="name"
               value="<?= h($old['name'] ?? '') ?>"
               required maxlength="100" autofocus>
        <?php if (isset($errors['name'])): ?>
          <div class="invalid-feedback"><?= h($errors['name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email"
               class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
               id="email" name="email"
               value="<?= h($old['email'] ?? '') ?>"
               required maxlength="100">
        <?php if (isset($errors['email'])): ?>
          <div class="invalid-feedback"><?= h($errors['email']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password"
               class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
               id="password" name="password"
               required minlength="8">
        <?php if (isset($errors['password'])): ?>
          <div class="invalid-feedback"><?= h($errors['password']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="password_confirm" class="form-label">Confirm password</label>
        <input type="password"
               class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>"
               id="password_confirm" name="password_confirm"
               required>
        <?php if (isset($errors['password_confirm'])): ?>
          <div class="invalid-feedback"><?= h($errors['password_confirm']) ?></div>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>

    <p class="mt-3 text-center">
      Already have an account? <a href="/login">Log in</a>
    </p>
  </div>
</div>
