<div class="row">
  <div class="col-lg-8">

    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/events">Veranstaltungen</a></li>
        <li class="breadcrumb-item active"><?= html_out($event->eventTitle) ?></li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-start mb-2">
      <h2><?= html_out($event->eventTitle) ?></h2>
      <?php if (Session::isLoggedIn() && Session::getUserId() === $event->creatorUserId): ?>
        <div class="btn-group ms-3">
          <a href="/events/<?= html_out($event->eventGuid) ?>/edit" class="btn btn-sm btn-outline-secondary">Bearbeiten</a>
          <a href="/events/<?= html_out($event->eventGuid) ?>/delete" class="btn btn-sm btn-outline-danger">Löschen</a>
        </div>
      <?php endif; ?>
    </div>

    <dl class="row mb-4">
      <dt class="col-sm-3">Datum &amp; Uhrzeit</dt>
      <dd class="col-sm-9"><?= event_date_out($event->eventDate) ?></dd>

      <?php if ($event->eventLocation !== null): ?>
        <dt class="col-sm-3">Veranstaltungsort</dt>
        <dd class="col-sm-9"><?= html_out($event->eventLocation) ?></dd>
      <?php endif; ?>

      <?php if ($event->eventDurationHours !== null): ?>
        <dt class="col-sm-3">Dauer</dt>
        <dd class="col-sm-9"><?= html_out($event->eventDurationHours) ?> Stunde(n)</dd>
      <?php endif; ?>

      <?php if ($event->eventMaxSubscriber !== null): ?>
        <dt class="col-sm-3">Max. Teilnehmerzahl</dt>
        <dd class="col-sm-9"><?= html_out($event->eventMaxSubscriber) ?></dd>
      <?php endif; ?>

      <dt class="col-sm-3">Veranstalter</dt>
      <dd class="col-sm-9"><?= html_out($event->creatorName ?? 'Unbekannt') ?></dd>

      <?php if ($isAdmin || $isCreator): ?>
        <dt class="col-sm-3">Sichtbarkeit</dt>
        <dd class="col-sm-9">
          <?php if ($event->eventIsVisible): ?>
            <span class="badge bg-success">Sichtbar</span>
          <?php else: ?>
            <span class="badge bg-warning text-dark">Versteckt</span>
          <?php endif; ?>
          <?php if ($isAdmin): ?>
            <form method="post" action="/events/<?= html_out($event->eventGuid) ?>/toggle-visible" class="d-inline ms-2">
              <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
              <button type="submit" class="btn btn-sm <?= $event->eventIsVisible ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                <?= $event->eventIsVisible ? 'Verstecken' : 'Sichtbar machen' ?>
              </button>
            </form>
          <?php endif; ?>
        </dd>
      <?php endif; ?>
    </dl>

    <?php if ($event->eventDescription !== null): ?>
      <h5>Beschreibung</h5>
      <p class="text-muted"><?= nl2br(html_out($event->eventDescription)) ?></p>
    <?php endif; ?>

  </div>

  <div class="col-lg-4">
    <?php
      $isFull = $event->eventMaxSubscriber !== null && $subscriberCount >= $event->eventMaxSubscriber;
    ?>
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Angemeldete Teilnehmer</strong>
        <?php if ($event->eventMaxSubscriber !== null): ?>
          <span class="badge <?= $isFull ? 'bg-danger' : 'bg-secondary' ?>">
            <?= html_out($subscriberCount) ?>/<?= html_out($event->eventMaxSubscriber) ?>
          </span>
        <?php elseif ($subscriberCount > 0): ?>
          <span class="badge bg-secondary"><?= html_out($subscriberCount) ?></span>
        <?php endif; ?>
      </div>

      <?php if (Session::isLoggedIn()): ?>
        <ul class="list-group list-group-flush">
          <?php if (empty($subscribers)): ?>
            <li class="list-group-item text-muted">Noch keine Teilnehmer angemeldet.</li>
          <?php else: ?>
            <?php foreach ($subscribers as $sub): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  <?= html_out($sub->subscriberName ?? 'Unbekannt') ?>
                  <small class="text-muted ms-1"><?= html_out($sub->subscriberEnrollTimestamp->format('d.m.Y')) ?></small>
                </span>
                <?php if ($sub->creatorUserId === Session::getUserId()): ?>
                  <a href="/events/<?= html_out($event->eventGuid) ?>/unenroll/<?= html_out($sub->subscriberGuid) ?>"
                     class="btn btn-sm btn-outline-danger">Abmelden</a>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>

        <div class="card-body">
          <?php if ($isFull): ?>
            <p class="text-danger mb-0">Diese Veranstaltung ist ausgebucht.</p>
          <?php else: ?>
            <?php if (!$isEnrolledAsSelf): ?>
              <form method="post" action="/events/<?= html_out($event->eventGuid) ?>/enroll" class="mb-3">
                <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
                <input type="hidden" name="enroll_type" value="self">
                <button type="submit" class="btn btn-primary btn-sm w-100">Selbst anmelden</button>
              </form>
            <?php else: ?>
              <p class="text-success mb-3"><small>Sie sind für diese Veranstaltung angemeldet.</small></p>
            <?php endif; ?>

            <form method="post" action="/events/<?= html_out($event->eventGuid) ?>/enroll">
              <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
              <input type="hidden" name="enroll_type" value="other">
              <div class="input-group input-group-sm">
                <input type="text" name="subscriber_name" class="form-control"
                       placeholder="Person anmelden" maxlength="100" required>
                <button type="submit" class="btn btn-outline-secondary">Anmelden</button>
              </div>
            </form>
          <?php endif; ?>
        </div>

      <?php else: ?>
        <div class="card-body text-muted">
          <a href="/login">Anmelden</a>, um Teilnehmer zu sehen und sich anzumelden.
        </div>
      <?php endif; ?>

    </div>
  </div>

</div>
