<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/events">Veranstaltungen</a></li>
    <li class="breadcrumb-item">
      <a href="/events/<?= h($event->eventGuid) ?>"><?= h($event->eventTitle) ?></a>
    </li>
    <li class="breadcrumb-item active">Abmeldung bestätigen</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white">
        <strong>Abmeldung bestätigen</strong>
      </div>
      <div class="card-body">
        <p>Möchten Sie die folgende Person wirklich abmelden?</p>
        <p class="fw-bold fs-5"><?= h($subscriber->subscriberName ?? 'Unbekannt') ?></p>
        <p class="text-muted small">
          Veranstaltung: <?= h($event->eventTitle) ?> &mdash; <?= format_event_date($event->eventDate) ?>
        </p>
      </div>
      <div class="card-footer d-flex gap-2">
        <form method="post" action="/events/<?= h($event->eventGuid) ?>/unenroll/<?= h($subscriber->subscriberGuid) ?>">
          <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
          <button type="submit" class="btn btn-danger">Ja, abmelden</button>
        </form>
        <a href="/events/<?= h($event->eventGuid) ?>" class="btn btn-outline-secondary">Abbrechen</a>
      </div>
    </div>
  </div>
</div>
