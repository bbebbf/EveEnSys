<div class="row">
  <div class="col-lg-8">

    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/events">Events</a></li>
        <li class="breadcrumb-item active"><?= h($event->eventTitle) ?></li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-2">
      <h2><?= h($event->eventTitle) ?></h2>
      <?php if (Session::isLoggedIn() && Session::getUserId() === $event->creatorUserId): ?>
        <div class="btn-group ms-3">
          <a href="/events/<?= h($event->eventGuid) ?>/edit" class="btn btn-sm btn-outline-secondary">Edit</a>
          <a href="/events/<?= h($event->eventGuid) ?>/delete" class="btn btn-sm btn-outline-danger">Delete</a>
        </div>
      <?php endif; ?>
    </div>

    <dl class="row mb-4">
      <dt class="col-sm-3">Date &amp; time</dt>
      <dd class="col-sm-9"><?= h(date('l, d F Y \a\t H:i', strtotime($event->eventDate))) ?></dd>

      <?php if ($event->eventDurationHours !== null): ?>
        <dt class="col-sm-3">Duration</dt>
        <dd class="col-sm-9"><?= h($event->eventDurationHours) ?> hour(s)</dd>
      <?php endif; ?>

      <?php if ($event->eventMaxSubscriber !== null): ?>
        <dt class="col-sm-3">Max participants</dt>
        <dd class="col-sm-9"><?= h($event->eventMaxSubscriber) ?></dd>
      <?php endif; ?>

      <dt class="col-sm-3">Organiser</dt>
      <dd class="col-sm-9"><?= h($event->creatorName ?? 'Unknown') ?></dd>
    </dl>

    <?php if ($event->eventDescription !== null): ?>
      <h5>Description</h5>
      <p class="text-muted"><?= nl2br(h($event->eventDescription)) ?></p>
    <?php endif; ?>

  </div>

  <div class="col-lg-4">
    <?php
      $isFull = $event->eventMaxSubscriber !== null && $subscriberCount >= $event->eventMaxSubscriber;
    ?>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Enrolled participants</strong>
        <?php if ($event->eventMaxSubscriber !== null): ?>
          <span class="badge <?= $isFull ? 'bg-danger' : 'bg-secondary' ?>">
            <?= h($subscriberCount) ?>/<?= h($event->eventMaxSubscriber) ?>
          </span>
        <?php elseif ($subscriberCount > 0): ?>
          <span class="badge bg-secondary"><?= h($subscriberCount) ?></span>
        <?php endif; ?>
      </div>

      <?php if (Session::isLoggedIn()): ?>
        <ul class="list-group list-group-flush">
          <?php if (empty($subscribers)): ?>
            <li class="list-group-item text-muted">No participants enrolled yet.</li>
          <?php else: ?>
            <?php foreach ($subscribers as $sub): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  <?= h($sub->subscriberName ?? 'Unknown') ?>
                  <?php if ($sub->subscriberIsCreator): ?>
                    <span class="badge bg-primary ms-1">Me</span>
                  <?php endif; ?>
                  <small class="text-muted ms-1"><?= h(date('Y-m-d', strtotime($sub->subscriberEnrollTimestamp))) ?></small>
                </span>
                <?php if ($sub->creatorUserId === Session::getUserId()): ?>
                  <form method="post"
                        action="/events/<?= h($event->eventGuid) ?>/unenroll/<?= h($sub->subscriberId) ?>"
                        onsubmit="return confirm('Remove this enrollment?')">
                    <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                  </form>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>

        <div class="card-body">
          <?php if ($isFull): ?>
            <p class="text-danger mb-0">This event is fully booked.</p>
          <?php else: ?>
            <?php if (!$isEnrolledAsSelf): ?>
              <form method="post" action="/events/<?= h($event->eventGuid) ?>/enroll" class="mb-3">
                <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
                <input type="hidden" name="enroll_type" value="self">
                <button type="submit" class="btn btn-primary btn-sm w-100">Enroll yourself</button>
              </form>
            <?php else: ?>
              <p class="text-success mb-3"><small>You are enrolled in this event.</small></p>
            <?php endif; ?>

            <form method="post" action="/events/<?= h($event->eventGuid) ?>/enroll">
              <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
              <input type="hidden" name="enroll_type" value="other">
              <div class="input-group input-group-sm">
                <input type="text" name="subscriber_name" class="form-control"
                       placeholder="Enroll someone by name" maxlength="100" required>
                <button type="submit" class="btn btn-outline-secondary">Enroll</button>
              </div>
            </form>
          <?php endif; ?>
        </div>

      <?php else: ?>
        <div class="card-body text-muted">
          <a href="/login">Log in</a> to see participants and enroll.
        </div>
      <?php endif; ?>

    </div>
  </div>

</div>
