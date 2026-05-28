<?php
function listThreatBadge(string $level): string {
    $map = ['critical'=>'danger','high'=>'warning','moderate'=>'success','low'=>'light'];
    $cls = $map[$level] ?? 'secondary';
    $dark = in_array($level, ['moderate', 'low'], true) ? ' text-dark' : '';
    return '<span class="badge bg-' . $cls . $dark . '">' . htmlspecialchars(ucfirst($level)) . '</span>';
}
function listStatusBadge(string $status): string {
    $map = ['open'=>'primary','contained'=>'warning','closed'=>'secondary','under_review'=>'info'];
    $cls = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return '<span class="badge bg-' . $cls . '">' . htmlspecialchars($label) . '</span>';
}
function listFmt(?string $v): string {
    return $v ? date('d M Y  H:i', strtotime($v)) : '—';
}
$typeLabels = [
    'public_disorder'  => 'Public Disorder',
    'crowd_control'    => 'Crowd Control',
    'evacuation'       => 'Evacuation',
    'crime'            => 'Crime',
    'intelligence_tip' => 'Intel Tip',
    'other'            => 'Other',
];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="fw-bold mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>All Incidents</h4>
    <small class="text-muted"><?= count($incidents ?? []) ?> records visible to your role</small>
  </div>
  <a class="btn btn-warning fw-semibold px-4" href="<?= htmlspecialchars($base_path ?? '') ?>/incidents/create">
    <i class="bi bi-plus-circle me-1"></i>New Incident
  </a>
</div>

