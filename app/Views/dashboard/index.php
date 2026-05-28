<?php
// Badge helpers
function threatBadge(string $level): string {
    $map = ['critical'=>'danger','high'=>'warning','moderate'=>'success','low'=>'light'];
    $cls = $map[$level] ?? 'secondary';
    $dark = in_array($level, ['moderate','low'], true) ? ' text-dark' : ' text-white';
    return '<span class="badge bg-' . $cls . $dark . '">'
         . htmlspecialchars(ucfirst($level)) . '</span>';
}
function statusBadge(string $status): string {
    $map = ['open'=>'primary','contained'=>'warning','closed'=>'secondary','under_review'=>'info'];
    $cls = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_',' ',$status));
    return '<span class="badge bg-' . $cls . '">' . htmlspecialchars($label) . '</span>';
}
function fmtDate(string $dt): string {
    return $dt ? date('d M Y H:i', strtotime($dt)) : '—';
}

$lusakaNow = new DateTimeImmutable('now', new DateTimeZone('Africa/Lusaka'));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-0"><i class="bi bi-speedometer2 me-2 text-primary"></i><?= htmlspecialchars($landing_title ?? 'Operational Dashboard') ?></h4>
    <small class="text-muted"><?= htmlspecialchars($lusakaNow->format('l, d F Y - H:i')) ?> CAT (Lusaka) &nbsp;|&nbsp; <?= htmlspecialchars($_SESSION['user']['unit'] ?? '') ?></small>
  </div>
  <a href="<?= htmlspecialchars($base_path) ?>/incidents/create" class="btn btn-warning fw-semibold px-4">
    <i class="bi bi-plus-circle me-1"></i>New Incident
  </a>
</div>

<?php if (!empty($landing_description)): ?>
<div class="alert alert-info border-0 rounded-3 mb-4">
  <div class="d-flex gap-3 align-items-start">
    <div class="fs-4 text-primary"><i class="bi bi-info-circle-fill"></i></div>
    <div>
      <div class="fw-semibold">Quick Overview</div>
      <div class="small text-muted"><?= htmlspecialchars($landing_description) ?></div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <!-- Total Incidents -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm stat-card h-100" style="border-left:4px solid #0d6efd !important;">
      <div class="card-body d-flex justify-content-between align-items-center p-3">
        <div>
          <div class="text-muted small text-uppercase fw-semibold mb-1">Total</div>
          <div class="stat-number text-primary"><?= (int)($stats['total'] ?? 0) ?></div>
          <div class="small text-muted">All incidents</div>
        </div>
        <i class="bi bi-journal-text text-primary" style="font-size:2.2rem;opacity:.2;"></i>
      </div>
    </div>
  </div>

  <!-- Open -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm stat-card h-100" style="border-left:4px solid #198754 !important;">
      <div class="card-body d-flex justify-content-between align-items-center p-3">
        <div>
          <div class="text-muted small text-uppercase fw-semibold mb-1">Open</div>
          <div class="stat-number text-success"><?= (int)($stats['total_open'] ?? 0) ?></div>
          <div class="small text-muted">Active incidents</div>
        </div>
        <i class="bi bi-exclamation-circle text-success" style="font-size:2.2rem;opacity:.2;"></i>
      </div>
    </div>
  </div>

  <!-- High / Critical -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm stat-card h-100" style="border-left:4px solid #dc3545 !important;">
      <div class="card-body d-flex justify-content-between align-items-center p-3">
        <div>
          <div class="text-muted small text-uppercase fw-semibold mb-1">High / Critical</div>
          <div class="stat-number text-danger"><?= (int)($stats['high_critical'] ?? 0) ?></div>
          <div class="small text-muted">Elevated threat</div>
        </div>
        <i class="bi bi-shield-exclamation text-danger" style="font-size:2.2rem;opacity:.2;"></i>
      </div>
    </div>
  </div>

  <!-- Today -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm stat-card h-100" style="border-left:4px solid #fd7e14 !important;">
      <div class="card-body d-flex justify-content-between align-items-center p-3">
        <div>
          <div class="text-muted small text-uppercase fw-semibold mb-1">Today</div>
          <div class="stat-number text-warning"><?= (int)($stats['today'] ?? 0) ?></div>
          <div class="small text-muted">Reported today</div>
        </div>
        <i class="bi bi-calendar-check text-warning" style="font-size:2.2rem;opacity:.2;"></i>
      </div>
    </div>
  </div>

  <!-- Resolved -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm stat-card h-100" style="border-left:4px solid #6f42c1 !important;">
      <div class="card-body d-flex justify-content-between align-items-center p-3">
        <div>
          <div class="text-muted small text-uppercase fw-semibold mb-1">Resolved</div>
          <div class="stat-number text-dark"><?= (int)($stats['total_resolved'] ?? 0) ?></div>
          <div class="small text-muted">Closed cases</div>
        </div>
        <i class="bi bi-check-circle text-dark" style="font-size:2.2rem;opacity:.2;"></i>
      </div>
    </div>
  </div>

  <!-- Avg Response Time -->
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm stat-card h-100" style="border-left:4px solid #0dcaf0 !important;">
      <div class="card-body d-flex justify-content-between align-items-center p-3">
        <div>
          <div class="text-muted small text-uppercase fw-semibold mb-1">Avg Response</div>
          <div class="stat-number text-info">
            <?php if ($avgResponseTime !== null): ?>
            <?= round((float)$avgResponseTime, 1) ?>h
            <?php else: ?>
            —
            <?php endif; ?>
          </div>
          <div class="small text-muted">Hours to resolve</div>
        </div>
        <i class="bi bi-hourglass-split text-info" style="font-size:2.2rem;opacity:.2;"></i>
      </div>
    </div>
  </div>
