<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In — IncidentOps</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    body {
      /* Dark military-green gradient background */
      background: linear-gradient(135deg, #0d1b2a 0%, #1b2e3c 40%, #0f2027 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      font-size: 0.9rem;
    }

    /* Subtle grid overlay for depth */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.02) 1px, transparent 1px);
      background-size: 40px 40px;
      pointer-events: none;
      z-index: 0;
    }

    .login-wrapper {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 420px;
      padding: 1rem;
    }

    /* Classification banner */
    .classification-bar {
      background: #d4a017;
      color: #1a1a1a;
      text-align: center;
      font-size: .7rem;
      font-weight: 700;
      letter-spacing: .18em;
      padding: 4px 0;
      border-radius: .6rem .6rem 0 0;
    }

    .login-card {
      background: rgba(255, 255, 255, 0.97);
      border: none;
      border-radius: 0 0 .75rem .75rem;
      box-shadow:
        0 20px 60px rgba(0,0,0,.5),
        0 4px 16px rgba(0,0,0,.3);
      overflow: hidden;
    }

    .login-card-body {
      padding: 2.5rem 2.25rem 2rem;
    }

    /* Shield icon glow */
    .shield-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
      border-radius: 50%;
      font-size: 1.9rem;
      color: #1a1a1a;
      box-shadow: 0 4px 16px rgba(255, 193, 7, .45);
      margin-bottom: .75rem;
    }

    .app-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: #0d1b2a;
      letter-spacing: -.02em;
      margin: 0;
    }

    .app-subtitle {
      color: #6c757d;
      font-size: .78rem;
      letter-spacing: .06em;
      text-transform: uppercase;
      margin-top: 2px;
    }

    .form-label {
      font-weight: 600;
      font-size: .82rem;
      color: #344054;
      margin-bottom: .35rem;
    }

    .input-group-text {
      background: #f8f9fa;
      border-color: #ced4da;
      color: #6c757d;
    }

    .form-control {
      border-color: #ced4da;
      font-size: .875rem;
      padding: .5rem .75rem;
    }

    .form-control:focus {
      border-color: #0d6efd;
      box-shadow: 0 0 0 3px rgba(13,110,253,.12);
    }

    /* Show/hide password button */
    .btn-pw-toggle {
      border-color: #ced4da;
      background: #f8f9fa;
      color: #6c757d;
      border-left: none;
    }
    .btn-pw-toggle:hover {
      background: #e9ecef;
      color: #343a40;
    }
    .btn-pw-toggle:focus {
      box-shadow: none;
    }

    .btn-signin {
      background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
      border: none;
      color: #1a1a1a;
      font-weight: 700;
      font-size: .9rem;
      padding: .65rem 1rem;
      letter-spacing: .02em;
      border-radius: .45rem;
      transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
    }

    .btn-signin:hover {
      filter: brightness(1.06);
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(255,193,7,.4);
      color: #1a1a1a;
    }

    .btn-signin:active {
      transform: translateY(0);
      filter: brightness(.98);
    }

    .btn-signin:disabled {
      opacity: .7;
      cursor: not-allowed;
      transform: none;
    }

    .divider-text {
      font-size: .72rem;
      color: #adb5bd;
      text-align: center;
      margin: 1.25rem 0 .75rem;
      position: relative;
    }

    .divider-text::before,
    .divider-text::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 38%;
      height: 1px;
      background: #e9ecef;
    }

    .divider-text::before { left: 0; }
    .divider-text::after  { right: 0; }

    .security-footer {
      font-size: .7rem;
      color: #adb5bd;
      text-align: center;
      margin-top: 1.5rem;
      line-height: 1.5;
    }

    .security-footer i {
      color: #6c757d;
    }

    /* Alert tweaks */
    .alert {
      font-size: .82rem;
      padding: .6rem .85rem;
      margin-bottom: 1.1rem;
      border-radius: .45rem;
    }

    /* Spinner inside button */
    .spinner-border-sm {
      width: .85rem;
      height: .85rem;
      border-width: .15em;
    }

    /* Zambia / ZAF flag strip at very bottom */
    .country-strip {
      text-align: center;
      margin-top: 1.25rem;
      font-size: .7rem;
      color: rgba(255,255,255,.35);
      letter-spacing: .08em;
    }
  </style>
</head>
<body>