<!-- Filter bar -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2 px-3 d-flex flex-wrap gap-2 align-items-center">
    <div class="btn-group btn-group-sm" role="group" id="statusFilter">
      <button type="button" class="btn btn-outline-secondary active" data-filter-status="">All</button>
      <button type="button" class="btn btn-outline-primary"   data-filter-status="open">Open</button>
      <button type="button" class="btn btn-outline-info"      data-filter-status="under_review">Under Review</button>
      <button type="button" class="btn btn-outline-warning"   data-filter-status="contained">Contained</button>
      <button type="button" class="btn btn-outline-secondary" data-filter-status="closed">Closed</button>
    </div>
    <div class="btn-group btn-group-sm ms-2" role="group" id="threatFilter">
      <button type="button" class="btn btn-outline-secondary active" data-filter-threat="">All Threats</button>
      <button type="button" class="btn btn-outline-danger"  data-filter-threat="critical">Critical</button>
      <button type="button" class="btn btn-outline-warning" data-filter-threat="high">High</button>
    </div>
    <div class="ms-auto">
      <input type="search" id="incidentListSearch" class="form-control form-control-sm"
             placeholder="Search incident no., type, unit…" style="width:220px;">
    </div>
    <span id="incidentCount" class="small text-muted text-nowrap"></span>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 incidents-table" id="incidentListTable">
        <thead class="table-light">
          <tr>
            <th class="ps-3">Incident No.</th>
            <th>Type</th>
            <th>Threat</th>
            <th>Status</th>
            <th>Province</th>
            <th>AO Sector</th>
            <th>Unit</th>
            <th>Reported At</th>
            <th>Casualties</th>
            <th class="pe-3"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($incidents ?? []) as $inc): ?>
          <tr class="align-middle"
              data-status="<?= htmlspecialchars($inc['status'] ?? '') ?>"
              data-threat="<?= htmlspecialchars($inc['threat_level'] ?? '') ?>"
              onclick="location.href='<?= htmlspecialchars($base_path ?? '') ?>/incidents/<?= (int)$inc['id'] ?>'"
              style="cursor:pointer;">
            <td class="ps-3 fw-semibold font-monospace small"><?= htmlspecialchars($inc['incident_number'] ?? '') ?></td>
            <td class="small"><?= htmlspecialchars($typeLabels[$inc['type'] ?? ''] ?? ucwords(str_replace('_',' ',$inc['type'] ?? ''))) ?></td>
            <td><?= listThreatBadge($inc['threat_level'] ?? 'low') ?></td>
            <td><?= listStatusBadge($inc['status'] ?? 'open') ?></td>
            <td class="small"><?= htmlspecialchars($inc['province'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($inc['ao_sector'] ?? '—') ?></td>
            <td class="small"><?= htmlspecialchars($inc['reporting_unit'] ?? '—') ?></td>
            <td class="text-nowrap small"><?= listFmt($inc['reported_at'] ?? ($inc['created_at'] ?? null)) ?></td>
            <td class="text-center">
              <?php if ((int)($inc['casualties_count'] ?? 0) > 0): ?>
                <span class="badge bg-danger"><?= (int)$inc['casualties_count'] ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="pe-3 text-end" onclick="event.stopPropagation()">
              <div class="d-flex gap-1 justify-content-end">
                <a href="<?= htmlspecialchars($base_path ?? '') ?>/incidents/<?= (int)$inc['id'] ?>"
                   class="btn btn-sm btn-outline-primary" title="View">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="<?= htmlspecialchars($base_path ?? '') ?>/reports/export/<?= (int)$inc['id'] ?>/pdf"
                   target="_blank" class="btn btn-sm btn-outline-danger" title="Export PDF">
                  <i class="bi bi-file-earmark-pdf"></i>
                </a>
                <form method="post"
                      action="<?= htmlspecialchars($base_path ?? '') ?>/incidents/<?= (int)$inc['id'] ?>/delete"
                      class="d-inline"
                      onsubmit="return confirm('Delete <?= htmlspecialchars($inc['incident_number'] ?? '') ?>? This cannot be undone.');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                  <button type="submit" class="btn btn-sm btn-outline-secondary" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($incidents)): ?>
          <tr>
            <td colspan="10" class="text-center text-muted py-5">
              <i class="bi bi-journal-x fs-3 d-block mb-2 opacity-25"></i>
              No incidents found.
              <a href="<?= htmlspecialchars($base_path ?? '') ?>/incidents/create" class="ms-2">Create the first one →</a>
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  'use strict';
  const rows         = Array.from(document.querySelectorAll('#incidentListTable tbody tr[data-status]'));
  const searchInput  = document.getElementById('incidentListSearch');
  const countLabel   = document.getElementById('incidentCount');
  const statusBtns   = document.querySelectorAll('#statusFilter button[data-filter-status]');
  const threatBtns   = document.querySelectorAll('#threatFilter button[data-filter-threat]');
  let activeStatus = '';
  let activeThreat = '';

  function applyFilters() {
    const q = (searchInput.value || '').toLowerCase().trim();
    let visible = 0;
    rows.forEach(function (row) {
      const status = row.dataset.status || '';
      const threat = row.dataset.threat || '';
      const text   = row.textContent.toLowerCase();
      const matchStatus = !activeStatus || status === activeStatus;
      const matchThreat = !activeThreat || threat === activeThreat;
      const matchSearch = !q || text.includes(q);
      const show = matchStatus && matchThreat && matchSearch;
      row.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if (countLabel) {
      countLabel.textContent = q || activeStatus || activeThreat
        ? visible + ' of ' + rows.length + ' shown'
        : '';
    }
  }

  function setActive(btns, val) {
    btns.forEach(function (b) {
      const bVal = b.dataset.filterStatus !== undefined ? b.dataset.filterStatus : b.dataset.filterThreat;
      b.classList.toggle('active', bVal === val);
    });
  }

  statusBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      activeStatus = btn.dataset.filterStatus;
      setActive(statusBtns, activeStatus);
      applyFilters();
    });
  });

  threatBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      activeThreat = btn.dataset.filterThreat;
      setActive(threatBtns, activeThreat);
      applyFilters();
    });
  });

  searchInput.addEventListener('input', applyFilters);
}());
</script>