</div>

<!-- Threat Breakdown Bar -->
<?php
$byThreat = $stats['by_threat'] ?? [];
$byStatus = $stats['by_status'] ?? [];
$total    = max(1, $stats['total'] ?? 1);
?>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-3">
        <div class="fw-semibold small text-uppercase text-muted mb-2"><i class="bi bi-bar-chart-fill me-1"></i>Threat Level Breakdown</div>
        <?php foreach (['critical'=>'danger','high'=>'warning','moderate'=>'info','low'=>'success'] as $lvl=>$cls): ?>
        <div class="d-flex align-items-center mb-1 gap-2">
          <span class="badge bg-<?= $cls ?>" style="width:72px;"><?= ucfirst($lvl) ?></span>
          <div class="progress flex-grow-1" style="height:8px;">
            <div class="progress-bar bg-<?= $cls ?>" style="width:<?= round(($byThreat[$lvl]??0)/$total*100) ?>%"></div>
          </div>
          <span class="small text-muted" style="width:24px;text-align:right;"><?= $byThreat[$lvl]??0 ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-3">
        <div class="fw-semibold small text-uppercase text-muted mb-2"><i class="bi bi-pie-chart-fill me-1"></i>Status Breakdown</div>
        <?php foreach (['open'=>'primary','contained'=>'warning','under_review'=>'info','closed'=>'secondary'] as $s=>$cls): ?>
        <div class="d-flex align-items-center mb-1 gap-2">
          <span class="badge bg-<?= $cls ?>" style="width:90px;"><?= ucwords(str_replace('_',' ',$s)) ?></span>
          <div class="progress flex-grow-1" style="height:8px;">
            <div class="progress-bar bg-<?= $cls ?>" style="width:<?= round(($byStatus[$s]??0)/$total*100) ?>%"></div>
          </div>
          <span class="small text-muted" style="width:24px;text-align:right;"><?= $byStatus[$s]??0 ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- Top Province and Unit Summary -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small text-uppercase text-muted"><i class="bi bi-flag-fill me-1"></i>Top Provinces</span>
      </div>
      <div class="card-body p-3">
        <?php if (!empty($topProvinces)): ?>
          <ul class="list-group list-group-flush">
            <?php foreach (array_slice($topProvinces, 0, 6) as $row): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
              <span><?= htmlspecialchars($row['label']) ?></span>
              <span class="badge bg-primary rounded-pill"><?= (int)$row['count'] ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-center text-muted py-4">No recent province data available.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small text-uppercase text-muted"><i class="bi bi-building me-1"></i>Top Reporting Units</span>
      </div>
      <div class="card-body p-3">
        <?php if (!empty($topUnits)): ?>
          <ul class="list-group list-group-flush">
            <?php foreach (array_slice($topUnits, 0, 6) as $row): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
              <span><?= htmlspecialchars($row['label']) ?></span>
              <span class="badge bg-secondary rounded-pill"><?= (int)$row['count'] ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-center text-muted py-4">No recent unit data available.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Geographic Visualization -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small text-uppercase text-muted"><i class="bi bi-geo-alt me-1"></i>Zambia Incident Heatmap</span>
      </div>
      <div class="card-body p-0">
           <div id="dashboard-incident-map" style="height:400px;background:#f5f5f5;"
             data-province="<?= htmlspecialchars($_SESSION['user']['province'] ?? '') ?>">
          <div class="d-flex justify-content-center align-items-center h-100">
            <span class="text-muted small">Loading map...</span>
          </div>
        </div>
      </div>
      <div class="card-footer bg-light border-top py-2">
        <div class="small text-muted d-flex gap-3 flex-wrap">
          <div class="d-flex align-items-center gap-2">
            <span style="display:inline-block;width:12px;height:12px;background:#dc3545;border-radius:50%;"></span>
            <span>Critical</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span style="display:inline-block;width:12px;height:12px;background:#fd7e14;border-radius:50%;"></span>
            <span>High</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span style="display:inline-block;width:12px;height:12px;background:#0dcaf0;border-radius:50%;"></span>
            <span>Moderate</span>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span style="display:inline-block;width:12px;height:12px;background:#198754;border-radius:50%;"></span>
            <span>Low</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Pending Queue (role-specific) -->
