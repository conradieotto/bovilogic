<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'nav_dashboard';
require_once __DIR__ . '/templates/header.php';
?>

<!-- Page Header -->
<header class="page-header">
  <h1><?= t('dashboard') ?></h1>
  <button class="btn-icon" onclick="window.location='/more.php'" aria-label="More">
    <svg viewBox="0 0 24 24"><path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
  </button>
</header>

<!-- Search -->
<div class="search-bar">
  <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
  <input type="search" id="global-search" placeholder="<?= t('search') ?> ear tag / RFID..." autocomplete="off">
</div>

<!-- Dashboard Buttons -->
<div class="dash-grid">

  <button class="dash-btn" onclick="window.location='/summary.php'">
    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
    <?= t('summary') ?>
  </button>

  <button class="dash-btn" onclick="window.location='/alerts.php'">
    <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
    <?= t('alerts') ?>
    <span id="alert-count" class="badge badge-red" style="display:none">0</span>
  </button>

  <button class="dash-btn" onclick="window.location='/quick-actions.php'">
    <svg viewBox="0 0 24 24"><path d="M13 2.05V4.05C17.39 4.59 20.5 8.58 19.96 12.97C19.5 16.61 16.64 19.5 13 19.93V21.93C18.5 21.38 22.5 16.5 21.95 11C21.5 6.25 17.73 2.5 13 2.05M11 2.06C9.05 2.25 7.19 3 5.67 4.26L7.1 5.74C8.22 4.84 9.57 4.26 11 4.06V2.06M4.26 5.67C3 7.19 2.25 9.05 2.06 11H4.06C4.26 9.57 4.84 8.22 5.74 7.1L4.26 5.67M2.06 13C2.25 14.95 3 16.81 4.27 18.33L5.74 16.9C4.84 15.78 4.26 14.43 4.06 13H2.06M7.1 18.37L5.67 19.74C7.18 21 9.04 21.79 11 22V20C9.57 19.8 8.22 19.22 7.1 18.37M12 7L7 12H10V16H14V12H17L12 7Z"/></svg>
    <?= t('quick_actions') ?>
  </button>

  <button class="dash-btn" onclick="window.location='/activity.php'">
    <svg viewBox="0 0 24 24"><path d="M13.5 5.5c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zM9.8 8.9L7 23h2.1l1.8-8 2.1 2v6h2v-7.5l-2.1-2 .6-3C14.8 12 16.8 13 19 13v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1L6 8.3V13h2V9.6l1.8-.7"/></svg>
    <?= t('recent_activity') ?>
  </button>

</div>

<!-- Quick Stat Strip (loaded via JS) -->
<div class="section-header"><h2><?= t('summary') ?></h2></div>
<div class="stat-grid" id="dash-stats">
  <div class="stat-card"><span class="stat-val" id="stat-total">–</span><span class="stat-label"><?= t('total_animals') ?></span></div>
  <div class="stat-card alert"><span class="stat-val" id="stat-vaccines">–</span><span class="stat-label"><?= t('vaccines_due') ?></span></div>
  <div class="stat-card"><span class="stat-val" id="stat-calvings">–</span><span class="stat-label"><?= t('upcoming_calvings') ?></span></div>
  <div class="stat-card"><span class="stat-val" id="stat-sale">–</span><span class="stat-label"><?= t('animals_for_sale') ?></span></div>
</div>

<!-- Recent Activity Preview (loaded via JS) -->
<div class="section-header">
  <h2><?= t('recent_activity') ?></h2>
  <a href="/activity.php" class="btn-text btn-sm"><?= t('view') ?> all</a>
</div>
<div class="list-card" id="recent-list">
  <div class="page-loader"><div class="spinner"></div></div>
</div>

<script>
document.getElementById('global-search').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && this.value.trim()) {
    window.location = '/animals.php?q=' + encodeURIComponent(this.value.trim());
  }
});

// Load dashboard stats
fetch('/api/dashboard.php')
  .then(r => r.json())
  .then(res => {
    if (!res.success) return;
    const d = res.data;
    document.getElementById('stat-total').textContent    = d.total_animals ?? 0;
    document.getElementById('stat-vaccines').textContent = d.vaccines_due ?? 0;
    document.getElementById('stat-calvings').textContent = d.upcoming_calvings ?? 0;
    document.getElementById('stat-sale').textContent     = d.for_sale ?? 0;

    const alertCount = (d.vaccines_overdue ?? 0) + (d.vaccines_due ?? 0);
    const badge = document.getElementById('alert-count');
    if (alertCount > 0) {
      badge.textContent = alertCount;
      badge.style.display = '';
    }
  })
  .catch(() => {});

// Load recent activity
fetch('/api/activity-log.php?limit=5')
  .then(r => r.json())
  .then(res => {
    const el = document.getElementById('recent-list');
    if (!res.success || !res.data.length) {
      el.innerHTML = '<div class="empty-state"><p>No recent activity.</p></div>';
      return;
    }
    el.innerHTML = res.data.map(a => `
      <div class="list-item">
        <div class="item-body">
          <div class="item-title">${escHtml(a.description || a.action)}</div>
          <div class="item-sub">${escHtml(a.entity_type)} &middot; ${formatDate(a.created_at)}</div>
        </div>
      </div>
    `).join('');
  })
  .catch(() => {
    document.getElementById('recent-list').innerHTML = '<div class="empty-state"><p>Could not load activity.</p></div>';
  });

function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = s || '';
  return d.innerHTML;
}
function formatDate(s) {
  if (!s) return '';
  return new Date(s).toLocaleDateString();
}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
