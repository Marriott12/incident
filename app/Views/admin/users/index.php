<?php
$roleLabels = include __DIR__ . '/../../../../config/role_labels.php';
$roleBadge  = [
    'admin'              => 'danger',
    'g_staff'            => 'warning',
    'formation_commander'=> 'info',
    'cpo'                => 'primary',
    'incident_officer'   => 'primary',
    'hq_readonly'        => 'secondary',
    'army_hq'            => 'dark',
];
function fmtLastLogin(?string $v): string {
    if (!$v) return '<span class="text-muted small">Never</span>';
    return '<span class="small">' . htmlspecialchars(date('d M Y H:i', strtotime($v))) . '</span>';
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>User Management</h4>
    <small class="text-muted"><?= (int)($total ?? 0) ?> registered users</small>
  </div>
  <a class="btn btn-warning fw-semibold px-4"
     href="<?= htmlspecialchars($base_path ?? '') ?>/admin/users/create">
    <i class="bi bi-person-plus me-1"></i>New User
  </a>
</div>

<!-- Search bar -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2 px-3">
    <form method="get" action="<?= htmlspecialchars($base_path ?? '') ?>/admin/users"
          class="d-flex gap-2 align-items-center">
      <div class="input-group input-group-sm" style="max-width:320px;">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input class="form-control" type="search" name="q"
               placeholder="Search name, email or role…"
               value="<?= htmlspecialchars($q ?? '') ?>">
      </div>
      <button class="btn btn-sm btn-outline-secondary" type="submit">Search</button>
      <?php if (!empty($q)): ?>
        <a class="btn btn-sm btn-outline-danger"
           href="<?= htmlspecialchars($base_path ?? '') ?>/admin/users">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th class="ps-3" style="width:40px;">#</th>
            <th>Name &amp; Rank</th>
            <th>Email</th>
            <th>Role</th>
            <th>Unit</th>
            <th>Formation</th>
            <th>Province</th>
            <th>Last Login</th>
            <th>Created</th>
            <th class="pe-3 text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($users ?? []) as $u):
            $uRole    = $u['role'] ?? 'incident_officer';
            $uBadge   = $roleBadge[$uRole] ?? 'secondary';
            $uLabel   = $roleLabels[$uRole] ?? ucwords(str_replace('_',' ',$uRole));
            $isMe     = ((int)($u['id'] ?? 0)) === (int)($_SESSION['user']['id'] ?? 0);
          ?>
          <tr>
            <td class="ps-3 text-muted small"><?= htmlspecialchars((string)$u['id']) ?></td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($u['name'] ?? '') ?>
                <?php if ($isMe): ?>
                  <span class="badge bg-light text-secondary border ms-1" style="font-size:.65rem;">You</span>
                <?php endif; ?>
              </div>
              <div class="text-muted small"><?= htmlspecialchars($u['rank'] ?? '') ?></div>
            </td>
            <td class="small"><?= htmlspecialchars($u['email'] ?? '') ?></td>
            <td>
              <span class="badge bg-<?= $uBadge ?><?= $uBadge === 'warning' ? ' text-dark' : '' ?>">
                <?= htmlspecialchars($uLabel) ?>
              </span>
            </td>
            <td class="small"><?= htmlspecialchars($u['unit'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($u['formation'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($u['province'] ?? '—') ?></td>
            <td><?= fmtLastLogin($u['last_login'] ?? null) ?></td>
            <td class="small text-muted">
              <?= $u['created_at'] ? htmlspecialchars(date('d M Y', strtotime($u['created_at']))) : '—' ?>
            </td>
            <td class="pe-3 text-end">
              <div class="d-flex gap-1 justify-content-end">
                <a class="btn btn-sm btn-outline-primary"
                   href="<?= htmlspecialchars($base_path ?? '') ?>/admin/users/<?= (int)$u['id'] ?>/edit"
                   title="Edit user">
                  <i class="bi bi-pencil"></i>
                </a>
                <?php if (!$isMe): ?>
                <form method="post"
                      action="<?= htmlspecialchars($base_path ?? '') ?>/admin/users/<?= (int)$u['id'] ?>/delete"
                      class="d-inline"
                      onsubmit="return confirm('Delete user <?= htmlspecialchars(addslashes($u['name'] ?? '')) ?>? This action cannot be undone.');">
                  <input type="hidden" name="csrf_token"
                         value="<?= htmlspecialchars(\App\Services\CsrfService::generateToken()) ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete user">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-danger disabled" disabled title="Cannot delete your own account">
                    <i class="bi bi-trash"></i>
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($users)): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-5">
              <i class="bi bi-people fs-3 d-block mb-2 opacity-25"></i>
              No users found<?= !empty($q) ? ' matching "' . htmlspecialchars($q) . '"' : '' ?>.
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php
  $total   = (int)($total   ?? 0);
  $perPage = (int)($perPage ?? 15);
  $page    = (int)($page    ?? 1);
  $pages   = max(1, (int)ceil($total / $perPage));
  if ($pages > 1): ?>
  <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center">
    <small class="text-muted">
      Showing <?= (($page-1)*$perPage)+1 ?>–<?= min($page*$perPage, $total) ?> of <?= $total ?>
    </small>
    <nav>
      <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="?q=<?= urlencode($q ?? '') ?>&page=<?= $page-1 ?>">‹ Prev</a>
        </li>
        <?php for ($p = max(1,$page-2); $p <= min($pages,$page+2); $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
          <a class="page-link" href="?q=<?= urlencode($q ?? '') ?>&page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
          <a class="page-link" href="?q=<?= urlencode($q ?? '') ?>&page=<?= $page+1 ?>">Next ›</a>
        </li>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>