<?php if (!empty($pending)): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
    <span class="fw-semibold"><i class="bi bi-clock-history me-1"></i>Pending for Review
      <span class="badge bg-light text-secondary ms-1"><?= count($pending) ?></span>
    </span>
    <small class="text-muted">Items awaiting your action</small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 incidents-table">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Incident No.</th>
            <th>Type</th>
            <th>Threat</th>
            <th>Status</th>
            <th>AO Sector</th>
            <th>Unit</th>
            <th>Reported At</th>
            <th class="pe-3"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($pending ?? []) as $inc): ?>
          <tr class="align-middle" onclick="location.href='<?= htmlspecialchars($base_path) ?>/incidents/<?= (int)$inc['id'] ?>'" style="cursor:pointer;">
            <td class="ps-3 fw-semibold"><?= htmlspecialchars($inc['incident_number']) ?></td>
            <td><?= htmlspecialchars(ucwords(str_replace('_',' ',$inc['type'] ?? ''))) ?></td>
            <td><?= threatBadge($inc['threat_level'] ?? 'low') ?></td>
            <td><?= statusBadge($inc['status'] ?? 'open') ?></td>
            <td><?= htmlspecialchars($inc['ao_sector'] ?? '—') ?></td>
            <td><?= htmlspecialchars($inc['reporting_unit'] ?? '—') ?></td>
            <td class="text-nowrap"><?= fmtDate($inc['reported_at'] ?? $inc['created_at'] ?? '') ?></td>
            <td class="pe-3 text-end">
              <a href="<?= htmlspecialchars($base_path) ?>/incidents/<?= (int)$inc['id'] ?>" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation()">View</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($pending)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No pending items.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Incidents Table -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span class="fw-semibold"><i class="bi bi-list-ul me-1"></i>Recent Incidents
      <span class="badge bg-light text-secondary ms-1"><?= count($incidents ?? []) ?></span>
    </span>
    <div class="d-flex gap-2 flex-wrap align-items-center">
      <!-- Quick filters -->
      <div class="btn-group btn-group-sm" role="group" id="filterBtns">
        <button class="btn btn-outline-secondary active" data-filter="">All</button>
        <button class="btn btn-outline-primary" data-filter="open">Open</button>
        <button class="btn btn-outline-danger" data-filter="critical">Critical</button>
        <button class="btn btn-outline-warning" data-filter="high">High</button>
      </div>
      <!-- Search -->
      <input type="search" id="incidentSearch" class="form-control form-control-sm" placeholder="Search…" style="width:180px;">
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 incidents-table" id="incidentTable">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Incident No.</th>
            <th>Type</th>
            <th>Threat</th>
            <th>Status</th>
            <th>AO Sector</th>
            <th>Unit</th>
            <th>Reported At</th>
            <th class="pe-3"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($incidents ?? []) as $inc): ?>
          <tr class="align-middle"
              data-status="<?= htmlspecialchars($inc['status'] ?? '') ?>"
              data-threat="<?= htmlspecialchars($inc['threat_level'] ?? '') ?>"
              onclick="location.href='<?= htmlspecialchars($base_path) ?>/incidents/<?= (int)$inc['id'] ?>'"
              style="cursor:pointer;">
            <td class="ps-3 fw-semibold"><?= htmlspecialchars($inc['incident_number']) ?></td>
            <td><?= htmlspecialchars(ucwords(str_replace('_',' ',$inc['type'] ?? ''))) ?></td>
            <td><?= threatBadge($inc['threat_level'] ?? 'low') ?></td>
            <td><?= statusBadge($inc['status'] ?? 'open') ?></td>
            <td><?= htmlspecialchars($inc['ao_sector'] ?? '—') ?></td>
            <td><?= htmlspecialchars($inc['reporting_unit'] ?? '—') ?></td>
            <td class="text-nowrap"><?= fmtDate($inc['reported_at'] ?? $inc['created_at'] ?? '') ?></td>
            <td class="pe-3 text-end">
              <a href="<?= htmlspecialchars($base_path) ?>/incidents/<?= (int)$inc['id'] ?>"
                 class="btn btn-sm btn-outline-primary"
                 onclick="event.stopPropagation()">
                <i class="bi bi-eye"></i>
              </a>
              <form method="post"
                    action="<?= htmlspecialchars($base_path) ?>/incidents/<?= (int)$inc['id'] ?>/delete"
                    class="d-inline"
                    onsubmit="event.stopPropagation(); return confirm('Delete incident <?= htmlspecialchars($inc['incident_number']) ?>? This action cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation()">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($incidents)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No incidents recorded.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Activity Feed Section -->