<div class="login-wrapper">

  <!-- Classification top bar -->
  <div class="classification-bar">
    ⬛ RESTRICTED SYSTEM — AUTHORISED USERS ONLY ⬛
  </div>

  <div class="login-card">
    <div class="login-card-body">

      <!-- Logo / branding -->
      <div class="text-center mb-4">
        <div class="shield-icon mx-auto">
          <i class="bi bi-shield-fill-exclamation"></i>
        </div>
        <h1 class="app-title">Joint Command Post</h1>
        <p class="app-subtitle">Internal Security Operations Platform</p>
      </div>

      <!-- Error alert -->
      <?php if (!empty($error)): ?>
      <div class="alert alert-danger d-flex align-items-start gap-2 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
        <span><?= htmlspecialchars((string)$error) ?></span>
      </div>
      <?php endif; ?>

      <!-- Locked alert -->
      <?php if (!empty($locked)): ?>
      <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" role="alert">
        <i class="bi bi-lock-fill mt-1 flex-shrink-0"></i>
        <span>
          Account temporarily locked after too many failed attempts.
          Please try again in 15 minutes.
        </span>
      </div>
      <?php endif; ?>

      <!-- Login form -->
      <form method="post"
            action="<?= htmlspecialchars($base_path ?? '') ?>/auth/login"
            novalidate
            id="loginForm">

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

        <!-- Email -->
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-envelope"></i>
            </span>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              placeholder="officer@unit.mil"
              autocomplete="username"
              autofocus
              required
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <!-- Password -->
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text">
              <i class="bi bi-key"></i>
            </span>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              placeholder="Enter your password"
              autocomplete="current-password"
              required>
            <button type="button"
                    class="btn btn-pw-toggle border"
                    id="togglePassword"
                    tabindex="-1"
                    title="Show / hide password">
              <i class="bi bi-eye" id="toggleIcon"></i>
            </button>
          </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-signin w-100" id="submitBtn">
          <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
      </form>

      <div class="divider-text">system info</div>

      <!-- Info pills -->
      <div class="d-flex justify-content-center gap-2 flex-wrap">
        <span class="badge bg-light text-secondary border" style="font-size:.68rem;font-weight:500;">
          <i class="bi bi-shield-lock me-1"></i>TLS Encrypted
        </span>
        <span class="badge bg-light text-secondary border" style="font-size:.68rem;font-weight:500;">
          <i class="bi bi-clock-history me-1"></i>Session: 30 min idle
        </span>
        <span class="badge bg-light text-secondary border" style="font-size:.68rem;font-weight:500;">
          <i class="bi bi-lock me-1"></i>Locks after 5 attempts
        </span>
      </div>

      <!-- Footer note -->
      <div class="security-footer">
        <i class="bi bi-geo-alt me-1"></i>
        Zambia Army — Internal Security Operations<br>
        Unauthorised access is a criminal offence under the Computer Misuse and Cybercrime Act.
      </div>

    </div><!-- /card-body -->
  </div><!-- /login-card -->

  <div class="country-strip">
    ZAMBIA ARMY &nbsp;·&nbsp; INCIDENTOPS v2.0
  </div>

</div><!-- /login-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  'use strict';

  // ── Show / hide password toggle ─────────────────────────
  var toggleBtn  = document.getElementById('togglePassword');
  var pwInput    = document.getElementById('password');
  var toggleIcon = document.getElementById('toggleIcon');

  if (toggleBtn && pwInput) {
    toggleBtn.addEventListener('click', function () {
      var isPassword = pwInput.type === 'password';
      pwInput.type = isPassword ? 'text' : 'password';
      toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }

  // ── Prevent double-submit, show spinner ─────────────────
  var form      = document.getElementById('loginForm');
  var submitBtn = document.getElementById('submitBtn');

  if (form && submitBtn) {
    form.addEventListener('submit', function () {
      // Basic client-side check before disabling
      var email = document.getElementById('email').value.trim();
      var pw    = pwInput ? pwInput.value : '';
      if (!email || !pw) return; // let HTML5 validation handle it

      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in…';
    });
  }

  // ── Auto-focus email if empty, password if email filled ─
  var emailInput = document.getElementById('email');
  if (emailInput) {
    if (emailInput.value.trim() !== '' && pwInput) {
      pwInput.focus();
    } else {
      emailInput.focus();
    }
  }

}());
</script>
</body>
</html>