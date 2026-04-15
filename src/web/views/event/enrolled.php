<div class="d-flex justify-content-between align-items-center mb-4">
  <h2>
    <?= html_out($pageTitle) ?>
    <?php if (count($enrollments) > 0): ?>
      <span class="badge rounded-pill bg-secondary fs-6 ms-2 align-middle"><?= count($enrollments) ?></span>
    <?php endif; ?>
  </h2>
</div>

<?php if (empty($enrollments)): ?>
  <div class="text-center text-muted py-5">
    <p class="fs-5">Du hast dich noch für keine Veranstaltung angemeldet.</p>
    <a href="/events" class="btn btn-outline-primary">Veranstaltungen anzeigen</a>
  </div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th><i class="bi bi-calendar-event"></i> Datum</th>
          <th>Veranstaltung</th>
          <th><i class="bi bi-person"></i> Teilnehmer</th>
          <th><i class="bi bi-clock"></i> Anmeldezeitpunkt</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $prev_eventGuid = ""; ?>
        <?php foreach ($enrollments as $enrollment): ?>
          <tr>
            <td class="text-nowrap">
              <?php if ($prev_eventGuid != $enrollment->eventGuid): ?>
                <?= html_out(event_datetime_out($enrollment->eventDate)) ?>
              <?php else: ?>
                &nbsp;
              <?php endif; ?>
            </td>
            <td>
              <?php if ($prev_eventGuid != $enrollment->eventGuid): ?>
                <a href="/events/<?= html_out($enrollment->eventGuid) ?>" class="text-decoration-none fw-semibold">
                  <?= html_out($enrollment->eventTitle) ?>
                </a>
              <?php else: ?>
                &nbsp;
              <?php endif; ?>
            </td>
            <td>
              <?= html_out($enrollment->subscriberName) ?>
            </td>
            <td class="text-nowrap text-muted small">
              <?= enrollment_datetime_out($enrollment->subscriberEnrollTimestamp) ?>
            </td>
            <td class="text-end">
              <?php
                $unenrollSubscriberGuid = $enrollment->subscriberGuid;
                $unenrollEventGuid      = $enrollment->eventGuid;
                $unenrollSubscriberName = $enrollment->subscriberName;
                $unenrollSource         = 'enrolled';
                $unenrollEventInfo      = $enrollment->eventTitle . ' — ' . event_datetime_out($enrollment->eventDate);
                include APP_ROOT . '/views/event/_unenroll_modal.php';
              ?>
            </td>
          </tr>
          <?php $prev_eventGuid = $enrollment->eventGuid; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
