<div class="row justify-content-center">
  <div class="col-lg-8 text-center mb-5">
    <h1 class="display-5 fw-bold mb-3">Willkommen bei EveEnSys</h1>
    <p class="lead text-muted">
      EveEnSys ist ein Veranstaltungsanmeldesystem. Stöbern Sie unten in den bevorstehenden Veranstaltungen –
      oder <a href="/register">erstellen Sie ein kostenloses Konto</a>, um eigene Veranstaltungen zu organisieren
      und Teilnehmer anzumelden.
    </p>
  </div>
</div>

<h2 class="mb-4">Bevorstehende Veranstaltungen</h2>

<?php if (empty($upcomingEvents)): ?>
  <div class="text-center text-muted py-5">
    <p class="fs-5">Derzeit keine bevorstehenden Veranstaltungen.</p>
    <?php if (Session::isLoggedIn()): ?>
      <a href="/events/create" class="btn btn-outline-primary">Erste Veranstaltung erstellen</a>
    <?php else: ?>
      <a href="/login" class="btn btn-outline-primary">Anmelden, um Veranstaltungen zu erstellen</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
    <?php foreach ($upcomingEvents as $event): ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">
              <a href="/events/<?= h($event->eventGuid) ?>" class="text-decoration-none stretched-link">
                <?= h($event->eventTitle) ?>
              </a>
            </h5>
            <?php if ($event->eventDescription !== null): ?>
              <p class="card-text text-muted small">
                <?= h(mb_strimwidth($event->eventDescription, 0, 120, '…')) ?>
              </p>
            <?php endif; ?>
          </div>
          <div class="card-footer text-muted small">
            <i class="bi bi-calendar-event"></i>
            <?= h(date('d.m.Y H:i', strtotime($event->eventDate))) ?>
            <?php if ($event->eventDurationHours !== null): ?>
              &nbsp;&bull;&nbsp;<?= h($event->eventDurationHours) ?>h
            <?php endif; ?>
            <?php if ($event->eventMaxSubscriber !== null): ?>
              &nbsp;&bull;&nbsp;max. Personen <?= h($event->eventMaxSubscriber) ?>
            <?php endif; ?>
            <br>
            Von <?= h($event->creatorName ?? 'Unbekannt') ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="text-center">
    <a href="/login" class="btn btn-outline-secondary">Anmelden, um alle bevorstehenden Veranstaltungen zu sehen</a>
  </div>
<?php endif; ?>
