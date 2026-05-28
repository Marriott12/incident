<?php
function rptThreatBadge(string $level): string {
    $map = ['critical'=>'danger','high'=>'warning','moderate'=>'success','low'=>'light'];
    $cls = $map[$level] ?? 'secondary';
    $dark = in_array($level, ['moderate', 'low'], true) ? ' text-dark' : '';
    return '<span class="badge bg-' . $cls . $dark . '">' . htmlspecialchars(ucfirst($level)) . '</span>';
}
function rptStatusBadge(string $status): string {
    $map = ['open'=>'primary','contained'=>'warning','closed'=>'secondary','under_review'=>'info'];
    $cls = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '">' . htmlspecialchars(ucwords(str_replace('_',' ',$status))) . '</span>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>Reports</h4>
    <small class="text-muted">Export incident reports as PDF</small>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span class="fw-semibold"><i class="bi bi-list-ul me-1"></i>All Incidents
      <span class="badge bg-light text-secondary ms-1"><?= count($incidents ?? []) ?></span>
    </span>
    <input type="search" id="reportSearch" class="form-control form-control-sm" placeholder="Search…" style="width:200px;">
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0" id="reportTable">
        <thead class="table-light">
          <tr>
            <th class="ps-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;">Incident No.</th>
            <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;">Type</th>
            <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;">Threat</th>
            <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;">Status</th>
            <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;">AO Sector</th>
            <th style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;">Reported At</th>
            <th class="pe-3" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;">Export</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($incidents ?? []) as $inc): ?>
          <tr class="align-middle">
            <td class="ps-3 fw-semibold"><?= htmlspecialchars($inc['incident_number']) ?></td>
            <td><?= htmlspecialchars(ucwords(str_replace('_',' ',$inc['type'] ?? ''))) ?></td>
            <td><?= rptThreatBadge($inc['threat_level'] ?? 'low') ?></td>
            <td><?= rptStatusBadge($inc['status'] ?? 'open') ?></td>
            <td><?= htmlspecialchars($inc['ao_sector'] ?? '—') ?></td>
            <td class="text-nowrap"><?= $inc['reported_at'] ? date('d M Y H:i', strtotime($inc['reported_at'])) : '—' ?></td>
            <td class="pe-3">
              <div class="d-flex gap-1">
                <a href="<?= htmlspecialchars($base_path) ?>/incidents/<?= (int)$inc['id'] ?>"
                   class="btn btn-sm btn-outline-secondary" title="View incident">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="<?= htmlspecialchars($base_path) ?>/reports/export/<?= (int)$inc['id'] ?>/pdf"
                   class="btn btn-sm btn-outline-danger" target="_blank" title="Export PDF">
                  <i class="bi bi-file-earmark-pdf"></i> PDF
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($incidents)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No incidents recorded.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  const rows   = Array.from(document.querySelectorAll('#reportTable tbody tr'));
  const search = document.getElementById('reportSearch');
  search.addEventListener('input', function () {
    const q = search.value.toLowerCase();
    rows.forEach(function (row) {
      row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}());
</script>
