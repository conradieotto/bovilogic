<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

// Fetch 2FA status — graceful fallback if migration hasn't run yet
try {
    $twoFaEnabled = (bool)DB::val('SELECT totp_enabled FROM users WHERE id = ?', [$user['id']]);
} catch (Throwable $e) {
    $twoFaEnabled = false;
}

$pageTitle = 'settings';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-gear"></i> <?= t('settings') ?></h1>
</div>

<div style="padding:16px">
  <div class="card">
    <div class="card-header"><h3>App Info</h3></div>
    <div class="card-body">
      <p class="text-muted text-sm">Version: <?= APP_VERSION ?></p>
      <p class="text-muted text-sm mt-8">Domain: <?= APP_URL ?></p>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><h3>Your Profile</h3></div>
    <div class="card-body">
      <p><strong><?= htmlspecialchars($user['name']) ?></strong></p>
      <p class="text-muted text-sm"><?= htmlspecialchars($user['email']) ?></p>
      <p class="text-muted text-sm mt-4">Role: <?= $user['role'] === 'super_admin' ? 'Super Admin' : 'View User' ?></p>
    </div>
  </div>

  <!-- 2FA Card -->
  <div class="card mt-16">
    <div class="card-header">
      <h3><i class="fa-solid fa-shield-halved" style="color:var(--blue);margin-right:6px"></i> Two-Factor Authentication</h3>
    </div>
    <div class="card-body">
      <?php if ($twoFaEnabled): ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
        <span style="font-size:1.5rem;color:var(--green)"><i class="fa-solid fa-circle-check"></i></span>
        <div>
          <p style="font-weight:600;color:var(--green)">2FA is active</p>
          <p class="text-muted text-sm">Your account is protected with an authenticator app.</p>
        </div>
      </div>
      <a href="/setup-2fa.php" class="btn btn-secondary">
        <i class="fa-solid fa-rotate-left"></i> Re-setup 2FA (new device)
      </a>
      <?php else: ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
        <span style="font-size:1.5rem;color:var(--orange)"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <div>
          <p style="font-weight:600;color:var(--orange)">2FA not set up</p>
          <p class="text-muted text-sm">Set up two-factor authentication to secure your account.</p>
        </div>
      </div>
      <a href="/setup-2fa.php" class="btn btn-primary">
        <i class="fa-solid fa-shield-halved"></i> Set Up 2FA Now
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><h3>Change Password</h3></div>
    <div class="card-body">
      <div id="pw-result"></div>
      <div class="form-group"><label class="form-label">Current Password</label><input type="password" id="pw-current" class="form-control"></div>
      <div class="form-group"><label class="form-label">New Password</label><input type="password" id="pw-new" class="form-control" placeholder="Min 8 characters"></div>
      <div class="form-group"><label class="form-label">Confirm New Password</label><input type="password" id="pw-confirm" class="form-control"></div>
      <button class="btn btn-primary btn-full" onclick="changePassword()">Change Password</button>
    </div>
  </div>

  <?php if (isSuperAdmin()): ?>
  <div class="card mt-16" style="border:1px solid #ffcdd2">
    <div class="card-header" style="background:#fff5f5"><h3 style="color:#c62828">Danger Zone</h3></div>
    <div class="card-body">
      <p class="text-sm" style="margin-bottom:12px">Permanently delete all animals marked as <strong>Sold</strong> or <strong>Dead</strong>. Their calving records on the mother will be kept. This cannot be undone.</p>
      <div id="purge-result"></div>
      <button class="btn btn-full" style="background:#c62828;color:#fff" onclick="purgeDeadSold()">
        Delete All Sold &amp; Dead Animals
      </button>
    </div>
  </div>
  <?php endif; ?>

  <div class="card mt-16">
    <div class="card-header"><h3>PWA / Install</h3></div>
    <div class="card-body">
      <p class="text-muted text-sm mb-12">Add BoviLogic to your home screen for the best experience.</p>
      <button id="install-btn" class="btn btn-secondary btn-full" style="display:none">Install App</button>
      <p class="text-muted text-sm" id="install-note">Use your browser's "Add to Home Screen" option to install.</p>
    </div>
  </div>
</div>

<script>
// PWA install prompt
let deferredPrompt;
window.addEventListener('beforeinstallprompt', e => {
  e.preventDefault();
  deferredPrompt = e;
  document.getElementById('install-btn').style.display = 'flex';
  document.getElementById('install-note').style.display = 'none';
});

document.getElementById('install-btn')?.addEventListener('click', () => {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(() => { deferredPrompt = null; });
  }
});

async function purgeDeadSold() {
  const res = await fetch('/api/animals.php?q=_count_dead_sold').then(r=>r.json()).catch(()=>null);
  const count = res?.data?.length ?? '?';
  if (!confirm(`This will permanently delete all sold and dead animals. Are you sure?`)) return;
  if (!confirm(`Second confirmation: this cannot be undone. Continue?`)) return;

  const result = await fetch('/api/purge-animals.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ statuses: ['sold','dead'] })
  }).then(r=>r.json());

  const el = document.getElementById('purge-result');
  if (result.success) {
    el.innerHTML = `<div class="alert-bar success mb-12">${result.message}</div>`;
  } else {
    el.innerHTML = `<div class="alert-bar error mb-12">${result.message || 'Error.'}</div>`;
  }
}

async function changePassword() {
  const current = document.getElementById('pw-current').value;
  const newPass  = document.getElementById('pw-new').value;
  const confirm  = document.getElementById('pw-confirm').value;
  if (!current || !newPass || !confirm) { alert('All fields required.'); return; }
  if (newPass !== confirm) { alert('Passwords do not match.'); return; }
  if (newPass.length < 8) { alert('Password must be at least 8 characters.'); return; }

  const res = await fetch('/api/change-password.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ current_password: current, new_password: newPass })
  }).then(r=>r.json());

  const el = document.getElementById('pw-result');
  el.innerHTML = `<div class="alert-bar ${res.success?'success':'error'} mb-12">${res.message}</div>`;
  if (res.success) {
    document.getElementById('pw-current').value = '';
    document.getElementById('pw-new').value = '';
    document.getElementById('pw-confirm').value = '';
  }
}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
