<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
requireRole('super_admin');
$user = currentUser();
loadLanguage($user['language']);

$herds = DB::rows('SELECT h.id, h.name, f.name AS farm_name FROM herds h LEFT JOIN farms f ON f.id = h.farm_id WHERE h.is_active = 1 ORDER BY f.name, h.name');

$pageTitle = 'add_vaccination';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/quick-actions.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1>Add Vaccination</h1>
</header>

<div style="padding:16px;">

  <!-- Step 1: Scope -->
  <div id="step-scope">
    <p class="text-muted text-sm" style="margin-bottom:16px">Apply vaccination to:</p>
    <div style="display:flex;flex-direction:column;gap:12px;">
<button class="btn btn-secondary" style="justify-content:flex-start;gap:12px;padding:16px;" onclick="setScope('herd')">
        <svg viewBox="0 0 24 24" style="width:24px;height:24px;flex-shrink:0"><circle cx="9" cy="8" r="3"/><circle cx="15" cy="8" r="3"/><path d="M1 18v-1c0-2.2 3.6-4 8-4s8 1.8 8 4v1H1zm14.3-4c2.5.4 4.7 1.7 4.7 3v1h-4v-1c0-1.1-.7-2.1-1.8-2.9l1.1-.1z"/></svg>
        <div style="text-align:left"><strong>By Herd</strong><br><span class="text-xs text-muted">Apply to all animals in a herd</span></div>
      </button>
      <button class="btn btn-secondary" style="justify-content:flex-start;gap:12px;padding:16px;" onclick="setScope('all')">
        <svg viewBox="0 0 24 24" style="width:24px;height:24px;flex-shrink:0"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        <div style="text-align:left"><strong>All Animals</strong><br><span class="text-xs text-muted">Apply to every active animal</span></div>
      </button>
    </div>
  </div>

  <!-- Step 2: Target selection -->
  <div id="step-target" style="display:none">

