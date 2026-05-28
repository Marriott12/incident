<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Incident <?= htmlspecialchars($incidentData['incident_number']) ?></title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    .header { text-align: center; margin-bottom: 10px; }
    .section { margin-bottom: 8px; }
    .label { font-weight: bold; }
    .watermark { position: fixed; top:45%; left:15%; opacity:0.08; font-size:72px; transform: rotate(-30deg); }
    .mapbox { width: 100%; height: 200px; background:#eee; display:flex; align-items:center; justify-content:center; color:#666 }
  </style>
</head>
<body>
  <div class="watermark">RESTRICTED</div>
  <div class="header">
    <h2>Incident Report</h2>
    <div><?= htmlspecialchars($incidentData['incident_number']) ?> — <?= htmlspecialchars($incidentData['reported_at'] ?? '') ?></div>
  </div>

  <div class="section">
    <div class="label">1. Incident Identification</div>
    <div>Type: <?= htmlspecialchars($incidentData['type'] ?? '') ?></div>
    <div>Reporting Unit: <?= htmlspecialchars($incidentData['reporting_unit'] ?? '') ?></div>
    <div>Grid Ref: <?= htmlspecialchars($incidentData['grid_reference'] ?? '') ?></div>
  </div>

  <div class="section">
    <div class="label">3. Situation Description</div>
    <div><?= nl2br(htmlspecialchars($incidentData['narrative'] ?? '')) ?></div>
  </div>

  <div class="section">
    <div class="label">Map</div>
    <div class="mapbox">Map for <?= htmlspecialchars($incidentData['latitude'] ?? '') ?>, <?= htmlspecialchars($incidentData['longitude'] ?? '') ?></div>
  </div>

  <div class="section">
    <div class="label">Outcome & Follow-Up</div>
    <div><?= nl2br(htmlspecialchars($incidentData['immediate_outcome'] ?? '')) ?></div>
  </div>

  <div style="position:fixed; bottom:10px; width:100%; text-align:center; font-size:10px;">Report completed by: <?= htmlspecialchars($incidentData['report_completed_by'] ?? '') ?> — Reviewed by: <?= htmlspecialchars($incidentData['reviewed_by'] ?? '') ?> — Generated: <?= date('c') ?></div>
</body>
</html>
