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
        <th>Aktiv</th>
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
            <?php if ($u->userIsActive): ?>
              <span class="badge bg-success">Ja</span>
            <?php else: ?>
              <span class="badge bg-secondary">Nein</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u->userRole >= 1): ?>
              <span class="badge bg-danger">Admin</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u->userId <> Session::getUserId()): ?>
              <form method="post" action="/admin/users/<?= html_out($u->userGuid) ?>/toggle-admin">
                <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
                <?php if ($u->userRole >= 1): ?>
                  <button type="submit" class="btn btn-outline-primary btn-sm">Admin entziehen</button>
                <?php else: ?>
                  <?php if ($u->userIsActive): ?>
                      <button type="submit" class="btn btn-outline-primary btn-sm">Zum Admin ernennen</button>
                  <?php endif; ?>
                <?php endif; ?>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
