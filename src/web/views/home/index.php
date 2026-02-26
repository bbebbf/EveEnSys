<div class="row justify-content-center">
  <div class="col-lg-8 text-center mb-5">
      <?php
        $greetingsFile = __DIR__ . '/greetings.php';
        if (file_exists($greetingsFile)) {
            include $greetingsFile;
        }
      ?>
  </div>
</div>

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
  <h2 class="mb-4">Bevorstehende Veranstaltungen</h2>
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
    <?php foreach ($upcomingEvents as $event): ?>
      <div class="col">


        <div class="card h-100 shadow-sm">
          <div class="card-header">
            <i class="bi bi-calendar-event"></i>
            <?= format_event_date($event->eventDate) ?>
          </div>
          <div class="card-body">
            <h5 class="card-title d-flex justify-content-between">
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
            <?php if ($event->eventDurationHours !== null): ?>
              &bull;&nbsp;<?= h($event->eventDurationHours) ?>h
            <?php endif; ?>
            <?php if ($event->eventMaxSubscriber !== null): ?>
              &bull;&nbsp;max. <?= h($event->eventMaxSubscriber) ?> Personen
            <?php endif; ?>
            <?php if ($event->eventLocation !== null): ?>
              &bull;&nbsp;<i class="bi bi-geo-alt"></i> <?= h($event->eventLocation) ?>
            <?php endif; ?>
          </div>
        </div>


    </div>
    <?php endforeach; ?>
  </div>

  <div class="text-center">
    <a href="/login" class="btn btn-outline-secondary">Anmelden, um alle bevorstehenden Veranstaltungen zu sehen</a>
  </div>
<?php endif; ?>
