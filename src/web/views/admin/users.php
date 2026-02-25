<h2 class="mb-4">Benutzerverwaltung</h2>

<div class="table-responsive">
  <table class="table table-striped align-middle">
    <thead>
      <tr>
        <th>Name</th>
        <th>E-Mail</th>
        <th>Aktiv</th>
        <th>Rolle</th>
        <th>Letzter Login</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= h($u->userName) ?></td>
          <td><?= h($u->userEmail) ?></td>
          <td>
            <?php if ($u->userIsActive): ?>
              <span class="badge bg-success">Ja</span>
            <?php else: ?>
              <span class="badge bg-secondary">Nein</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($u->userRole >= 1): ?>
              <span class="badge bg-danger">Administrator</span>
            <?php endif; ?>
          </td>
          <td><?= h($u->userLastLogin ?? '—') ?></td>
          <td>
            <?php if ($u->userId <> Session::getUserId()): ?>
              <form method="post" action="/admin/users/<?= h($u->userGuid) ?>/toggle-admin">
                <input type="hidden" name="_csrf" value="<?= h(Session::getCsrfToken()) ?>">
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
