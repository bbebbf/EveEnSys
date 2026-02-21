<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/events">Events</a></li>
    <li class="breadcrumb-item">
      <a href="/events/<?= h($event->eventGuid) ?>"><?= h($event->eventTitle) ?></a>
    </li>
    <li class="breadcrumb-item active">Delete</li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-danger">
      <div class="card-header bg-danger text-white">
        <strong>Confirm deletion</strong>
      </div>
      <div class="card-body">
        <p>Are you sure you want to permanently delete the event:</p>
        <p class="fw-bold fs-5"><?= h($event->eventTitle) ?></p>
        <p class="text-muted small">
          Scheduled: <?= h(date('d M Y H:i', strtotime($event->eventDate))) ?>
        </p>
        <p class="text-danger small">This action cannot be undone. All enrollments will also be removed.</p>
      </div>
      <div class="card-footer d-flex gap-2">
        <form method="post" action="/events/<?= h($event->eventGuid) ?>/delete">
          <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
          <button type="submit" class="btn btn-danger">Yes, delete</button>
        </form>
        <a href="/events/<?= h($event->eventGuid) ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </div>
  </div>
</div>
