<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
requireRole('super_admin');
$user = currentUser();
loadLanguage($user['language']);

$farms = DB::rows('SELECT id, name FROM farms WHERE is_active = 1 ORDER BY name');

$pageTitle = 'move_herd';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-truck-moving"></i> Move Herd</h1>
  <a href="/quick-actions.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<div style="padding:16px">

  <!-- Step 1: Choose herd -->
  <div id="step-herd">
    <p class="text-muted text-sm" style="margin-bottom:12px">Step 1 of 4 — Select a herd</p>
    <div id="herd-loading" class="page-loader" style="min-height:120px"><div class="spinner"></div></div>
    <div id="herd-list" style="display:none" class="list-card"></div>
  </div>

  <!-- Step 2: Choose farm -->
  <div id="step-farm" style="display:none">
    <div class="list-item" style="margin-bottom:12px;background:var(--surface-2,#f5f5f5);border-radius:8px;padding:12px 16px">
      <div class="item-body"><div class="item-sub">Herd</div><div class="item-title" id="chosen-herd-name"></div></div>
      <button class="btn btn-secondary btn-sm" onclick="restart()">Change</button>
    </div>
    <p class="text-muted text-sm" style="margin-bottom:12px">Step 2 of 4 — Select destination farm</p>
    <div class="list-card">
      <?php foreach ($farms as $f): ?>
      <button class="list-item" data-id="<?= $f['id'] ?>" data-name="<?= htmlspecialchars($f['name']) ?>"
        onclick="selectFarm(this)" style="cursor:pointer;width:100%;text-align:left;background:none;border:none;">
        <div class="item-body"><div class="item-title"><?= htmlspecialchars($f['name']) ?></div></div>
        <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Step 3: Choose camp -->
  <div id="step-camp" style="display:none">
    <div class="list-item" style="margin-bottom:8px;background:var(--surface-2,#f5f5f5);border-radius:8px;padding:12px 16px">
      <div class="item-body"><div class="item-sub">Herd</div><div class="item-title" id="chosen-herd-name-2"></div></div>
      <button class="btn btn-secondary btn-sm" onclick="restart()">Change</button>
    </div>
    <div class="list-item" style="margin-bottom:12px;background:var(--surface-2,#f5f5f5);border-radius:8px;padding:12px 16px">
      <div class="item-body"><div class="item-sub">Farm</div><div class="item-title" id="chosen-farm-name"></div></div>
      <button class="btn btn-secondary btn-sm" onclick="goToStep('step-farm')">Change</button>
    </div>
    <p class="text-muted text-sm" style="margin-bottom:12px">Step 3 of 4 — Select new camp</p>
    <div id="camp-loading" class="page-loader" style="min-height:80px;display:none"><div class="spinner"></div></div>
    <div id="camp-list" class="list-card"></div>
  </div>

  <!-- Step 4: Date -->
  <div id="step-date" style="display:none">
    <div class="list-item" style="margin-bottom:8px;background:var(--surface-2,#f5f5f5);border-radius:8px;padding:12px 16px">
      <div class="item-body"><div class="item-sub">Herd</div><div class="item-title" id="chosen-herd-name-3"></div></div>
      <button class="btn btn-secondary btn-sm" onclick="restart()">Change</button>
    </div>
    <div class="list-item" style="margin-bottom:8px;background:var(--surface-2,#f5f5f5);border-radius:8px;padding:12px 16px">
      <div class="item-body"><div class="item-sub">Farm</div><div class="item-title" id="chosen-farm-name-2"></div></div>
      <button class="btn btn-secondary btn-sm" onclick="goToStep('step-farm')">Change</button>
    </div>
    <div class="list-item" style="margin-bottom:16px;background:var(--surface-2,#f5f5f5);border-radius:8px;padding:12px 16px">
      <div class="item-body"><div class="item-sub">New Camp</div><div class="item-title" id="chosen-camp-name"></div></div>
      <button class="btn btn-secondary btn-sm" onclick="goToStep('step-camp')">Change</button>
    </div>
    <p class="text-muted text-sm" style="margin-bottom:12px">Step 4 of 4 — Entry date into new camp</p>
    <div class="form-group">
      <label class="form-label">Date Entering Camp <span class="required">*</span></label>
      <input type="date" id="move-date" class="form-control" value="<?= date('Y-m-d') ?>">
    </div>
    <button class="btn btn-primary btn-full btn-lg" id="save-btn" onclick="saveMove()">Move Herd</button>
  </div>

  <div id="save-result" style="margin-top:16px"></div>

</div>

<script>
let selectedHerd = null;
let selectedFarm = null;
let selectedCamp = null;

