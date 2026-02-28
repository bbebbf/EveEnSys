<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle ?? '') ?> — <?= h(APP_TITLE_SHORT) ?></title>
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        crossorigin="anonymous">
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<nav class="navbar navbar-expand navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/"><?= h(APP_TITLE_SHORT) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <?php if (Session::isLoggedIn()): ?>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person"></i><span class="d-none d-md-inline">  <?= h(Session::getUserName()) ?><span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="/events/my"><i class="bi bi-calendar-event"></i> Meine Veranstaltungen</a></li>
              <li><a class="dropdown-item" href="/events/enrolled"><i class="bi bi-card-checklist"></i> Meine Anmeldungen</a></li>
              <div class="dropdown-divider"></div>
              <li><a class="dropdown-item" href="/events/all"><i class="bi bi-calendar-week"></i> Alle Veranstaltungen</a></li>

              <?php if (Session::isAdmin()): ?>
                <div class="dropdown-divider"></div>
                <li><a class="dropdown-item" href="/admin/users"><i class="bi bi-people"></i> Benutzerverwaltung</a></li>
              <?php endif; ?>

              <div class="dropdown-divider"></div>
              <li><a class="dropdown-item" href="/profile/<?= h(Session::getUserGuid()) ?>"><i class="bi bi-person-square"></i> Profil &amp; Passwort</a></li>
              <form method="post" action="/logout" class="dropdown-item">
                <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
                <button type="submit" class="btn border-0 p-0 w-100 text-start"><i class="bi bi-x-circle-fill"></i> Abmelden</button>
              </form>

            </ul>
          </li>
        </ul>
      <?php else: ?>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link" href="/login">Anmelden</a></li>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container mt-4">

<?php
$_flashSuccess = Session::getFlash('success');
$_flashError   = Session::getFlash('error');
?>
<?php if ($_flashSuccess !== null): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= h($_flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($_flashError !== null): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= h($_flashError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
