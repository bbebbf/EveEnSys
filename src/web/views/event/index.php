<div class="d-flex justify-content-between align-items-center mb-4">
  <h2><?= h($pageTitle) ?></h2>
  <?php if (Session::isLoggedIn()): ?>
    <a href="/events/create" class="btn btn-primary">+ Veranstaltung erstellen</a>
  <?php endif; ?>
</div>

<?php if (empty($events)): ?>
  <div class="text-center text-muted py-5">
    <p class="fs-5">Keine bevorstehenden Veranstaltungen.</p>
    <a href="/events/create" class="btn btn-outline-primary">Jetzt erstellen</a>
  </div>
<?php else: ?>
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
    <?php foreach ($events as $event): ?>
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
              &nbsp;&bull;&nbsp;max. <?= h($event->eventMaxSubscriber) ?> Personen
            <?php endif; ?>
            <br>
            Von <?= h($event->creatorName ?? 'Unbekannt') ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
