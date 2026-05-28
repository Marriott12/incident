<?php
// ── Helpers ────────────────────────────────────────────────────────────────
$status  = (string)($incident['status']      ?? 'open');
$threat  = (string)($incident['threat_level'] ?? 'low');
$confLvl = (string)($incident['confidentiality_level'] ?? 'restricted');

$statusClass  = ['open'=>'primary','under_review'=>'info','contained'=>'warning','closed'=>'secondary'][$status]  ?? 'secondary';
$threatClass  = ['critical'=>'danger','high'=>'warning','moderate'=>'success','low'=>'light'][$threat]              ?? 'secondary';
$threatText   = in_array($threat, ['moderate','low'], true) ? ' text-dark' : '';
$confClass    = ['restricted'=>'warning','confidential'=>'orange','secret'=>'danger'][$confLvl]                   ?? 'secondary';

$typeLabels = [
    'public_disorder'=>'Public Disorder','crowd_control'=>'Crowd Control',
    'evacuation'=>'Evacuation','crime'=>'Crime',
    'intelligence_tip'=>'Intelligence Tip','other'=>'Other',
];
function dtFmt(?string $v): string {
    return $v ? date('d M Y  H:i', strtotime($v)) : '—';
}
function safeVal($v, string $fallback = '—'): string {
    $s = trim((string)($v ?? ''));
    return $s !== '' ? htmlspecialchars($s) : $fallback;
}
function numFmt($v): string {
    return (int)$v > 0 ? htmlspecialchars((string)(int)$v) : '0';
}

$lat = trim((string)($incident['latitude']  ?? ''));
$lng = trim((string)($incident['longitude'] ?? ''));
$hasCoords = $lat !== '' && $lng !== '';
?>

<!-- ── Page Header ──────────────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <a href="<?= htmlspecialchars($base_path ?? '') ?>/incidents"
         class="btn btn-sm btn-outline-secondary py-0">
        <i class="bi bi-arrow-left"></i>
      </a>
      <h4 class="fw-bold mb-0">
        <i class="bi bi-journal-text me-2 text-primary"></i>
        <?= safeVal($incident['incident_number']) ?>
      </h4>
      <span class="badge bg-<?= htmlspecialchars($confClass === 'orange' ? 'warning text-dark' : $confClass) ?> ms-1">
        <?= strtoupper(safeVal($incident['confidentiality_level'])) ?>
      </span>
    </div>
    <div class="d-flex flex-wrap gap-2 ps-5">
      <span class="badge bg-<?= htmlspecialchars($statusClass) ?> fs-6px">
        <i class="bi bi-circle-fill me-1" style="font-size:.5em;"></i>
        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?>
      </span>
      <span class="badge bg-<?= htmlspecialchars($threatClass) ?><?= $threat==='moderate' ? ' text-dark' : '' ?>">
        <i class="bi bi-shield-exclamation me-1"></i>
        <?= htmlspecialchars(ucfirst($threat)) ?> Threat
      </span>
      <span class="badge bg-light text-secondary border">
        <i class="bi bi-clock me-1"></i><?= dtFmt($incident['reported_at'] ?? null) ?>
      </span>
    </div>
  </div>

  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-danger" target="_blank"
       href="<?= htmlspecialchars($base_path ?? '') ?>/reports/export/<?= (int)$incident['id'] ?>/pdf">
      <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
    </a>
    <?php if (!empty($allowedTransitions)): ?>
    <form method="post"
          action="<?= htmlspecialchars($base_path ?? '') ?>/incidents/<?= (int)$incident['id'] ?>/status"
          class="d-flex gap-2 align-items-center">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
      <select name="status" class="form-select form-select-sm" style="min-width:140px;">
        <?php foreach ($allowedTransitions as $t): ?>
          <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ',$t))) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-sm btn-primary text-nowrap">
        <i class="bi bi-arrow-repeat me-1"></i>Update Status
      </button>
    </form>
    <?php endif; ?>
    <form method="post"
          action="<?= htmlspecialchars($base_path ?? '') ?>/incidents/<?= (int)$incident['id'] ?>/delete"
          onsubmit="return confirm('Permanently delete <?= htmlspecialchars($incident['incident_number'] ?? '') ?>? This cannot be undone.');">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
      <button type="submit" class="btn btn-sm btn-outline-danger">
        <i class="bi bi-trash me-1"></i>Delete
      </button>
    </form>
  </div>
