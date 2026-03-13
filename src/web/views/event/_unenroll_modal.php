<?php
// Required: $unenrollSubscriberGuid, $unenrollEventGuid, $unenrollSubscriberName
// Optional: $unenrollSource (default ''), $unenrollEventInfo (default null — shown as context line)
$unenrollSource    ??= '';
$unenrollEventInfo ??= null;
?>
<button type="button" class="btn btn-sm btn-outline-danger"
        data-bs-toggle="modal"
        data-bs-target="#unenroll-<?= html_out($unenrollSubscriberGuid) ?>">Abmelden</button>
<div class="modal fade" id="unenroll-<?= html_out($unenrollSubscriberGuid) ?>" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content text-start">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Abmeldung bestätigen</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <form method="post" action="/events/<?= html_out($unenrollEventGuid) ?>/unenroll/<?= html_out($unenrollSubscriberGuid) ?>">
        <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
        <?php if ($unenrollSource !== ''): ?>
          <input type="hidden" name="source" value="<?= html_out($unenrollSource) ?>">
        <?php endif; ?>
        <div class="modal-body">
          <p>Möchtest du die folgende Person wirklich abmelden?</p>
          <p class="fw-bold"><?= html_out($unenrollSubscriberName) ?></p>
          <?php if ($unenrollEventInfo !== null): ?>
            <p class="text-muted small"><?= html_out($unenrollEventInfo) ?></p>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-danger">Ja, abmelden</button>
        </div>
      </form>
    </div>
  </div>
</div>
