<?php
$roleLabels = include __DIR__ . '/../../../../config/role_labels.php';
$roles      = include __DIR__ . '/../../../../config/roles.php';
$roleBadge  = [
    'admin'              => 'danger',
  'g_staff'            => 'warning',
  'formation_commander'=> 'info',
  'cpo'                => 'primary',
  'incident_officer'   => 'primary',
  'hq_readonly'        => 'secondary',
  'army_hq'            => 'dark',
];
?>

<div class="d-flex align-items-center gap-2 mb-4">
  <a href="<?= htmlspecialchars($base_path ?? '') ?>/admin/users"
     class="btn btn-sm btn-outline-secondary py-0">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h4 class="fw-bold mb-0"><i class="bi bi-person-plus me-2 text-primary"></i>Create New User</h4>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4">
  <strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following:</strong>
  <ul class="mb-0 mt-2">
    <?php foreach ($errors as $e): ?>
      <li><?= htmlspecialchars((string)$e) ?></li>
    <?php endforeach; ?>
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row justify-content-start">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4">
        <form method="post"
              action="<?= htmlspecialchars($base_path ?? '') ?>/admin/users/store"
              novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

          <div class="row g-3">
            <!-- Name -->
            <div class="col-md-8">
              <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control"
                     placeholder="e.g. John Banda"
                     value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                     required>
            </div>

            <!-- Rank -->
            <div class="col-md-4">
              <label class="form-label fw-semibold">Rank</label>
              <input type="text" name="rank" class="form-control"
                     placeholder="e.g. Major"
                     value="<?= htmlspecialchars($old['rank'] ?? '') ?>">
            </div>

            <!-- Email -->
            <div class="col-12">
              <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control"
                       placeholder="officer@unit.mil"
                       autocomplete="new-password"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                       required>
              </div>
            </div>

            <!-- Password -->
            <div class="col-12">
              <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="password" id="password-input" name="password" class="form-control"
                       autocomplete="new-password"
                       data-pwbar="pw-bar" data-pwtext="pw-text"
                       placeholder="Min 12 chars, upper/lower/number/special"
                       required>
                <button type="button" class="btn btn-outline-secondary" id="togglePw" tabindex="-1">
                  <i class="bi bi-eye" id="togglePwIcon"></i>
                </button>
              </div>
              <!-- Strength meter -->
              <div class="mt-2">
                <div class="progress" style="height:6px;">
                  <div id="pw-bar" class="progress-bar" role="progressbar" style="width:0%;transition:width .3s;"></div>
                </div>
                <small id="pw-text" class="text-muted"></small>
              </div>
              <div class="form-text">
                Must be at least 12 characters and include uppercase, lowercase, a number, and a special character.
              </div>
            </div>

            <!-- Role -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
              <select name="role" class="form-select" id="roleSelect">
                <?php foreach ($roles as $r): ?>
                  <option value="<?= htmlspecialchars($r) ?>"
                    <?= (isset($old['role']) && $old['role'] === $r) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($roleLabels[$r] ?? $r) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <!-- Role description -->
              <div id="roleDesc" class="form-text mt-1"></div>
            </div>

            <!-- Unit -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Unit / Organisation</label>
              <input type="text" name="unit" class="form-control"
                     placeholder="e.g. 1 Para Bn / HQ"
                     value="<?= htmlspecialchars($old['unit'] ?? '') ?>">
            </div>

            <!-- Formation -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Formation / Brigade</label>
              <input type="text" name="formation" class="form-control"
                     placeholder="e.g. 1 Brigade"
                     value="<?= htmlspecialchars($old['formation'] ?? $user['formation'] ?? '') ?>">
            </div>

            <!-- Province -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Province</label>
              <input type="text" name="province" class="form-control"
                     placeholder="e.g. Lusaka"
                     value="<?= htmlspecialchars($old['province'] ?? $user['province'] ?? '') ?>">
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= htmlspecialchars($base_path ?? '') ?>/admin/users"
               class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-warning fw-semibold px-5">
              <i class="bi bi-person-check me-1"></i>Create User
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Role reference card -->
  <div class="col-lg-4 mt-4 mt-lg-0">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-bottom py-2">
        <span class="fw-semibold small text-uppercase text-muted"><i class="bi bi-info-circle me-1"></i>Role Reference</span>
      </div>
      <div class="card-body p-0">
        <?php
        $roleDescriptions = [
            'admin'              => 'Full system access. Can manage users, view all incidents, reports and analytics.',
            'commanding_officer' => 'Full incident access. Can endorse reports, close incidents, and view analytics.',
            'incident_officer'   => 'Can create and manage incidents they submitted. Cannot access analytics.',
            'hq_readonly'        => 'Read-only access. Can view incidents and export PDFs. Cannot create or edit.',
        ];
        foreach ($roles as $r):
          $badge = $roleBadge[$r] ?? 'secondary';
          $lbl   = $roleLabels[$r] ?? $r;
        ?>
        <div class="d-flex gap-2 px-3 py-2 border-bottom">
          <span class="badge bg-<?= $badge ?><?= $badge==='warning'?' text-dark':'' ?> mt-1" style="min-width:90px;text-align:center;">
            <?= htmlspecialchars($lbl) ?>
          </span>
          <span class="small text-muted"><?= htmlspecialchars($roleDescriptions[$r] ?? '') ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<script src="<?= htmlspecialchars($base_path ?? '') ?>/js/password.js"></script>
<script>
// Toggle password visibility
(function () {
  const btn  = document.getElementById('togglePw');
  const inp  = document.getElementById('password-input');
  const icon = document.getElementById('togglePwIcon');
  if (btn && inp) {
    btn.addEventListener('click', function () {
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }
})();

// Role description
(function () {
  const descs = <?= json_encode($roleDescriptions ?? [], JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const sel   = document.getElementById('roleSelect');
  const desc  = document.getElementById('roleDesc');
  function update() { if (desc && sel) desc.textContent = descs[sel.value] || ''; }
  if (sel) { sel.addEventListener('change', update); update(); }
})();
</script>