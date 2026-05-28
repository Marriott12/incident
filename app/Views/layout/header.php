<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title ?? 'Joint Command Post') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <link rel="stylesheet" href="<?= htmlspecialchars($base_path ?? '') ?>/css/custom.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body data-base-path="<?= htmlspecialchars($base_path ?? '') ?>">
<?php
$_bp      = $base_path ?? '';
$_uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$_role    = $_SESSION['user']['role'] ?? null;
$_uname   = $_SESSION['user']['name'] ?? 'Guest';
$_uemail  = $_SESSION['user']['email'] ?? '';
$_roleLabels = include __DIR__ . '/../../../config/role_labels.php';
$_roleDisplay = $_roleLabels[$_role ?? ''] ?? str_replace('_', ' ', (string)($_role ?? ''));
$_isAdmin = in_array($_role, ['admin'], true);
$_isCO    = in_array($_role, ['admin','commanding_officer'], true);
function _navActive(string $prefix, string $base, string $uri): string {
    $full = rtrim($base . $prefix, '/');
    return ($uri === $full || str_starts_with($uri, $full . '/')) ? ' active' : '';
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= $_bp ?>/dashboard">
      <i class="bi bi-shield-fill-exclamation text-warning fs-5"></i>
      <span>Joint Command Post</span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link<?= _navActive('/dashboard', $_bp, $_uri) ?>" href="<?= $_bp ?>/dashboard">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= _navActive('/incidents', $_bp, $_uri) ?>" href="<?= $_bp ?>/incidents">
            <i class="bi bi-journal-text me-1"></i>Incidents
          </a>
        </li>
        <?php if ($_isCO): ?>
        <li class="nav-item">
          <a class="nav-link<?= _navActive('/reports', $_bp, $_uri) ?>" href="<?= $_bp ?>/reports">
            <i class="bi bi-file-earmark-bar-graph me-1"></i>Reports
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?= _navActive('/analytics', $_bp, $_uri) ?>" href="<?= $_bp ?>/analytics">
            <i class="bi bi-graph-up-arrow me-1"></i>Analytics
          </a>
        </li>
        <?php endif; ?>
        <?php if ($_isAdmin): ?>
        <li class="nav-item">
          <a class="nav-link<?= _navActive('/admin', $_bp, $_uri) ?>" href="<?= $_bp ?>/admin/users">
            <i class="bi bi-people me-1"></i>Admin
          </a>
        </li>
        <?php endif; ?>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= $_bp ?>/incidents/create" class="btn btn-warning btn-sm fw-semibold px-3">
          <i class="bi bi-plus-circle me-1"></i>New Incident
        </a>
        <div class="dropdown">
          <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_uname) ?>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width:220px;">
            <li class="px-3 py-2">
              <div class="fw-semibold"><?= htmlspecialchars($_uname) ?></div>
              <div class="text-muted small"><?= htmlspecialchars($_uemail) ?></div>
              <span class="badge bg-secondary mt-1"><?= htmlspecialchars((string)$_roleDisplay) ?></span>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
              <form method="POST" action="<?= $_bp ?>/auth/logout" class="px-3 pb-2">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\App\Services\CsrfService::generateToken()) ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                  <i class="bi bi-box-arrow-right me-1"></i>Logout
                </button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>
<main class="container-fluid px-4 py-3">
