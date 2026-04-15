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
    <p class="fs-5">Es liegen noch keine Anmeldungen vor.</p>
    <a href="/events" class="btn btn-outline-primary">Veranstaltungen anzeigen</a>
  </div>
<?php else: ?>

  <?php if (count($enrollments) > APP_CONFIG->getSearchbarStartsAtItemCount()): ?>
  <div class="mb-4">
    <input type="search" id="enrollment-search" class="form-control" placeholder="Anmeldungen suchen …" autocomplete="off">
  </div>
  <?php endif; ?>
  <div id="no-search-results" class="text-center text-muted py-5 d-none">
    <p class="fs-5">Keine Anmeldungen gefunden.</p>
  </div>

  <div class="table-responsive" id="enrollment-table">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th><i class="bi bi-calendar-event"></i> Datum</th>
          <th>Veranstaltung</th>
          <th><i class="bi bi-person"></i> Teilnehmer</th>
          <th><i class="bi bi-person-check"></i> Angemeldet von</th>
          <th><i class="bi bi-clock"></i> Anmeldezeitpunkt</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php $prev_eventGuid = ""; ?>
        <?php foreach ($enrollments as $enrollment): ?>
          <?php $searchData = mb_strtolower(
            $enrollment->eventTitle . ' '
            . event_datetime_out($enrollment->eventDate) . ' '
            . $enrollment->subscriberName . ' '
            . $enrollment->creatorUserName
          ); ?>
          <tr data-search="<?= html_out($searchData) ?>">
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
            <td class="text-muted small">
              <?= html_out($enrollment->creatorUserName) ?>
            </td>
            <td class="text-nowrap text-muted small">
              <?= enrollment_datetime_out($enrollment->subscriberEnrollTimestamp) ?>
            </td>
            <td class="text-end">
              <?php
                $unenrollSubscriberGuid = $enrollment->subscriberGuid;
                $unenrollEventGuid      = $enrollment->eventGuid;
                $unenrollSubscriberName = $enrollment->subscriberName;
                $unenrollSource         = 'admin_enrolled';
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
  <script>
    (function () {
      const input = document.getElementById('enrollment-search');
      if (!input) return;
      const table = document.getElementById('enrollment-table');
      const noResults = document.getElementById('no-search-results');
      input.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        let visible = 0;
        table.querySelectorAll('tr[data-search]').forEach(function (row) {
          const match = !term || row.dataset.search.includes(term);
          row.classList.toggle('d-none', !match);
          if (match) visible++;
        });
        noResults.classList.toggle('d-none', visible > 0);
        table.classList.toggle('d-none', visible === 0);
      });
    })();
  </script>
<?php endif; ?>
