<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'settings';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/more.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('settings') ?></h1>
</header>

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
