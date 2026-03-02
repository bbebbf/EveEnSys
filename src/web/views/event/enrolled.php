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
    <p class="fs-5">Sie haben sich noch für keine Veranstaltung angemeldet.</p>
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
                <?= html_out(event_date_out($enrollment->eventDate)) ?>
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
              <?= html_out($enrollment->subscriberEnrollTimestamp->format('d.m.Y H:i \U\h\r')) ?>
            </td>
            <td class="text-end">
              <a href="/events/<?= html_out($enrollment->eventGuid) ?>/unenroll/<?= html_out($enrollment->subscriberGuid) ?>"
                 class="btn btn-sm btn-outline-danger">
                Abmelden
              </a>
            </td>
          </tr>
          <?php $prev_eventGuid = $enrollment->eventGuid; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