</div>

<!-- ── Main Grid ─────────────────────────────────────────────────────── -->
<div class="row g-4">

  <!-- LEFT COLUMN -->
  <div class="col-lg-8">

    <!-- Section 1 & 2: Identification + Command -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2">
        <span class="badge bg-primary rounded-pill">1–2</span>
        <span class="fw-semibold">Identification &amp; Command</span>
      </div>
      <div class="card-body">
        <div class="row g-0">
          <div class="col-md-6">
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr><th class="text-muted fw-normal ps-0" style="width:140px;">Incident No.</th>
                    <td class="fw-semibold"><?= safeVal($incident['incident_number']) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Reported At</th>
                    <td><?= dtFmt($incident['reported_at'] ?? null) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Type</th>
                    <td><?= htmlspecialchars($typeLabels[$incident['type'] ?? ''] ?? ucwords(str_replace('_',' ',$incident['type'] ?? ''))) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Incident Grading</th>
                    <td><span class="badge bg-<?= htmlspecialchars($threatClass) . $threatText ?>"><?= htmlspecialchars(ucfirst($threat)) ?></span></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Source</th>
                    <td><?= safeVal($incident['reporting_unit']) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Province</th>
                    <td><?= safeVal($incident['province']) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">District</th>
                    <td><?= safeVal($incident['district']) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">AO Sector</th>
                    <td><?= safeVal($incident['ao_sector']) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Grid Reference</th>
                    <td class="font-monospace"><?= safeVal($incident['grid_reference']) ?></td></tr>
                <?php if ($hasCoords): ?>
                <tr><th class="text-muted fw-normal ps-0">Coordinates</th>
                    <td class="font-monospace small"><?= htmlspecialchars($lat) ?>, <?= htmlspecialchars($lng) ?></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="col-md-6 border-start ps-4">
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr><th class="text-muted fw-normal ps-0" style="width:140px;">Command Post Officer</th>
                    <td><?= safeVal($incident['commanding_officer']) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Shift</th>
                    <td><?= htmlspecialchars(ucfirst($incident['shift'] ?? '—')) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Comms Channels</th>
                    <td><?= safeVal($incident['comms_channels']) ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Liaison Notes</th>
                    <td><?= $incident['liaison_notes'] ? nl2br(htmlspecialchars($incident['liaison_notes'])) : '—' ?></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Status</th>
                    <td><span class="badge bg-<?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ',$status))) ?></span></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Threat Level</th>
                    <td><span class="badge bg-<?= htmlspecialchars($threatClass) ?><?= $threat==='moderate'?' text-dark':'' ?>"><?= htmlspecialchars(ucfirst($threat)) ?></span></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Confidentiality</th>
                    <td><span class="badge bg-warning text-dark"><?= strtoupper(safeVal($incident['confidentiality_level'])) ?></span></td></tr>
                <tr><th class="text-muted fw-normal ps-0">Submitted HQ</th>
                    <td><?= dtFmt($incident['submitted_to_hq_at'] ?? null) ?></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 3: Situation -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2">
        <span class="badge bg-primary rounded-pill">3</span>
        <span class="fw-semibold">Situation Description</span>
      </div>
      <div class="card-body">
        <?php if ($incident['narrative']): ?>
          <div class="bg-light rounded p-3 mb-3 border-start border-4 border-primary"
               style="white-space:pre-wrap;line-height:1.65;font-size:.92rem;">
            <?= htmlspecialchars($incident['narrative']) ?>
          </div>
        <?php else: ?>
          <p class="text-muted fst-italic">No narrative recorded.</p>
        <?php endif; ?>

        <!-- Personnel counts -->
        <div class="row g-2 mb-3">
          <?php foreach ([
            ['Military',   $incident['personnel_count_military']   ?? 0, 'primary',   'person-badge'],
            ['Police',     $incident['personnel_count_police']     ?? 0, 'info',      'shield'],
            ['Civilians',  $incident['personnel_count_civilians']  ?? 0, 'success',   'people'],
            ['Adversaries',$incident['personnel_count_adversaries']?? 0, 'danger',    'person-x'],
          ] as [$lbl, $num, $cls, $icon]): ?>
          <div class="col-6 col-md-3">
            <div class="card border-0 bg-light h-100 text-center p-2">
              <div class="fs-4 fw-bold text-<?= $cls ?>"><?= (int)$num ?></div>
              <div class="small text-muted">
                <i class="bi bi-<?= $icon ?> me-1"></i><?= $lbl ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if ($incident['civilian_impact']): ?>
        <div class="mb-2">
          <span class="fw-semibold small text-uppercase text-muted">Civilian Impact</span>
          <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($incident['civilian_impact'])) ?></p>
        </div>
        <?php endif; ?>
        <?php if ($incident['environmental_conditions']): ?>
        <div>
          <span class="fw-semibold small text-uppercase text-muted">Environmental / Tactical Conditions</span>
          <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($incident['environmental_conditions'])) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Section 4: Threat -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2">
        <span class="badge bg-primary rounded-pill">4</span>
        <span class="fw-semibold">Threat Assessment &amp; Escalation</span>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Threat Level</div>
            <span class="badge bg-<?= htmlspecialchars($threatClass) ?><?= $threat==='moderate'?' text-dark':'' ?> fs-6 px-3 py-2">
              <i class="bi bi-shield-fill-exclamation me-1"></i>
              <?= htmlspecialchars(strtoupper($threat)) ?>
            </span>
          </div>
          <div class="col-md-6">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Weapons / HAZMAT</div>
            <?php if (!empty($incident['weapons_hazmat_present'])): ?>
              <span class="badge bg-danger px-3 py-2">
                <i class="bi bi-radioactive me-1"></i>YES — PRESENT
              </span>
              <?php if ($incident['weapons_hazmat_details']): ?>
                <p class="mt-2 mb-0 small"><?= nl2br(htmlspecialchars($incident['weapons_hazmat_details'])) ?></p>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle me-1"></i>None Reported</span>
            <?php endif; ?>
          </div>
          <?php if ($incident['escalation_measures']): ?>
          <div class="col-12">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Escalation Measures</div>
            <p class="mb-0"><?= nl2br(htmlspecialchars($incident['escalation_measures'])) ?></p>
          </div>
          <?php endif; ?>
          <?php if ($incident['patterns_forecast']): ?>
          <div class="col-12">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Patterns / Forecast</div>
            <p class="mb-0"><?= nl2br(htmlspecialchars($incident['patterns_forecast'])) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Section 5: Actions -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2">
        <span class="badge bg-primary rounded-pill">5</span>
        <span class="fw-semibold">Actions Taken</span>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php foreach ([
            ['Military Actions',   $incident['military_actions']    ?? null, 'lightning-charge'],
            ['Support Actions',    $incident['support_actions']     ?? null, 'tools'],
            ['Intelligence Gathered',$incident['intelligence_gathered']??null,'eye'],
            ['Resources Utilised', $incident['resources_utilized']  ?? null, 'box-seam'],
          ] as [$lbl, $val, $icon]): ?>
          <div class="col-md-6">
            <div class="fw-semibold small text-uppercase text-muted mb-1">
              <i class="bi bi-<?= $icon ?> me-1"></i><?= $lbl ?>
            </div>
            <?php if ($val): ?>
              <p class="mb-0 small"><?= nl2br(htmlspecialchars($val)) ?></p>
            <?php else: ?>
              <p class="mb-0 text-muted fst-italic small">Not recorded</p>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Section 6: Outcome -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2">
        <span class="badge bg-primary rounded-pill">6</span>
        <span class="fw-semibold">Outcome &amp; Follow-Up</span>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Immediate Outcome</div>
            <p class="mb-0"><?= $incident['immediate_outcome'] ? nl2br(htmlspecialchars($incident['immediate_outcome'])) : '<span class="text-muted fst-italic">Not recorded</span>' ?></p>
          </div>
          <div class="col-md-3">
            <div class="card bg-<?= (int)($incident['casualties_count']??0)>0 ? 'danger' : 'light' ?> border-0 text-center p-3">
              <div class="fs-3 fw-bold <?= (int)($incident['casualties_count']??0)>0 ? 'text-white' : 'text-muted' ?>">
                <?= numFmt($incident['casualties_count'] ?? 0) ?>
              </div>
              <div class="small <?= (int)($incident['casualties_count']??0)>0 ? 'text-white' : 'text-muted' ?>">Casualties</div>
            </div>
          </div>
          <div class="col-md-9">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Damages</div>
            <p class="mb-0"><?= safeVal($incident['damages_description']) ?></p>
          </div>
          <?php if ($incident['followup_actions']): ?>
          <div class="col-12">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Follow-Up Actions Required</div>
            <p class="mb-0"><?= nl2br(htmlspecialchars($incident['followup_actions'])) ?></p>
          </div>
          <?php endif; ?>
          <div class="col-md-6">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Follow-Up Officer</div>
            <p class="mb-0"><?= safeVal($incident['followup_officer']) ?></p>
          </div>
          <div class="col-md-6">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Follow-Up Unit</div>
            <p class="mb-0"><?= safeVal($incident['followup_unit']) ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 7 & 8: Reporting + Compliance -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom d-flex align-items-center gap-2 py-2">
        <span class="badge bg-primary rounded-pill">7–8</span>
        <span class="fw-semibold">Reporting, Documentation &amp; Compliance</span>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Report Completed By</div>
            <p class="mb-0"><?= safeVal($incident['report_completed_by']) ?></p>
          </div>
          <div class="col-md-6">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Reviewed By</div>
            <p class="mb-0"><?= safeVal($incident['reviewed_by']) ?></p>
          </div>
          <div class="col-md-6">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Submitted to HQ</div>
            <p class="mb-0"><?= dtFmt($incident['submitted_to_hq_at'] ?? null) ?></p>
          </div>
          <div class="col-md-6">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Confidentiality</div>
            <span class="badge bg-warning text-dark px-3 py-2"><?= strtoupper(safeVal($incident['confidentiality_level'])) ?></span>
          </div>
          <?php if ($incident['roe_compliance_notes']): ?>
          <div class="col-12">
            <div class="fw-semibold small text-uppercase text-muted mb-1">ROE Compliance Notes</div>
            <p class="mb-0"><?= nl2br(htmlspecialchars($incident['roe_compliance_notes'])) ?></p>
          </div>
          <?php endif; ?>
          <?php if ($incident['human_rights_notes']): ?>
          <div class="col-12">
            <div class="fw-semibold small text-uppercase text-muted mb-1">Human Rights Notes</div>
            <p class="mb-0"><?= nl2br(htmlspecialchars($incident['human_rights_notes'])) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Attachments -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between py-2">
        <span class="fw-semibold"><i class="bi bi-paperclip me-1"></i>Attachments
          <span class="badge bg-light text-secondary ms-1"><?= count($attachments ?? []) ?></span>
        </span>
      </div>
      <div class="card-body p-0">
        <?php if (!empty($attachments)): ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($attachments as $att): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
              <div class="d-flex align-items-center gap-2">
                <?php
                $mimeType = (string)($att['mime_type'] ?? '');
                if (strpos($mimeType, 'image') !== false) {
                    $mimeIcon = 'file-earmark-image text-success';
                } elseif (strpos($mimeType, 'pdf') !== false) {
                    $mimeIcon = 'file-earmark-pdf text-danger';
                } else {
                    $mimeIcon = 'file-earmark text-secondary';
                }
                ?>
                <i class="bi bi-<?= $mimeIcon ?> fs-5"></i>
                <div>
                  <div class="fw-semibold small"><?= htmlspecialchars((string)$att['file_name']) ?></div>
                  <div class="text-muted" style="font-size:.75rem;">
                    <?= htmlspecialchars(ucwords(str_replace('_',' ',$att['file_type'] ?? 'other'))) ?>
                    <?php if (!empty($att['file_size'])): ?>
                      &nbsp;·&nbsp;<?= round((int)$att['file_size'] / 1024, 1) ?> KB
                    <?php endif; ?>
                    &nbsp;·&nbsp;<?= dtFmt($att['uploaded_at'] ?? null) ?>
                  </div>
                </div>
              </div>
              <a class="btn btn-sm btn-outline-primary"
                 href="<?= htmlspecialchars($base_path ?? '') ?>/incidents/<?= (int)$incident['id'] ?>/attachments/<?= (int)$att['id'] ?>/download">
                <i class="bi bi-download me-1"></i>Download
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-muted text-center py-4">
            <i class="bi bi-paperclip fs-4 d-block mb-2 opacity-25"></i>
            No attachments uploaded for this incident.
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /LEFT -->

  <!-- RIGHT COLUMN -->
  <div class="col-lg-4">

    <!-- Map -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold"><i class="bi bi-map me-1"></i>Incident Location</span>
        <?php if ($hasCoords): ?>
          <span class="badge bg-success ms-2 float-end mt-1" style="font-size:.7rem;">
            <i class="bi bi-geo-alt me-1"></i>Pinned
          </span>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <div
          id="incident-detail-map"
          style="height:300px;border-radius:0 0 .5rem .5rem;"
          data-lat="<?= htmlspecialchars($lat) ?>"
          data-lng="<?= htmlspecialchars($lng) ?>"
          data-grid-reference="<?= htmlspecialchars(trim((string)($incident['grid_reference'] ?? ''))) ?>"
          data-threat="<?= htmlspecialchars($threat) ?>"
          data-status="<?= htmlspecialchars($status) ?>"
          data-incident-number="<?= htmlspecialchars((string)($incident['incident_number'] ?? '')) ?>"
        ></div>
        <?php if ($hasCoords): ?>
        <div class="px-3 py-2 border-top bg-light small text-muted font-monospace">
          <?= htmlspecialchars($lat) ?>, <?= htmlspecialchars($lng) ?>
          <?php if ($incident['grid_reference']): ?>
            &nbsp;·&nbsp;<?= htmlspecialchars($incident['grid_reference']) ?>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Workflow / Status update -->
    <?php if (!empty($allowedTransitions)): ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold"><i class="bi bi-arrow-repeat me-1"></i>Update Status</span>
      </div>
      <div class="card-body">
        <div class="mb-2 small">
          Current:
          <span class="badge bg-<?= htmlspecialchars($statusClass) ?> ms-1">
            <?= htmlspecialchars(ucwords(str_replace('_',' ',$status))) ?>
          </span>
        </div>
          <?php if (!empty($incident['g_staff_comment'])): ?>
            <div class="mb-2">
              <div class="small text-uppercase text-muted">G Staff Comment</div>
              <div class="border rounded p-2 bg-light small mb-2" style="white-space:pre-wrap;"><?= htmlspecialchars($incident['g_staff_comment']) ?></div>
              <div class="small text-muted">Reviewed: <?= htmlspecialchars($incident['g_staff_reviewed_at'] ?? '—') ?></div>
            </div>
          <?php endif; ?>
          <?php if (!empty($incident['formation_comment'])): ?>
            <div class="mb-2">
              <div class="small text-uppercase text-muted">Formation Comment</div>
              <div class="border rounded p-2 bg-light small mb-2" style="white-space:pre-wrap;"><?= htmlspecialchars($incident['formation_comment']) ?></div>
              <div class="small text-muted">Reviewed: <?= htmlspecialchars($incident['formation_reviewed_at'] ?? '—') ?></div>
            </div>
          <?php endif; ?>

          <form method="post"
                action="<?= htmlspecialchars($base_path ?? '') ?>/incidents/<?= (int)$incident['id'] ?>/status"
                class="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
            <div class="mb-2">
              <select name="status" class="form-select form-select-sm">
                <?php foreach ($allowedTransitions as $t): ?>
                  <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ',$t))) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label small">Comment (required for reviews)</label>
              <textarea name="comment" class="form-control form-control-sm" rows="3" aria-label="Review comment"></textarea>
            </div>
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-sm btn-primary text-nowrap">Apply</button>
            </div>
          </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Quick facts sidebar -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold"><i class="bi bi-info-circle me-1"></i>Quick Facts</span>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-borderless mb-0">
          <tbody>
            <tr class="border-bottom">
              <td class="text-muted ps-3 py-2 small">Province</td>
              <td class="fw-semibold pe-3 py-2 small"><?= safeVal($incident['province']) ?></td>
            </tr>
            <tr class="border-bottom">
              <td class="text-muted ps-3 py-2 small">District</td>
              <td class="fw-semibold pe-3 py-2 small"><?= safeVal($incident['district']) ?></td>
            </tr>
            <tr class="border-bottom">
              <td class="text-muted ps-3 py-2 small">AO Sector</td>
              <td class="fw-semibold pe-3 py-2 small"><?= safeVal($incident['ao_sector']) ?></td>
            </tr>
            <tr class="border-bottom">
              <td class="text-muted ps-3 py-2 small">Shift</td>
              <td class="fw-semibold pe-3 py-2 small"><?= htmlspecialchars(ucfirst($incident['shift'] ?? '—')) ?></td>
            </tr>
            <tr class="border-bottom">
              <td class="text-muted ps-3 py-2 small">Casualties</td>
              <td class="fw-semibold pe-3 py-2 small <?= (int)($incident['casualties_count']??0)>0?'text-danger':'' ?>">
                <?= numFmt($incident['casualties_count'] ?? 0) ?>
              </td>
            </tr>
            <tr class="border-bottom">
              <td class="text-muted ps-3 py-2 small">Weapons/HAZMAT</td>
              <td class="fw-semibold pe-3 py-2 small <?= !empty($incident['weapons_hazmat_present'])?'text-danger':'' ?>">
                <?= !empty($incident['weapons_hazmat_present']) ? '⚠ Yes' : 'No' ?>
              </td>
            </tr>
            <tr>
              <td class="text-muted ps-3 py-2 small">Created</td>
              <td class="pe-3 py-2 small"><?= dtFmt($incident['created_at'] ?? null) ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Audit Trail -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold"><i class="bi bi-clock-history me-1"></i>Audit Trail</span>
        <span class="badge bg-light text-secondary"><?= count($auditTrail ?? []) ?></span>
      </div>
      <div class="card-body p-0" style="max-height:420px;overflow-y:auto;">
        <?php if (!empty($auditTrail)):
          $groupedAudit = [];
          foreach ($auditTrail as $entry) {
            $dk = date('Y-m-d', strtotime((string)$entry['created_at']));
            $groupedAudit[$dk][] = $entry;
          }
          $actionIcons = [
            'incident.create'        => 'plus-circle text-success',
            'incident.draft_create'  => 'pencil text-secondary',
            'incident.draft_update'  => 'pencil text-secondary',
            'incident.status_update' => 'arrow-repeat text-primary',
            'incident.delete'        => 'trash text-danger',
            'incident.attachment_upload' => 'paperclip text-info',
            'incident.attachment_download' => 'download text-info',
            'report.export_pdf'      => 'file-earmark-pdf text-danger',
          ];
          foreach ($groupedAudit as $dk => $entries): ?>
          <div class="px-3 pt-2 pb-1">
            <div class="small text-uppercase text-muted fw-semibold mb-2 border-bottom pb-1">
              <?= htmlspecialchars(date('d M Y', strtotime($dk))) ?>
            </div>
            <?php foreach ($entries as $entry):
              $actionKey  = (string)($entry['action'] ?? '');
              $iconClass  = $actionIcons[$actionKey] ?? 'dot text-secondary';
              $actionLabel = ucwords(str_replace(['incident.','report.','auth.','_'], ['','','','  '], $actionKey));
            ?>
            <div class="d-flex gap-2 mb-2 pb-2 border-bottom">
              <div class="pt-1">
                <i class="bi bi-<?= $iconClass ?>" style="font-size:.85rem;"></i>
              </div>
              <div class="flex-grow-1">
                <div class="small fw-semibold"><?= htmlspecialchars($actionLabel) ?></div>
                <div class="small text-muted"><?= htmlspecialchars(date('H:i:s', strtotime((string)$entry['created_at']))) ?></div>
                <?php if (!empty($entry['ip_address'])): ?>
                  <div class="text-muted" style="font-size:.7rem;">IP: <?= htmlspecialchars($entry['ip_address']) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="text-muted text-center py-4">
            <i class="bi bi-clock-history fs-4 d-block mb-2 opacity-25"></i>
            No audit entries yet.
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /RIGHT -->
</div>