<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$campId = (int)($_GET['id'] ?? 0);
if (!$campId) { header('Location: /farms.php'); exit; }

$camp = DB::row(
    'SELECT c.*, f.name AS farm_name, f.id AS farm_id FROM camps c
     LEFT JOIN farms f ON f.id = c.farm_id
     WHERE c.id = ?',
    [$campId]
);
if (!$camp) { header('Location: /farms.php'); exit; }

$pageTitle = 'camps';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/camps.php?farm=<?= $camp['farm_id'] ?>" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= htmlspecialchars($camp['name']) ?></h1>
</header>

<!-- Camp info card -->
<div style="padding:16px 16px 0">
  <div class="list-card" style="margin-bottom:0">
    <div class="list-item" style="pointer-events:none">
      <div class="item-body">
        <div class="item-title"><?= htmlspecialchars($camp['name']) ?></div>
        <div class="item-sub">
          <?= htmlspecialchars($camp['farm_name']) ?>
          <?php if ($camp['size_ha']): ?>· <?= htmlspecialchars($camp['size_ha']) ?> ha<?php endif; ?>
        </div>
      </div>
    </div>
    <div id="current-herd-row" style="padding:12px 16px;border-top:1px solid var(--border)">
      <span class="text-muted text-sm">Loading current herd…</span>
    </div>
  </div>
</div>

<!-- Movement history -->
<div class="section-header" style="margin-top:16px"><h2>Herd History</h2></div>
<div id="history-content" style="padding:0 16px 16px">
  <div class="page-loader" style="min-height:120px"><div class="spinner"></div></div>
</div>

<script>
const CAMP_ID = <?= $campId ?>;

// Load current herd
fetch('/api/herds.php')
  .then(r => r.json())
  .then(res => {
    const herd = (res.data || []).find(h => h.camp_id == CAMP_ID);
    const el = document.getElementById('current-herd-row');
    if (herd) {
      el.innerHTML = `<span class="text-sm text-muted">Current Herd</span><br>
        <strong style="color:var(--green)">${escHtml(herd.name)}</strong>
        <span class="text-muted text-sm"> · ${herd.animal_count} animals</span>`;
    } else {
      el.innerHTML = '<span class="text-sm text-muted" style="font-style:italic">No herd currently in this camp</span>';
    }
  });

// Load movement history
fetch(`/api/herd_movements.php?camp_id=${CAMP_ID}`)
  .then(r => r.json())
  .then(res => {
    const el = document.getElementById('history-content');
    if (!res.data?.length) {
      el.innerHTML = '<div class="list-card"><div class="p-16 text-muted text-sm" style="padding:16px">No movement history recorded yet.</div></div>';
      return;
    }
    el.innerHTML = '<div class="list-card">' + res.data.map(m => {
      const dateIn  = formatDate(m.date_in);
      const dateOut = m.date_out ? formatDate(m.date_out) : null;
      const days    = calcDays(m.date_in, m.date_out);
      return `
        <div class="list-item" style="align-items:flex-start;padding:14px 16px">
          <div style="width:8px;height:8px;border-radius:50%;margin-top:6px;flex-shrink:0;
            background:${m.date_out ? 'var(--text-muted)' : 'var(--green)'}"></div>
          <div class="item-body" style="margin-left:12px">
            <div class="item-title">${escHtml(m.herd_name || 'Unknown herd')}</div>
            <div class="item-sub" style="margin-top:2px">
              <strong>In:</strong> ${dateIn}
              ${dateOut
                ? ` &nbsp;·&nbsp; <strong>Out:</strong> ${dateOut} &nbsp;·&nbsp; ${days} days`
                : ' &nbsp;·&nbsp; <span style="color:var(--green);font-weight:600">Currently here</span>'}
            </div>
          </div>
        </div>`;
    }).join('') + '</div>';
  });

function formatDate(d) {
  if (!d) return '–';
  const dt = new Date(d);
  return dt.toLocaleDateString(undefined, {day:'numeric', month:'short', year:'numeric'});
}

function calcDays(dateIn, dateOut) {
  if (!dateOut) return '';
  const d1 = new Date(dateIn);
  const d2 = new Date(dateOut);
  return Math.round((d2 - d1) / 86400000);
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
