<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($pageTitle ?? 'EveEnSys') ?> — EveEnSys</title>
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/events">EveEnSys</a>
    <div class="navbar-nav ms-auto align-items-center">
      <?php if (Session::isLoggedIn()): ?>
        <a class="nav-link me-2 text-white-50" href="/profile">
          Hello, <?= h(Session::getUserName()) ?>
        </a>
        <form method="post" action="/logout" class="d-inline">
          <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
          <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
        </form>
      <?php else: ?>
        <a class="nav-link" href="/login">Login</a>
        <a class="nav-link" href="/register">Register</a>
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
