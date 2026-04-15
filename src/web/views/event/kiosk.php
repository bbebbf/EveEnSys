<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= html_out(APP_CONFIG->getAppTitleLong()) ?></title>
  <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: #0d1117;
      color: #e6edf3;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      height: 100vh;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    /* Header bar */
    .kiosk-header {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 0.75rem 2rem;
      background: #161b22;
      border-bottom: 1px solid #30363d;
      flex-shrink: 0;
    }
    .kiosk-header .app-title {
      font-size: 1rem;
      font-weight: 600;
      color: #58a6ff;
      letter-spacing: 0.05em;
      text-transform: uppercase;
    }

    /* Slide area */
    .kiosk-stage {
      flex: 1;
      position: relative;
      overflow: hidden;
    }

    .slide {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3rem 4rem;
      opacity: 0;
      transform: translateX(60px);
      transition: opacity 0.6s ease, transform 0.6s ease;
      pointer-events: none;
    }
    .slide.active {
      opacity: 1;
      transform: translateX(0);
      pointer-events: auto;
    }
    .slide.exit {
      opacity: 0;
      transform: translateX(-60px);
    }

    /* Date badge */
    .event-date-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: #1f6feb22;
      border: 1px solid #1f6feb55;
      color: #79c0ff;
      font-size: 1.1rem;
      font-weight: 600;
      padding: 0.4rem 1rem;
      border-radius: 2rem;
      margin-bottom: 1.5rem;
    }

    /* Title */
    .event-title {
      font-size: clamp(2rem, 5vw, 3.5rem);
      font-weight: 700;
      line-height: 1.15;
      color: #e6edf3;
      margin-bottom: 1rem;
    }

    /* Description */
    .event-description {
      font-size: 1.15rem;
      color: #8b949e;
      line-height: 1.6;
      margin-bottom: 2rem;
      max-height: 7rem;
      overflow: hidden;
      display: -webkit-box;
      -webkit-line-clamp: 4;
      -webkit-box-orient: vertical;
    }

    /* Meta pills */
    .event-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }
    .meta-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: #21262d;
      border: 1px solid #30363d;
      color: #c9d1d9;
      font-size: 0.95rem;
      padding: 0.35rem 0.85rem;
      border-radius: 0.5rem;
    }
    .meta-pill i {
      color: #58a6ff;
      font-size: 1rem;
    }

    /* QR code */
    .slide-inner {
      max-width: 960px;
      width: 100%;
      display: flex;
      align-items: center;
      gap: 3rem;
    }
    .slide-content {
      flex: 1;
      min-width: 0;
    }
    .event-qr-wrap {
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.6rem;
    }
    .event-qr-wrap canvas,
    .event-qr-wrap img {
      border-radius: 0.5rem;
      display: block;
    }
    .event-qr-label {
      font-size: 0.75rem;
      color: #484f58;
      text-align: center;
      white-space: nowrap;
    }

    /* Empty state */
    .kiosk-empty {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: #484f58;
      gap: 1rem;
    }
    .kiosk-empty i { font-size: 4rem; }
    .kiosk-empty p { font-size: 1.25rem; }

    /* Progress bar */
    .kiosk-footer {
      flex-shrink: 0;
      padding: 0.75rem 2rem;
      display: flex;
      align-items: center;
      gap: 1.5rem;
      background: #161b22;
      border-top: 1px solid #30363d;
    }
    .progress-bar-wrap {
      flex: 1;
      height: 3px;
      background: #30363d;
      border-radius: 2px;
      overflow: hidden;
    }
    .progress-bar-fill {
      height: 100%;
      background: #1f6feb;
      border-radius: 2px;
      width: 0%;
      transition: width linear;
    }
    .slide-counter {
      font-size: 0.85rem;
      color: #484f58;
      white-space: nowrap;
    }
  </style>
</head>
<body>

<div class="kiosk-header">
  <span class="app-title"><?= html_out(APP_CONFIG->getAppTitleLong()) ?></span>
