<h2 class="mb-4">E-Mail prüfen</h2>

<div class="row justify-content-center">
  <div class="col-md-6 text-center">
    <p class="text-muted">
      Registrierung erfolgreich! Ein Aktivierungslink wurde an deine E-Mail-Adresse gesendet.
      Bitte klicke auf den Link, um dein Konto zu aktivieren, bevor du dich anmeldest.
    </p>
    <p class="text-muted">
      Solltest Du in den nächsten Minuten keine E-Mail erhalten, überprüfe deinen Spam-Ordner
      und melde dich gerne bei <?= html_out(APP_CONFIG->getOperatorResponsible()) ?> (<?= html_out(APP_CONFIG->getOperatorEmail()) ?>).
    </p>
    <p class="text-muted">
      Der Link ist 24 Stunden gültig.
    </p>
    <p class="mt-3">
      <a href="/login">Zurück zur Anmeldung</a>
    </p>
  </div>
</div>
