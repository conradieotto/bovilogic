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

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-syringe"></i> <?= t('add_vaccination') ?></h1>
  <a href="/quick-actions.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> <?= t('back') ?></a>
</div>

<div style="padding:16px;">

  <!-- Step 1: Scope -->
  <div id="step-scope">
    <p class="text-muted text-sm" style="margin-bottom:16px"><?= t('vacc_apply_to') ?></p>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <button class="btn btn-secondary" style="justify-content:flex-start;gap:12px;padding:16px;" onclick="setScope('herd')">
        <svg viewBox="0 0 24 24" style="width:24px;height:24px;flex-shrink:0"><circle cx="9" cy="8" r="3"/><circle cx="15" cy="8" r="3"/><path d="M1 18v-1c0-2.2 3.6-4 8-4s8 1.8 8 4v1H1zm14.3-4c2.5.4 4.7 1.7 4.7 3v1h-4v-1c0-1.1-.7-2.1-1.8-2.9l1.1-.1z"/></svg>
        <div style="text-align:left"><strong><?= t('by_herd') ?></strong><br><span class="text-xs text-muted"><?= t('apply_herd_sub') ?></span></div>
      </button>
      <button class="btn btn-secondary" style="justify-content:flex-start;gap:12px;padding:16px;" onclick="setScope('all')">
        <svg viewBox="0 0 24 24" style="width:24px;height:24px;flex-shrink:0"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        <div style="text-align:left"><strong><?= t('all_animals_label') ?></strong><br><span class="text-xs text-muted"><?= t('apply_all_sub') ?></span></div>
      </button>
    </div>
  </div>

  <!-- Step 2: Target selection -->
  <div id="step-target" style="display:none">

    <div id="target-herd" style="display:none">
      <div class="form-group">
        <label class="form-label"><?= t('herd') ?> <span class="required">*</span></label>
        <select id="herd-select" class="form-control">
          <option value=""><?= t('choose_herd_ph') ?></option>
          <?php foreach ($herds as $h): ?>
          <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['farm_name'] . ' – ' . $h['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div id="herd-animal-count" class="text-muted text-sm" style="margin-bottom:16px"></div>
    </div>

    <div id="target-all" style="display:none">
      <div class="card" style="margin-bottom:16px;background:var(--green-light,#e8f5e9)">
        <div class="card-body text-sm" id="all-animal-msg"></div>
      </div>
    </div>

    <button class="btn btn-secondary btn-sm" onclick="backToScope()" style="margin-bottom:20px">← <?= t('change_scope') ?></button>
  </div>

  <!-- Step 3: Vaccination details -->
  <div id="step-details" style="display:none">
    <div style="border-top:1px solid var(--border);padding-top:16px;margin-top:4px;">
      <div class="form-group">
        <label class="form-label"><?= t('product') ?> <span class="required">*</span></label>
        <input type="text" id="v-product" class="form-control" placeholder="e.g. Multimin, Bovivac">
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('dosage') ?></label>
        <input type="text" id="v-dosage" class="form-control" placeholder="e.g. 2ml">
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('due_date') ?> <span class="required">*</span></label>
        <input type="date" id="v-due" class="form-control" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label class="form-label"><?= t('notes') ?></label>
        <textarea id="v-notes" class="form-control" rows="2"></textarea>
      </div>

      <button class="btn btn-primary btn-full btn-lg" id="save-btn" onclick="saveVaccinations()"><?= t('save_vaccination') ?></button>
    </div>
  </div>

  <!-- Result -->
  <div id="save-result" style="display:none;margin-top:16px"></div>

</div>

<script>
const T = <?= json_encode([
  'product_due_required'  => t('product_due_required'),
  'req_select_herd'       => t('req_select_herd'),
  'no_active_in_herd'     => t('no_active_in_herd'),
  'no_active_animals'     => t('no_active_animals'),
  'saving'                => t('saving'),
  'save_vaccination'      => t('save_vaccination'),
  'mark_done'             => t('mark_done'),
  'vacc_added_to'         => t('vacc_added_to'),
  'animals_count'         => t('animals_count'),
  'animals_in_herd'       => t('animals_in_herd'),
  'add_another'           => t('add_another'),
  'quick_actions'         => t('nav_quick_actions'),
  'error_saving'          => t('error_saving'),
]) ?>;

let scope = null;
const herdsData = <?= json_encode($herds) ?>;

function setScope(s) {
  scope = s;
  document.getElementById('step-scope').style.display  = 'none';
  document.getElementById('step-target').style.display = 'block';
  document.getElementById('target-herd').style.display = s === 'herd' ? 'block' : 'none';
  document.getElementById('target-all').style.display  = s === 'all'  ? 'block' : 'none';
  document.getElementById('step-details').style.display = 'block';

  if (s === 'all') {
    fetch('/api/animals.php?status=active&limit=9999')
      .then(r => r.json())
      .then(res => {
        const count = res.data?.length ?? 0;
        document.getElementById('all-animal-msg').textContent = count + ' ' + T.animals_count;
      });
  }

  if (s === 'herd') {
    document.getElementById('herd-select').addEventListener('change', updateHerdCount);
  }
}

function backToScope() {
  scope = null;
  document.getElementById('step-scope').style.display  = 'block';
  document.getElementById('step-target').style.display = 'none';
  document.getElementById('step-details').style.display = 'none';
  document.getElementById('save-result').style.display  = 'none';
}

function updateHerdCount() {
  const herdId = document.getElementById('herd-select').value;
  const el = document.getElementById('herd-animal-count');
  if (!herdId) { el.textContent = ''; return; }
  fetch(`/api/animals.php?herd_id=${herdId}&status=active`)
    .then(r => r.json())
    .then(res => {
      const count = res.data?.length ?? 0;
      el.textContent = count + ' ' + T.animals_in_herd;
    });
}

async function saveVaccinations() {
  const product = document.getElementById('v-product').value.trim();
  const due     = document.getElementById('v-due').value;
  if (!product || !due) { alert(T.product_due_required); return; }

  const payload = {
    product,
    dosage:   document.getElementById('v-dosage').value.trim(),
    due_date: due,
    notes:    document.getElementById('v-notes').value.trim(),
  };

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  btn.textContent = T.saving;

  try {
    if (scope === 'herd') {
      const herdId = document.getElementById('herd-select').value;
      if (!herdId) { alert(T.req_select_herd); btn.disabled = false; btn.textContent = T.save_vaccination; return; }
      const res = await fetch(`/api/animals.php?herd_id=${herdId}&status=active`).then(r => r.json());
      const animals = res.data ?? [];
      if (!animals.length) { alert(T.no_active_in_herd); btn.disabled = false; btn.textContent = T.save_vaccination; return; }
      for (const a of animals) await postVacc({ ...payload, animal_id: a.id, herd_id: parseInt(herdId) });
      showResult(animals.length);

    } else if (scope === 'all') {
      const res = await fetch('/api/animals.php?status=active&limit=9999').then(r => r.json());
      const animals = res.data ?? [];
      if (!animals.length) { alert(T.no_active_animals); btn.disabled = false; btn.textContent = T.save_vaccination; return; }
      for (const a of animals) await postVacc({ ...payload, animal_id: a.id });
      showResult(animals.length);
    }
  } catch(e) {
    alert(T.error_saving + ' ' + e.message);
    btn.disabled = false;
    btn.textContent = T.save_vaccination;
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
  document.getElementById('step-target').style.display  = 'none';
  document.getElementById('step-details').style.display = 'none';
  document.getElementById('save-result').style.display  = 'block';
  document.getElementById('save-result').innerHTML = `
    <div class="card" style="text-align:center;padding:24px">
      <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:var(--green);margin-bottom:12px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z"/></svg>
      <h3>${T.mark_done}!</h3>
      <p class="text-muted">${T.vacc_added_to} <strong>${count}</strong> ${T.animals_count}.</p>
      <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap">
        <button class="btn btn-primary" onclick="location.reload()">${T.add_another}</button>
        <a href="/quick-actions.php" class="btn btn-secondary">${T.quick_actions}</a>
      </div>
    </div>`;
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
