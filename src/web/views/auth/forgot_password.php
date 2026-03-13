<h2 class="mb-4">Passwort vergessen</h2>

<div class="row justify-content-center">
  <div class="col-md-6">
    <p class="text-muted">
      Gib die E-Mail-Adresse deines Kontos ein und ein Link zum Zurücksetzen deines Passworts wird an diese E-Mail-Adresse gesendet.
    </p>

    <form method="post" action="/forgot-password" novalidate>
      <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">

      <div class="mb-3">
        <label for="email" class="form-label">E-Mail-Adresse</label>
        <input type="email"
               class="form-control"
               id="email" name="email"
               required autofocus>
      </div>

      <button type="submit" class="btn btn-primary w-100">Link senden</button>
    </form>

    <p class="mt-3 text-center">
      <a href="/login">Zurück zur Anmeldung</a>
    </p>
  </div>
</div>