<?php if (!empty($recentActivity)): ?>
<div class="row g-3 mt-4">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small text-uppercase text-muted"><i class="bi bi-lightning-charge me-1"></i>Recent Activity</span>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush" style="max-height:350px;overflow-y:auto;">
          <?php foreach (array_slice($recentActivity, 0, 8) as $activity): ?>
            <div class="list-group-item px-3 py-2 border-bottom" style="font-size:.9rem;">
              <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                  <div class="fw-semibold small"><?= htmlspecialchars($activity['action'] ?? 'System Action') ?></div>
                  <?php if ($activity['incident_number'] ?? null): ?>
                    <div class="text-muted small">
                      Incident #<?= htmlspecialchars($activity['incident_number']) ?>
                      <?php if ($activity['name'] ?? null): ?>
                        by <strong><?= htmlspecialchars($activity['name']) ?></strong>
                      <?php endif; ?>
                    </div>
                  <?php else: ?>
                    <div class="text-muted small">
                      <?php if ($activity['name'] ?? null): ?>
                        <strong><?= htmlspecialchars($activity['name']) ?></strong>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="text-muted small" style="white-space:nowrap;">
                  <?php 
                    $created = strtotime($activity['created_at'] ?? 'now');
                    $diff = time() - $created;
                    if ($diff < 60) echo 'Now';
                    elseif ($diff < 3600) echo round($diff / 60) . 'm ago';
                    elseif ($diff < 86400) echo round($diff / 3600) . 'h ago';
                    else echo date('M d', $created);
                  ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

