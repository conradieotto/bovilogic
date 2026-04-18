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

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-map-location-dot"></i> <?= htmlspecialchars($camp['name']) ?></h1>
  <a href="/camps.php?farm=<?= $camp['farm_id'] ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> <?= t('back') ?></a>
</div>

<!-- Camp info + grazing summary -->
<div style="padding:16px 16px 0">
  <div class="list-card" style="margin-bottom:0">
    <div class="list-item" style="pointer-events:none">
      <div class="item-body">
        <div class="item-title"><?= htmlspecialchars($camp['name']) ?></div>
        <div class="item-sub">
          <?= htmlspecialchars($camp['farm_name']) ?>
          <?php if ($camp['size_ha']): ?>· <?= htmlspecialchars($camp['size_ha']) ?> ha<?php endif; ?>
          <?php if ($camp['stocking_ratio']): ?>· <?= t('stocking_rate') ?>: 1:<?= htmlspecialchars($camp['stocking_ratio']) ?><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Grazing budget panel -->
    <div id="grazing-panel" style="padding:12px 16px;border-top:1px solid var(--border)">
      <div class="page-loader" style="min-height:60px"><div class="spinner"></div></div>
    </div>

    <!-- Current herd -->
    <div id="current-herd-row" style="padding:12px 16px;border-top:1px solid var(--border)">
      <span class="text-muted text-sm"><?= t('loading') ?></span>
    </div>
  </div>
</div>

<!-- Grazing History -->
<div class="section-header" style="margin-top:16px"><h2><?= t('grazing_history') ?></h2></div>
<div id="history-content" style="padding:0 16px 16px">
  <div class="page-loader" style="min-height:120px"><div class="spinner"></div></div>
</div>

<script>
const CAMP_ID = <?= $campId ?>;
const T = <?= json_encode([
  'stocking_rate'    => t('stocking_rate'),
  'grazing_budget'   => t('grazing_budget'),
  'used_12m'         => t('used_12m'),
  'remaining'        => t('grazing_remaining'),
  'days_left'        => t('days_left'),
  'move_out_by'      => t('move_out_by'),
  'overgrazed'       => t('overgrazed'),
  'no_stocking_rate' => t('no_stocking_rate'),
  'animal_days'      => t('animal_days'),
  'no_grazing_data'  => t('no_grazing_data'),
  'current_herd'     => t('herd'),
  'no_herd'          => t('no_herd_assigned'),
  'animals_count'    => t('animals_count'),
  'days'             => t('days'),
  'ongoing'          => t('ongoing_label'),
]) ?>;

// Load camp with grazing info
fetch(`/api/camps.php?id=${CAMP_ID}`)
  .then(r => r.json())
  .then(res => {
    const c  = res.data;
    const g  = c?.grazing;
    const el = document.getElementById('grazing-panel');

    if (!g) {
      el.innerHTML = `<div style="color:var(--text-muted);font-size:0.875rem;font-style:italic">${T.no_stocking_rate} — ${T.no_grazing_data}</div>`;
      return;
    }

    const pct      = g.pct_used;
    const barColor = pct >= 100 ? '#c62828' : pct >= 80 ? '#e65100' : pct >= 60 ? '#f57f17' : '#2e7d32';
    const bgColor  = pct >= 100 ? '#ffebee' : pct >= 80 ? '#fff3e0' : pct >= 60 ? '#fffde7' : '#e8f5e9';

    let moveInfo = '';
    if (g.current_animals > 0) {
      if (g.days_left <= 0) {
        moveInfo = `<div style="margin-top:8px;padding:8px 12px;background:#ffebee;border-radius:8px;color:#c62828;font-weight:700">
          ⚠ ${T.overgrazed} — ${T.move_out_by.toLowerCase()} ${T.days_left.toLowerCase()}
        </div>`;
      } else {
        const moveDate = new Date(g.move_out_by + 'T00:00:00').toLocaleDateString(undefined,{weekday:'short',day:'numeric',month:'short',year:'numeric'});
        const urgencyColor = g.days_left <= 7 ? '#c62828' : g.days_left <= 14 ? '#e65100' : barColor;
        moveInfo = `<div style="margin-top:8px;padding:8px 12px;background:${bgColor};border-radius:8px;font-size:0.875rem">
          <strong style="color:${urgencyColor}">${g.days_left} ${T.days} ${T.days_left.toLowerCase()}</strong>
          &nbsp;·&nbsp; ${T.move_out_by}: <strong>${moveDate}</strong>
        </div>`;
      }
    }

    el.innerHTML = `
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px">
        <div style="text-align:center;padding:8px;background:var(--surface-1,#f8f8f8);border-radius:8px">
          <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em">${T.grazing_budget}</div>
          <div style="font-size:1.1rem;font-weight:700">${g.budget.toLocaleString()}</div>
          <div style="font-size:0.7rem;color:var(--text-muted)">${T.animal_days}/yr</div>
        </div>
        <div style="text-align:center;padding:8px;background:var(--surface-1,#f8f8f8);border-radius:8px">
          <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em">${T.used_12m}</div>
          <div style="font-size:1.1rem;font-weight:700;color:${barColor}">${g.used.toLocaleString()}</div>
          <div style="font-size:0.7rem;color:var(--text-muted)">${pct}%</div>
        </div>
        <div style="text-align:center;padding:8px;background:var(--surface-1,#f8f8f8);border-radius:8px">
          <div style="font-size:0.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em">${T.remaining}</div>
          <div style="font-size:1.1rem;font-weight:700;color:#2e7d32">${g.remaining.toLocaleString()}</div>
          <div style="font-size:0.7rem;color:var(--text-muted)">${T.animal_days}</div>
        </div>
      </div>
      <div style="height:8px;border-radius:4px;background:${bgColor};overflow:hidden">
        <div style="height:100%;width:${Math.min(100,pct)}%;background:${barColor};border-radius:4px"></div>
      </div>
      ${moveInfo}`;
  });

