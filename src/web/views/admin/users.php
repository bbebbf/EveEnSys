<div class="d-flex justify-content-between align-items-center mb-4">
  <h2>
    <?= html_out($pageTitle) ?>
    <?php if (count($users) > 0): ?>
      <span class="badge rounded-pill bg-secondary fs-6 ms-2 align-middle"><?= count($users) ?></span>
    <?php endif; ?>
  </h2>
</div>

<?php if (count($users) > APP_CONFIG->getSearchbarStartsAtItemCount()): ?>
<div class="mb-4">
  <input type="search" id="user-search" class="form-control" placeholder="Benutzer suchen …" autocomplete="off">
</div>
<?php endif; ?>
<div id="no-search-results" class="text-center text-muted py-5 d-none">
  <p class="fs-5">Keine Benutzer gefunden.</p>
</div>

<div class="table-responsive" id="user-table">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>Name</th>
        <th>E-Mail-Adresse</th>
        <th>Letzter Login</th>
        <th>Aktionen</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <?php
          $oidcLabels = array_map(fn($id) => $oidcProviderInfos[$id->providerKey]->label ?? $id->providerKey, $oidcByUser[$u->userId] ?? []);
          $searchData = mb_strtolower($u->userName
            . ' ' . $u->userEmail
            . ' ' . implode(' ', $oidcLabels));
        ?>
        <?php if ($u->userId == Session::getUserId()): ?>
          <tr class="table-success" data-search="<?= html_out($searchData) ?>">
        <?php elseif (!$u->userIsActive && !$u->userIsNew): ?>
          <tr class="table-warning" data-search="<?= html_out($searchData) ?>">
        <?php else: ?>
          <tr data-search="<?= html_out($searchData) ?>">
        <?php endif; ?>
          <td>
            <?= html_out($u->userName) ?>
            <?php if ($u->userIsNew): ?>
              <span class="badge bg-success">Neu</span>
            <?php endif; ?>
            <?php if ($u->userRole >= 1): ?>
              <span class="badge bg-primary">Admin</span>
            <?php endif; ?>
            <?php if ($u->hasPendingPasswordReset): ?>
              <span class="badge bg-warning text-dark" title="Ausstehender Passwort-Reset">Passwort-Reset</span>
            <?php endif; ?>
            <?php if ($u->hasPendingActivationToken): ?>
              <span class="badge bg-secondary" title="Ausstehende Aktivierung">Aktivierung</span>
            <?php endif; ?>
          </td>
          <td>
            <?= html_out($u->userEmail) ?>
            <?php foreach ($oidcByUser[$u->userId] ?? [] as $identity): ?>
              <span class="badge bg-info text-dark">
                <?= html_out($oidcProviderInfos[$identity->providerKey]->label ?? $identity->providerKey) ?>
              </span>
            <?php endforeach; ?>
          </td>
          <td>
            <?= $u->userLastLogin ? html_out(date('d.m.Y H:i', strtotime($u->userLastLogin))) : '—' ?>
          </td>
          <td>
            <?php if ($u->userId <> Session::getUserId()): ?>
            <div class="d-flex gap-2">
              <form method="post" action="/admin/users/<?= html_out($u->userGuid) ?>/toggle-active">
                <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
                <?php if ($u->userIsActive): ?>
                  <button type="submit" class="btn btn-warning btn-md" title="Benutzer deaktivieren">
                    <i class="bi bi-x-circle"></i>
                  </button>
                <?php elseif (!$u->userIsNew): ?>
                  <button type="submit" class="btn btn-outline-success btn-md" title="Benutzer reaktivieren">
                    <i class="bi bi-x-circle"></i>
                  </button>
                <?php else: ?>
                  <button class="btn btn-primary btn-md invisible">
                    <i class="bi bi-exclamation-square"></i>
                  </button>
                <?php endif; ?>
              </form>
              <form method="post" action="/admin/users/<?= html_out($u->userGuid) ?>/toggle-admin">
                <input type="hidden" name="_csrf" value="<?= html_out(Session::getCsrfToken()) ?>">
                <?php if ($u->userRole >= 1): ?>
                  <button type="submit" class="btn btn-outline-primary btn-md" title="Administrator-Rechte entziehen">
                    <i class="bi bi-shield-fill-exclamation"></i>
                  </button>
                <?php elseif ($u->userIsActive): ?>
                  <button type="submit" class="btn btn-primary btn-md" title="Zum Administrator ernennen">
                    <i class="bi bi-shield-fill-exclamation"></i>
                  </button>
                <?php else: ?>
                  <button class="btn btn-primary btn-md invisible">
                    <i class="bi bi-exclamation-square"></i>
                  </button>
                <?php endif; ?>
              </form>
              <?php
                $deleteUserGuid = $u->userGuid;
                $deleteUserName = $u->userName;
                include APP_ROOT . '/views/admin/_delete_user_modal.php';
              ?>
            </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
  (function () {
    const input = document.getElementById('user-search');
    if (!input) return;
    const table = document.getElementById('user-table');
    const noResults = document.getElementById('no-search-results');
    input.addEventListener('input', function () {
      const term = this.value.toLowerCase().trim();
      let visible = 0;
      table.querySelectorAll('tr[data-search]').forEach(function (row) {
        const match = !term || row.dataset.search.includes(term);
        row.classList.toggle('d-none', !match);
        if (match) visible++;
      });
      noResults.classList.toggle('d-none', visible > 0);
      table.classList.toggle('d-none', visible === 0);
    });
  })();
</script>
