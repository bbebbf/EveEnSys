<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle ?? '') ?> — <?= h(APP_TITLE_SHORT) ?></title>
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/"><?= h(APP_TITLE_SHORT) ?></a>
    <div class="navbar-nav ms-auto align-items-center gap-2">
      <?php if (Session::isLoggedIn()): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?= h(Session::getUserName()) ?>
          </a>
          <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
            <a class="dropdown-item" href="/events/my">Meine Veranstaltungen</a>
            <a class="dropdown-item" href="/events/all">Alle Veranstaltungen</a>
            <?php if (Session::isAdmin()): ?>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="/admin/users">Benutzerverwaltung</a>
            <?php endif; ?>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="/profile/<?= h(Session::getUserGuid()) ?>">Profil &amp; Passwort</a>
            <div class="dropdown-divider"></div>
            <form method="post" action="/logout" class="dropdown-item">
              <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
              <button type="submit" class="btn border-0 p-0">Abmelden</button>
            </form>
          </div>
        </li>
      <?php else: ?>
        <a class="nav-link" href="/login">Anmelden</a>
        <a class="nav-link" href="/register">Registrieren</a>
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
