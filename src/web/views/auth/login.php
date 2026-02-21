<h2 class="mb-4">Login</h2>

<?php if (isset($errors['general'])): ?>
  <div class="alert alert-danger"><?= h($errors['general']) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <form method="post" action="/login" novalidate>
      <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">

      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email"
               class="form-control"
               id="email" name="email"
               value="<?= h($old['email'] ?? '') ?>"
               required autofocus>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password"
               class="form-control"
               id="password" name="password"
               required>
      </div>

      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <p class="mt-3 text-center">
      <a href="/forgot-password">Forgot your password?</a>
    </p>
    <p class="text-center">
      No account yet? <a href="/register">Register</a>
    </p>
  </div>
</div>
