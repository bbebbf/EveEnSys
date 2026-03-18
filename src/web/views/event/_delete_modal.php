<?php
// Required: $deleteEventGuid, $deleteEventTitle, $deleteEventDate
// Optional: $deleteEventOrigin (default '')
$deleteEventOrigin ??= '';
?>
<div class="modal fade" id="deleteEventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-start">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Löschen bestätigen</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <form method="post" action="/events/<?= html_out($deleteEventGuid) ?>/delete">
        <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
        <input type="hidden" name="origin" value="<?= html_out($deleteEventOrigin) ?>">
        <div class="modal-body">
          <p>Möchtest du die folgende Veranstaltung wirklich dauerhaft löschen?</p>
          <p class="fw-bold"><?= html_out($deleteEventTitle) ?></p>
          <p class="text-muted small">Geplant: <?= event_date_out($deleteEventDate) ?></p>
          <p class="text-danger small mb-0">Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-danger">Ja, löschen</button>
        </div>
      </form>
    </div>
  </div>
</div>