// Load herds on page load
fetch('/api/herds.php')
  .then(r => r.json())
  .then(res => {
    document.getElementById('herd-loading').style.display = 'none';
    const el = document.getElementById('herd-list');
    el.style.display = 'block';
    if (!res.data?.length) {
      el.innerHTML = '<div class="p-16 text-muted text-sm">No herds found.</div>';
      return;
    }
    el.innerHTML = res.data.map(h =>
      `<button class="list-item" data-id="${h.id}" data-name="${escHtml(h.name)}"
        onclick="selectHerd(this)" style="cursor:pointer;width:100%;text-align:left;background:none;border:none;">
        <div class="item-body">
          <div class="item-title">${escHtml(h.name)}</div>
          <div class="item-sub">${escHtml(h.farm_name || '')}${h.camp_name ? ' · ' + escHtml(h.camp_name) : ''} · ${h.animal_count} animals</div>
        </div>
        <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </button>`
    ).join('');
  });

function selectHerd(btn) {
  selectedHerd = {id: parseInt(btn.dataset.id), name: btn.dataset.name};
  document.getElementById('chosen-herd-name').textContent   = selectedHerd.name;
  document.getElementById('chosen-herd-name-2').textContent = selectedHerd.name;
  document.getElementById('chosen-herd-name-3').textContent = selectedHerd.name;
  goToStep('step-farm');
}

function selectFarm(btn) {
  selectedFarm = {id: parseInt(btn.dataset.id), name: btn.dataset.name};
  document.getElementById('chosen-farm-name').textContent   = selectedFarm.name;
  document.getElementById('chosen-farm-name-2').textContent = selectedFarm.name;
  loadCamps(selectedFarm.id);
  goToStep('step-camp');
}

function loadCamps(farmId) {
  const list = document.getElementById('camp-list');
  const loader = document.getElementById('camp-loading');
  list.innerHTML = '';
  loader.style.display = 'block';
  fetch(`/api/camps.php?farm_id=${farmId}`)
    .then(r => r.json())
    .then(res => {
      loader.style.display = 'none';
      if (!res.data?.length) {
        list.innerHTML = '<div class="p-16 text-muted text-sm">No camps on this farm.</div>';
        return;
      }
      list.innerHTML = res.data.map(c =>
        `<button class="list-item" data-id="${c.id}" data-name="${escHtml(c.name)}"
          onclick="selectCamp(this)" style="cursor:pointer;width:100%;text-align:left;background:none;border:none;">
          <div class="item-body">
            <div class="item-title">${escHtml(c.name)}</div>
            ${c.size_ha ? `<div class="item-sub">${parseFloat(c.size_ha)} ha</div>` : ''}
          </div>
          <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
        </button>`
      ).join('');
    });
}

function selectCamp(btn) {
  selectedCamp = {id: parseInt(btn.dataset.id), name: btn.dataset.name};
  document.getElementById('chosen-camp-name').textContent = selectedCamp.name;
  goToStep('step-date');
}

function goToStep(stepId) {
  ['step-herd','step-farm','step-camp','step-date'].forEach(id => {
    document.getElementById(id).style.display = id === stepId ? 'block' : 'none';
  });
}

function restart() {
  selectedHerd = null;
  selectedFarm = null;
  selectedCamp = null;
  goToStep('step-herd');
}

async function saveMove() {
  const date = document.getElementById('move-date').value;
  if (!date) { alert('Please select a date.'); return; }

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  btn.textContent = 'Moving…';

  try {
    // Fetch current herd data first, then update farm_id, camp_id
    const herdRes = await fetch(`/api/herds.php?id=${selectedHerd.id}`).then(r => r.json());
    if (!herdRes.success) throw new Error('Could not load herd data.');
    const h = herdRes.data;

    const res = await fetch(`/api/herds.php?id=${selectedHerd.id}`, {
      method: 'PUT',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        name:             h.name,
        color:            h.color ?? '#4CAF50',
        farm_id:          selectedFarm.id,
        camp_id:          selectedCamp.id,
        breeding_bull_id: h.breeding_bull_id ?? null,
        breeding_start:   h.breeding_start ?? null,
        breeding_end:     h.breeding_end ?? null,
        notes:            h.notes ?? null,
        move_date:        date,
      })
    }).then(r => r.json());

    if (res.success) {
      document.getElementById('step-date').style.display = 'none';
      document.getElementById('save-result').innerHTML = `
        <div class="card" style="text-align:center;padding:24px">
          <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:var(--green);margin-bottom:12px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z"/></svg>
          <h3>Herd moved!</h3>
          <p class="text-muted"><strong>${escHtml(selectedHerd.name)}</strong> moved to<br><strong>${escHtml(selectedCamp.name)}</strong> on <strong>${escHtml(selectedFarm.name)}</strong></p>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap">
            <button class="btn btn-primary" onclick="location.reload()">Move Another</button>
            <a href="/quick-actions.php" class="btn btn-secondary">Quick Actions</a>
          </div>
        </div>`;
    } else {
      alert(res.message || 'Error moving herd.');
      btn.disabled = false;
      btn.textContent = 'Move Herd';
    }
  } catch(e) {
    alert('Error: ' + e.message);
    btn.disabled = false;
    btn.textContent = 'Move Herd';
  }
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
