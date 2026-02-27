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
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>
              <i class="bi bi-calendar-event"></i>
              <?= format_event_date($event->eventDate) ?>
            </span>
            <span>
              <?php if ($isAdmin): ?>
                <div class="mt-2 position-relative" style="z-index: 2;">
                  <form method="post" action="/events/<?= h($event->eventGuid) ?>/toggle-visible">
                    <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
                    <button type="submit" class="btn btn-sm <?= $event->eventIsVisible ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                      <?= $event->eventIsVisible ? 'Verstecken' : 'Sichtbar machen' ?>
                    </button>
                  </form>
                </div>
              <?php elseif (!$event->eventIsVisible): ?>
                <span class="badge text-bg-warning"><small>Versteckt</small></span>
              <?php endif; ?>
            </span>
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
          <div class="card-footer text-muted small d-flex justify-content-between align-items-center">
            <span>
            <?php if ($event->eventDurationHours !== null): ?>
              &nbsp;<i class="bi bi-clock-history"></i> <?= h($event->eventDurationHours) ?> h
            <?php endif; ?>
            <?php if ($event->eventMaxSubscriber !== null): ?>
              &nbsp;<i class="bi bi-people"></i> max. <?= h($event->eventMaxSubscriber) ?>
            <?php endif; ?>
            <?php if ($event->eventLocation !== null): ?>
              &nbsp;<i class="bi bi-geo-alt"></i> <?= h($event->eventLocation) ?>
            <?php endif; ?>
            </span>
            <span class="ms-2 text-body-tertiary">
              <?= h($event->creatorName ?? 'Unbekannt') ?>
            </span>
          </div>
        </div>


      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
