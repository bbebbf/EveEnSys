<?php
// Helper: return posted value (underscore key), fall back to EventDto property (camelCase), then $fallback
$val = function(string $postKey, string $dtoProp = '', mixed $fallback = '') use ($old, $event): string {
    if (!empty($old) && array_key_exists($postKey, $old)) {
        return (string)$old[$postKey];
    }
    if ($event !== null && $dtoProp !== '') {
        return (string)($event->$dtoProp ?? $fallback);
    }
    return (string)$fallback;
};

$isEdit = $event !== null;
$action = $isEdit ? '/events/' . $event->eventGuid . '/edit' : '/events/create';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/events">Veranstaltungen</a></li>
    <?php if ($isEdit): ?>
      <li class="breadcrumb-item">
        <a href="/events/<?= h($event->eventGuid) ?>"><?= h($event->eventTitle) ?></a>
      </li>
    <?php endif; ?>
    <li class="breadcrumb-item active"><?= $isEdit ? 'Bearbeiten' : 'Veranstaltung erstellen' ?></li>
  </ol>
</nav>

<h2 class="mb-4"><?= $isEdit ? 'Veranstaltung bearbeiten' : 'Veranstaltung erstellen' ?></h2>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $error): ?>
        <li><?= h($error) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-md-8">
    <form method="post" action="<?= h($action) ?>" novalidate>
      <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">

      <div class="mb-3">
        <label for="event_title" class="form-label">Titel <span class="text-danger">*</span></label>
        <input type="text"
               class="form-control <?= isset($errors['event_title']) ? 'is-invalid' : '' ?>"
               id="event_title" name="event_title"
               value="<?= h($val('event_title', 'eventTitle')) ?>"
               required maxlength="150" autofocus>
        <?php if (isset($errors['event_title'])): ?>
          <div class="invalid-feedback"><?= h($errors['event_title']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="event_date" class="form-label">Datum &amp; Uhrzeit <span class="text-danger">*</span></label>
        <?php
        // Format the existing date for datetime-local input (Y-m-d\TH:i)
        $dateValue = '';
        if (!empty($old['event_date'])) {
            $dateValue = $old['event_date'];
        } elseif ($event !== null && $event->eventDate) {
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $event->eventDate);
            $dateValue = $dt ? $dt->format('Y-m-d\TH:i') : '';
        }
        ?>
        <input type="datetime-local"
               class="form-control <?= isset($errors['event_date']) ? 'is-invalid' : '' ?>"
               id="event_date" name="event_date"
               value="<?= h($dateValue) ?>"
               required>
        <?php if (isset($errors['event_date'])): ?>
          <div class="invalid-feedback"><?= h($errors['event_date']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="event_description" class="form-label">Beschreibung</label>
        <textarea class="form-control"
                  id="event_description" name="event_description"
                  rows="4"><?= h($val('event_description', 'eventDescription')) ?></textarea>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="event_duration_hours" class="form-label">Dauer (Stunden)</label>
          <input type="number"
                 class="form-control <?= isset($errors['event_duration_hours']) ? 'is-invalid' : '' ?>"
                 id="event_duration_hours" name="event_duration_hours"
                 value="<?= h($val('event_duration_hours', 'eventDurationHours')) ?>"
                 step="0.5" min="0.5">
          <?php if (isset($errors['event_duration_hours'])): ?>
            <div class="invalid-feedback"><?= h($errors['event_duration_hours']) ?></div>
          <?php endif; ?>
        </div>

        <div class="col-md-6 mb-3">
          <label for="event_max_subscriber" class="form-label">Max. teilnehmende Personen</label>
          <input type="number"
                 class="form-control <?= isset($errors['event_max_subscriber']) ? 'is-invalid' : '' ?>"
                 id="event_max_subscriber" name="event_max_subscriber"
                 value="<?= h($val('event_max_subscriber', 'eventMaxSubscriber')) ?>"
                 min="1" step="1">
          <?php if (isset($errors['event_max_subscriber'])): ?>
            <div class="invalid-feedback"><?= h($errors['event_max_subscriber']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <?= $isEdit ? 'Änderungen speichern' : 'Veranstaltung erstellen' ?>
        </button>
        <?php if ($isEdit): ?>
          <a href="/events/<?= h($event->eventGuid) ?>" class="btn btn-outline-secondary">Abbrechen</a>
        <?php else: ?>
          <a href="/events" class="btn btn-outline-secondary">Abbrechen</a>
        <?php endif; ?>
      </div>

    </form>
  </div>
</div>