<div id="target-herd" style="display:none">
      <div class="form-group">
        <label class="form-label">Select Herd <span class="required">*</span></label>
        <select id="herd-select" class="form-control">
          <option value="">– Choose herd –</option>
          <?php foreach ($herds as $h): ?>
          <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['farm_name'] . ' – ' . $h['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="herd-animal-count" class="text-muted text-sm" style="margin-bottom:16px"></div>
    </div>

    <div id="target-all" style="display:none">
      <div class="card" style="margin-bottom:16px;background:var(--green-light,#e8f5e9)">
        <div class="card-body text-sm">This will add the vaccination to <strong id="all-animal-count">all active animals</strong>.</div>
      </div>
    </div>

    <button class="btn btn-secondary btn-sm" onclick="backToScope()" style="margin-bottom:20px">← Change scope</button>
  </div>

  <!-- Step 3: Vaccination details (always visible after scope chosen) -->
  <div id="step-details" style="display:none">
    <div style="border-top:1px solid var(--border);padding-top:16px;margin-top:4px;">
      <div class="form-group">
        <label class="form-label">Product <span class="required">*</span></label>
        <input type="text" id="v-product" class="form-control" placeholder="e.g. Multimin, Bovivac">
      </div>
      <div class="form-group">
        <label class="form-label">Dosage</label>
        <input type="text" id="v-dosage" class="form-control" placeholder="e.g. 2ml">
      </div>
      <div class="form-group">
        <label class="form-label">Due Date <span class="required">*</span></label>
        <input type="date" id="v-due" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea id="v-notes" class="form-control" rows="2"></textarea>
      </div>

      <button class="btn btn-primary btn-full btn-lg" id="save-btn" onclick="saveVaccinations()">Save Vaccination</button>
    </div>
  </div>

  <!-- Progress / result -->
  <div id="save-result" style="display:none;margin-top:16px"></div>

</div>

<script>
let scope = null;
const herdsData = <?= json_encode($herds) ?>;

function setScope(s) {
  scope = s;
  document.getElementById('step-scope').style.display = 'none';
  document.getElementById('step-target').style.display = 'block';
document.getElementById('target-herd').style.display = s === 'herd' ? 'block' : 'none';
  document.getElementById('target-all').style.display  = s === 'all'  ? 'block' : 'none';
  document.getElementById('step-details').style.display = 'block';

  if (s === 'all') {
    fetch('/api/animals.php?status=active&limit=9999')
      .then(r => r.json())
      .then(res => {
        const count = res.data?.length ?? 0;
        document.getElementById('all-animal-count').textContent = count + ' active animal' + (count !== 1 ? 's' : '');
      });
  }

  if (s === 'herd') {
    document.getElementById('herd-select').addEventListener('change', updateHerdCount);
  }
}

function backToScope() {
  scope = null;
  document.getElementById('step-scope').style.display = 'block';
  document.getElementById('step-target').style.display = 'none';
  document.getElementById('step-details').style.display = 'none';
  document.getElementById('save-result').style.display = 'none';
}

function updateHerdCount() {
  const herdId = document.getElementById('herd-select').value;
  const el = document.getElementById('herd-animal-count');
  if (!herdId) { el.textContent = ''; return; }
  fetch(`/api/animals.php?herd_id=${herdId}&status=active`)
    .then(r => r.json())
    .then(res => {
      const count = res.data?.length ?? 0;
      el.textContent = count + ' active animal' + (count !== 1 ? 's' : '') + ' in this herd.';
    });
}

async function saveVaccinations() {
  const product = document.getElementById('v-product').value.trim();
  const due     = document.getElementById('v-due').value;
  if (!product || !due) { alert('Product and due date are required.'); return; }

  const payload = {
    product,
    dosage:   document.getElementById('v-dosage').value.trim(),
    due_date: due,
    notes:    document.getElementById('v-notes').value.trim(),
  };

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  btn.textContent = 'Saving…';

  try {
    if (scope === 'herd') {
      const herdId = document.getElementById('herd-select').value;
      if (!herdId) { alert('Please select a herd.'); btn.disabled = false; btn.textContent = 'Save Vaccination'; return; }
      const res = await fetch(`/api/animals.php?herd_id=${herdId}&status=active`).then(r => r.json());
      const animals = res.data ?? [];
      if (!animals.length) { alert('No active animals in this herd.'); btn.disabled = false; btn.textContent = 'Save Vaccination'; return; }
      for (const a of animals) await postVacc({ ...payload, animal_id: a.id, herd_id: parseInt(herdId) });
      showResult(animals.length);

    } else if (scope === 'all') {
      const res = await fetch('/api/animals.php?status=active&limit=9999').then(r => r.json());
      const animals = res.data ?? [];
      if (!animals.length) { alert('No active animals found.'); btn.disabled = false; btn.textContent = 'Save Vaccination'; return; }
      for (const a of animals) await postVacc({ ...payload, animal_id: a.id });
      showResult(animals.length);
    }
  } catch(e) {
    alert('Error saving vaccinations: ' + e.message);
    btn.disabled = false;
    btn.textContent = 'Save Vaccination';
  }
}

function postVacc(data) {
  return fetch('/api/vaccinations.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(data)
  }).then(r => r.json());
}

function showResult(count) {
  document.getElementById('step-target').style.display = 'none';
  document.getElementById('step-details').style.display = 'none';
  document.getElementById('save-result').style.display = 'block';
  document.getElementById('save-result').innerHTML = `
    <div class="card" style="text-align:center;padding:24px">
      <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:var(--green);margin-bottom:12px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z"/></svg>
      <h3>Done!</h3>
      <p class="text-muted">Vaccination added to <strong>${count}</strong> animal${count !== 1 ? 's' : ''}.</p>
      <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap">
        <button class="btn btn-primary" onclick="location.reload()">Add Another</button>
        <a href="/quick-actions.php" class="btn btn-secondary">Quick Actions</a>
      </div>
    </div>`;
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
