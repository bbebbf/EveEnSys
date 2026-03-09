<?php
// Required: $deleteUserGuid, $deleteUserName, $deleteOidcOnly
// Optional: $deleteErrors (default [])
$deleteErrors ??= [];
?>
<button type="button" class="btn btn-outline-danger btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#deleteProfileModal">Profil löschen</button>
<div class="modal fade" id="deleteProfileModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-start">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Profil löschen bestätigen</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <form method="post" action="/profile/<?= html_out($deleteUserGuid) ?>/delete" novalidate>
        <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
        <div class="modal-body">
          <p>Möchten Sie Ihr Konto <strong><?= html_out($deleteUserName) ?></strong> wirklich dauerhaft löschen?</p>
          <ul class="text-danger small">
            <li>Alle Ihre Veranstaltungen werden gelöscht.</li>
            <li>Alle Ihre Anmeldungen zu Veranstaltungen werden gelöscht.</li>
            <li>Sie verlieren den Zugriff auf alle Funktionen dieser Plattform.</li>
          </ul>

          <?php if (!empty($deleteErrors)): ?>
            <div class="alert alert-danger">
              <ul class="mb-0">
                <?php foreach ($deleteErrors as $e): ?>
                  <li><?= html_out($e) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if (!$deleteOidcOnly): ?>
            <div class="mb-0">
              <label for="deletePassword" class="form-label">Passwort zur Bestätigung</label>
              <input type="password"
                     class="form-control <?= isset($deleteErrors['password']) ? 'is-invalid' : '' ?>"
                     id="deletePassword" name="password"
                     required>
              <?php if (isset($deleteErrors['password'])): ?>
                <div class="invalid-feedback"><?= html_out($deleteErrors['password']) ?></div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <p class="text-muted fst-italic mb-0">Sie sind ausschließlich über einen externen Anbieter angemeldet. Keine Passwort-Bestätigung erforderlich.</p>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-danger">Ja, Profil löschen</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php if (!empty($deleteErrors)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('deleteProfileModal')).show();
  });
</script>
<?php endif; ?>
