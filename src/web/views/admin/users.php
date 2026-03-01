<div class="d-flex justify-content-between align-items-center mb-4">
  <h2>
    <?= html_out($pageTitle) ?>
    <?php if (count($users) > 0): ?>
      <span class="badge rounded-pill bg-secondary fs-6 ms-2 align-middle"><?= count($users) ?></span>
    <?php endif; ?>
  </h2>
</div>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Name</th>
        <th>E-Mail-Adresse</th>
        <th>Status</th>
        <th></th>
        <th>Rolle</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= html_out($u->userName) ?></td>
          <td><?= html_out($u->userEmail) ?></td>
          <td>
            <?php if ($u->userIsNew): ?>
              <span class="badge bg-warning">Neu</span>
            <?php elseif ($u->userIsActive): ?>
              <span class="badge bg-success">Aktiv</span>
            <?php else: ?>
              <span class="badge bg-secondary">Inaktiv</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u->userId <> Session::getUserId()): ?>
              <div class="d-flex gap-2">
                <form method="post" action="/admin/users/<?= html_out($u->userGuid) ?>/toggle-active">
                  <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
                  <?php if ($u->userIsActive): ?>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Deaktivieren</button>
                  <?php elseif (!$u->userIsNew): ?>
                    <button type="submit" class="btn btn-outline-success btn-sm">Aktivieren</button>
                  <?php endif; ?>
                </form>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u->userRole >= 1): ?>
              <span class="badge bg-danger">Admin</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u->userId <> Session::getUserId()): ?>
              <div class="d-flex gap-2">
                <form method="post" action="/admin/users/<?= html_out($u->userGuid) ?>/toggle-admin">
                  <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
                  <?php if ($u->userRole >= 1): ?>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Admin entziehen</button>
                  <?php elseif ($u->userIsActive): ?>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Zum Admin ernennen</button>
                  <?php endif; ?>
                </form>
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
