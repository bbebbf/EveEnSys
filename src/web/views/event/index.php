<div class="d-flex justify-content-between align-items-center mb-4">
  <h2>
    <?= html_out($pageTitle) ?>
    <?php if (count($events) > 0): ?>
      <span class="badge rounded-pill bg-secondary fs-6 ms-2 align-middle"><?= count($events) ?></span>
    <?php endif; ?>
  </h2>
  <?php if (Session::isLoggedIn()): ?>
    <div>
      <a href="/events/create" class="text-decoration-none d-none d-lg-inline text-primary me-1 fs-5">
          Veranstaltung erstellen
      </a>
      <a href="/events/create" class="btn btn-primary btn-lg">
        <i class="bi bi-calendar-plus"></i>
      </a>
    </div>
  <?php endif; ?>
</div>

<?php if (empty($events)): ?>
  <div class="text-center text-muted py-5">
    <p class="fs-5">Keine bevorstehenden Veranstaltungen.</p>
    <a href="/events/create" class="btn btn-outline-primary">Jetzt erstellen</a>
  </div>
<?php else: ?>
  <?php if (count($events) > APP_CONFIG->getSearchbarStartsAtItemCount()): ?>
  <div class="mb-4">
    <input type="search" id="event-search" class="form-control" placeholder="Veranstaltungen durchsuchen …" autocomplete="off">
  </div>
  <?php endif; ?>
  <div id="no-search-results" class="text-center text-muted py-5 d-none">
    <p class="fs-5">Keine Veranstaltungen gefunden.</p>
  </div>
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="event-cards">
    <?php foreach ($events as $event): ?>
      <?php
        $searchData = mb_strtolower($event->eventTitle .
            ' ' . ($event->eventDescription ?? '') .
            ' ' . ($event->eventLocation ?? '') .
            ' ' . ($event->creatorName ?? ''));
      ?>
      <div class="col" data-search="<?= html_out($searchData) ?>">


        <div class="card h-100 shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>
              <i class="bi bi-calendar-event"></i>
              <?= event_date_out($event->eventDate) ?>
              <?php if ($event->eventIsNew): ?>
                <span class="badge bg-success">Neu</span>
              <?php endif; ?>
            </span>
            <span>
              <?php if ($isAdmin): ?>
                <div class="mt-2 position-relative" style="z-index: 2;">
                  <form method="post" action="/events/<?= html_out($event->eventGuid) ?>/toggle-visible">
                    <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
                    <button type="submit" class="btn btn-sm <?= $event->eventIsVisible ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                      <?= $event->eventIsVisible ? 'Verstecken' : 'Sichtbar machen' ?>
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </span>
          </div>
          <div class="card-body">
            <h5 class="card-title d-flex justify-content-between">
            <a href="/events/<?= html_out($event->eventGuid) ?><?= mb_strlen($origin ?? '') > 0 ? '?origin=' . $origin : ''; ?>" class="text-decoration-none stretched-link">
                <?= html_out($event->eventTitle) ?>
              </a>
            </h5>
            <?php if ($event->eventDescription !== null): ?>
              <p class="card-text text-muted small">
                <?= html_out(mb_strimwidth($event->eventDescription, 0, 120, '…')) ?>
              </p>
            <?php endif; ?>
          </div>
          <div class="card-footer text-muted small d-flex justify-content-between align-items-center">
            <span>
            <?php if ($event->eventDurationHours !== null): ?>
              &nbsp;<i class="bi bi-clock-history"></i> <?= html_out($event->eventDurationHours) ?> h
            <?php endif; ?>
            <?php if ($event->eventMaxSubscriber !== null): ?>
              &nbsp;<i class="bi bi-people"></i> max. <?= html_out($event->eventMaxSubscriber) ?>
            <?php endif; ?>
            <?php if ($event->eventLocation !== null): ?>
              &nbsp;<i class="bi bi-geo-alt"></i> <?= html_out($event->eventLocation) ?>
            <?php endif; ?>
            </span>
            <span class="ms-2 text-body-tertiary">
              <?= html_out($event->creatorName ?? 'Unbekannt') ?>
            </span>
          </div>
        </div>


      </div>
    <?php endforeach; ?>
  </div>
  <script>
    (function () {
      const input = document.getElementById('event-search');
      if (!input) return;
      const grid  = document.getElementById('event-cards');
      const noResults = document.getElementById('no-search-results');
      input.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        let visible = 0;
        grid.querySelectorAll('.col[data-search]').forEach(function (col) {
          const match = !term || col.dataset.search.includes(term);
          col.classList.toggle('d-none', !match);
          if (match) visible++;
        });
        noResults.classList.toggle('d-none', visible > 0);
        grid.classList.toggle('d-none', visible === 0);
      });
    })();
  </script>
<?php endif; ?>
