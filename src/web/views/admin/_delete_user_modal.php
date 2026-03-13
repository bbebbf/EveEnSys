<?php
// Required: $deleteUserGuid, $deleteUserName
?>
<button type="button" class="btn btn-danger btn-md" title="Benutzer löschen"
        data-bs-toggle="modal"
        data-bs-target="#deleteUserModal-<?= html_out($deleteUserGuid) ?>"><i class="bi bi-trash"></i></button>
<div class="modal fade" id="deleteUserModal-<?= html_out($deleteUserGuid) ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-start">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Benutzer löschen bestätigen</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <form method="post" action="/admin/users/<?= html_out($deleteUserGuid) ?>/delete">
        <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
        <div class="modal-body">
          <p>Möchtest du das Konto <strong><?= html_out($deleteUserName) ?></strong> wirklich dauerhaft löschen?</p>
          <ul class="text-danger small">
            <li>Alle Veranstaltungen dieses Benutzers werden gelöscht.</li>
            <li>Alle Anmeldungen dieses Benutzers werden gelöscht.</li>
            <li>Diese Aktion kann nicht rückgängig gemacht werden.</li>
          </ul>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-danger">Ja, Benutzer löschen</button>
        </div>
      </form>
    </div>
  </div>
</div>