</div>

<div class="kiosk-stage" id="stage">

  <?php $baseUrl = get_base_url(); ?>

  <?php if (empty($events)): ?>
    <div class="kiosk-empty">
      <i class="bi bi-calendar-x"></i>
      <p>Keine bevorstehenden Veranstaltungen</p>
    </div>
  <?php else: ?>
    <?php foreach ($events as $i => $event): ?>
      <?php $eventUrl = $baseUrl . '/events/' . $event->eventGuid; ?>
      <div class="slide<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>" data-event-url="<?= html_out($eventUrl) ?>">
        <div class="slide-inner">

          <div class="slide-content">
            <div class="event-date-badge">
              <i class="bi bi-calendar-event"></i>
              <?= html_out(event_datetime_out($event->eventDate)) ?>
            </div>

            <div class="event-title"><?= html_out($event->eventTitle) ?></div>

            <?php if ($event->eventDescription !== null): ?>
              <div class="event-description"><?= html_out($event->eventDescription) ?></div>
            <?php endif; ?>

            <div class="event-meta">
              <?php if (Session::isLoggedIn() && $event->eventLocation !== null): ?>
                <span class="meta-pill"><i class="bi bi-geo-alt"></i><?= html_out($event->eventLocation) ?></span>
              <?php endif; ?>
              <?php if ($event->eventDurationHours !== null): ?>
                <span class="meta-pill"><i class="bi bi-clock"></i><?= html_out($event->eventDurationHours) ?> Std.</span>
              <?php endif; ?>
              <?php if ($event->eventMaxSubscriber !== null): ?>
                <span class="meta-pill"><i class="bi bi-people"></i>Max. <?= html_out($event->eventMaxSubscriber) ?> Teilnehmer</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="event-qr-wrap">
            <div class="event-qr" id="qr-<?= $i ?>"></div>
          </div>

        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<?php if (!empty($events)): ?>
<div class="kiosk-footer">
  <div class="progress-bar-wrap"><div class="progress-bar-fill" id="progressBar"></div></div>
  <span class="slide-counter" id="slideCounter">1 / <?= count($events) ?></span>
</div>
<?php endif; ?>

<script>
(function () {
  document.querySelectorAll('.slide[data-event-url]').forEach(function (slide, i) {
    var url = slide.dataset.eventUrl;
    var container = document.getElementById('qr-' + i);
    if (!container || !url) return;
    new QRCode(container, {
      text: url,
      width: 300,
      height: 300,
      colorDark: '#c9d1d9',
      colorLight: '#0d1117',
      correctLevel: QRCode.CorrectLevel.M
    });
  });
})();

(function () {
  const SLIDE_DURATION = <?= APP_CONFIG->getKioskSlideDurationMs() ?>;

  const slides = document.querySelectorAll('.slide');
  if (slides.length <= 1) return;

  const progressBar = document.getElementById('progressBar');
  const slideCounter = document.getElementById('slideCounter');
  let current = 0;
  let startTime = null;
  let animFrame = null;

  function showSlide(next) {
    slides[current].classList.add('exit');
    slides[current].classList.remove('active');
    setTimeout(() => slides[current === next ? current : current].classList.remove('exit'), 650);

    current = next;
    slides[current].classList.remove('exit');
    slides[current].classList.add('active');
    slideCounter.textContent = (current + 1) + ' / ' + slides.length;
  }

  function startProgress() {
    startTime = performance.now();
    progressBar.style.transition = 'none';
    progressBar.style.width = '0%';

    requestAnimationFrame(() => {
      progressBar.style.transition = 'width ' + SLIDE_DURATION + 'ms linear';
      progressBar.style.width = '100%';
    });

    clearTimeout(animFrame);
    animFrame = setTimeout(() => {
      const next = (current + 1) % slides.length;
      showSlide(next);
      startProgress();
    }, SLIDE_DURATION);
  }

  startProgress();
})();
</script>
</body>
</html>
