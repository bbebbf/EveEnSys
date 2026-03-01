<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/events">Veranstaltungen</a></li>
    <li class="breadcrumb-item">
      <a href="/events/<?= html_out($event->eventGuid) ?>"><?= html_out($event->eventTitle) ?></a>
    </li>
    <li class="breadcrumb-item active">Löschen</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white">
        <strong>Löschen bestätigen</strong>
      </div>
      <div class="card-body">
        <p>Möchten Sie die folgende Veranstaltung wirklich dauerhaft löschen?</p>
        <p class="fw-bold fs-5"><?= html_out($event->eventTitle) ?></p>
        <p class="text-muted small">
          Geplant: <?= event_date_out($event->eventDate) ?>
        </p>
        <p class="text-danger small">Diese Aktion kann nicht rückgängig gemacht werden.</p>
      </div>
      <div class="card-footer d-flex gap-2">
        <form method="post" action="/events/<?= html_out($event->eventGuid) ?>/delete">
          <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
          <button type="submit" class="btn btn-danger">Ja, löschen</button>
        </form>
        <a href="/events/<?= html_out($event->eventGuid) ?>" class="btn btn-outline-secondary">Abbrechen</a>
      </div>
    </div>
  </div>
</div>
