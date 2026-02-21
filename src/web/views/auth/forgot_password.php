<h2 class="mb-4">Forgot Password</h2>

<div class="row justify-content-center">
  <div class="col-md-6">
    <p class="text-muted">
      Enter the email address of your account and we will send you a link to reset your password.
    </p>

    <form method="post" action="/forgot-password" novalidate>
      <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">

      <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email"
               class="form-control"
               id="email" name="email"
               required autofocus>
      </div>

      <button type="submit" class="btn btn-primary w-100">Send reset link</button>
    </form>

    <p class="mt-3 text-center">
      <a href="/login">Back to login</a>
    </p>
  </div>
</div>