// Load current herd
fetch('/api/herds.php')
  .then(r => r.json())
  .then(res => {
    const herd = (res.data || []).find(h => h.camp_id == CAMP_ID);
    const el   = document.getElementById('current-herd-row');
    if (herd) {
      el.innerHTML = `<span class="text-sm text-muted">${T.current_herd}</span><br>
        <strong style="color:var(--green)">${escHtml(herd.name)}</strong>
        <span class="text-muted text-sm"> · ${herd.animal_count} ${T.animals_count}</span>`;
    } else {
      el.innerHTML = `<span class="text-sm text-muted" style="font-style:italic">${T.no_herd}</span>`;
    }
  });

// Load grazing history
fetch(`/api/herd_movements.php?camp_id=${CAMP_ID}`)
  .then(r => r.json())
  .then(res => {
    const el = document.getElementById('history-content');
    if (!res.data?.length) {
      el.innerHTML = `<div class="list-card"><div style="padding:16px;color:var(--text-muted);font-size:0.875rem">${T.no_grazing_data}</div></div>`;
      return;
    }

    // Summary totals
    const totalDays    = res.data.reduce((s,m) => s + (m.days||0), 0);
    const totalADays   = res.data.reduce((s,m) => s + (m.animal_days||0), 0);

    el.innerHTML = `
      <div class="list-card">
        ${res.data.map(m => {
          const dateIn  = fmtDate(m.date_in);
          const dateOut = m.date_out ? fmtDate(m.date_out) : null;
          const isOpen  = m.is_open;
          return `
            <div class="list-item" style="align-items:flex-start;padding:14px 16px">
              <div style="width:8px;height:8px;border-radius:50%;margin-top:6px;flex-shrink:0;
                background:${isOpen ? 'var(--green)' : 'var(--text-muted)'}"></div>
              <div class="item-body" style="margin-left:12px">
                <div class="item-title">${escHtml(m.herd_name || 'Unknown herd')}</div>
                <div class="item-sub" style="margin-top:2px">
                  <strong>In:</strong> ${dateIn}
                  ${dateOut
                    ? ` &nbsp;·&nbsp; <strong>Out:</strong> ${dateOut}`
                    : ` &nbsp;·&nbsp; <span style="color:var(--green);font-weight:600">${T.ongoing}</span>`}
                  &nbsp;·&nbsp; ${m.days} ${T.days}
                </div>
                <div class="item-sub" style="margin-top:2px">
                  ${m.animal_count ? m.animal_count + ' ' + T.animals_count : '–'}
                  &nbsp;·&nbsp; <strong>${(m.animal_days||0).toLocaleString()}</strong> ${T.animal_days}
                </div>
              </div>
            </div>`;
        }).join('')}
        <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;justify-content:space-between;font-size:0.8rem;color:var(--text-muted)">
          <span>${T.used_12m}: <strong>${totalADays.toLocaleString()} ${T.animal_days}</strong></span>
          <span>${totalDays} ${T.days} total</span>
        </div>
      </div>`;
  });

function fmtDate(d) {
  if (!d) return '–';
  return new Date(d + 'T00:00:00').toLocaleDateString(undefined, {day:'numeric', month:'short', year:'numeric'});
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
