<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'sync';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/more.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('sync') ?></h1>
</header>

<div style="padding:16px">
  <div class="card mb-16">
    <div class="card-body">
      <div class="flex-between mb-12">
        <span class="font-bold">Connection</span>
        <span id="online-status" class="badge">Checking...</span>
      </div>
      <div class="flex-between mb-12">
        <span class="font-bold">Pending Changes</span>
        <span id="pending-count" class="badge badge-amber">–</span>
      </div>
      <div class="flex-between">
        <span class="font-bold">Last Sync</span>
        <span id="last-sync" class="text-muted text-sm">–</span>
      </div>
    </div>
    <div class="card-footer">
      <button class="btn btn-primary btn-full" id="sync-btn" onclick="forceSync()">Force Sync Now</button>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h3>Sync Log</h3></div>
    <div id="sync-log" class="card-body">
      <p class="text-muted text-sm">No sync activity yet.</p>
    </div>
  </div>
</div>

<script>
// Connection status
function updateStatus() {
  const badge = document.getElementById('online-status');
  if (navigator.onLine) {
    badge.textContent = 'Online';
    badge.className = 'badge badge-green';
  } else {
    badge.textContent = 'Offline';
    badge.className = 'badge badge-red';
  }
}
window.addEventListener('online', updateStatus);
window.addEventListener('offline', updateStatus);
updateStatus();

// Pending count from IndexedDB
function updatePendingCount() {
  if (typeof BL_DB !== 'undefined') {
    BL_DB.getSyncQueueCount().then(n => {
      const el = document.getElementById('pending-count');
      el.textContent = n;
      el.className = n > 0 ? 'badge badge-amber' : 'badge badge-green';
    });
  } else {
    document.getElementById('pending-count').textContent = '0';
    document.getElementById('pending-count').className = 'badge badge-green';
  }
}
updatePendingCount();

// Last sync from localStorage
const lastSync = localStorage.getItem('bl_last_sync');
if (lastSync) {
  document.getElementById('last-sync').textContent = new Date(parseInt(lastSync)).toLocaleString();
}

async function forceSync() {
  const btn = document.getElementById('sync-btn');
  btn.disabled = true;
  btn.textContent = 'Syncing...';
  addLog('Manual sync started...');
  try {
    if (typeof BL_SYNC !== 'undefined') {
      await BL_SYNC.processQueue();
    }
    localStorage.setItem('bl_last_sync', Date.now());
    document.getElementById('last-sync').textContent = new Date().toLocaleString();
    addLog('Sync complete.');
    updatePendingCount();
  } catch (e) {
    addLog('Sync failed: ' + e.message);
  }
  btn.disabled = false;
  btn.textContent = 'Force Sync Now';
}

function addLog(msg) {
  const el = document.getElementById('sync-log');
  const p  = document.createElement('p');
  p.className = 'text-sm';
  p.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
  if (el.firstChild?.textContent === 'No sync activity yet.') el.innerHTML = '';
  el.prepend(p);
}

// Listen for sync events from service worker
navigator.serviceWorker?.addEventListener('message', e => {
  if (e.data?.type === 'SYNC_COMPLETE') {
    addLog('Background sync complete.');
    localStorage.setItem('bl_last_sync', Date.now());
    document.getElementById('last-sync').textContent = new Date().toLocaleString();
    updatePendingCount();
  }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
