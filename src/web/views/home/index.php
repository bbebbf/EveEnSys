<div class="row justify-content-center">
  <div class="col-lg-8 text-center mb-5">
    <h1 class="display-5 fw-bold mb-3">Welcome to EveEnSys</h1>
    <p class="lead text-muted">
      EveEnSys is an event enrollment system. Browse upcoming events below —
      or <a href="/register">create a free account</a> to organize your own events
      and enroll participants.
    </p>
  </div>
</div>

<h2 class="mb-4">Upcoming Events</h2>

<?php if (empty($upcomingEvents)): ?>
  <div class="text-center text-muted py-5">
    <p class="fs-5">No upcoming events at the moment.</p>
    <?php if (Session::isLoggedIn()): ?>
      <a href="/events/create" class="btn btn-outline-primary">Create the first one</a>
    <?php else: ?>
      <a href="/login" class="btn btn-outline-primary">Log in to create events</a>
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
            <?= h(date('D, d M Y H:i', strtotime($event->eventDate))) ?>
            <?php if ($event->eventDurationHours !== null): ?>
              &nbsp;&bull;&nbsp;<?= h($event->eventDurationHours) ?>h
            <?php endif; ?>
            <?php if ($event->eventMaxSubscriber !== null): ?>
              &nbsp;&bull;&nbsp;max <?= h($event->eventMaxSubscriber) ?>
            <?php endif; ?>
            <br>
            By <?= h($event->creatorName ?? 'Unknown') ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="text-center">
    <a href="/login" class="btn btn-outline-secondary">Log in to view all upcoming events</a>
  </div>
<?php endif; ?>
