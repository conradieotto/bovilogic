<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$farmId = (int)($_GET['farm'] ?? 0);
$pageTitle = 'nav_herds';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="<?= $farmId ? "/camps.php?farm=$farmId" : '/index.php' ?>" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('herds') ?></h1>
  <?php if (isSuperAdmin()): ?>
  <button class="btn-icon" id="btn-add-herd">
    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
  </button>
  <?php endif; ?>
</header>

<div id="herds-list"><div class="page-loader"><div class="spinner"></div></div></div>

<?php if (isSuperAdmin()): ?>
<div class="modal-overlay" id="herd-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title" id="herd-modal-title"><?= t('add_herd') ?></div>
    <div class="modal-body">
      <form id="herd-form">
        <input type="hidden" id="herd-id" value="">
        <div class="form-group"><label class="form-label"><?= t('herd_name') ?> <span class="required">*</span></label><input type="text" id="herd-name" class="form-control"></div>
        <div class="form-group">
          <label class="form-label"><?= t('color') ?></label>
          <div class="color-picker" id="color-picker"></div>
          <input type="hidden" id="herd-color" value="#4CAF50">
        </div>
        <div class="form-group"><label class="form-label"><?= t('farm') ?> <span class="required">*</span></label>
          <select id="herd-farm" class="form-control"></select>
        </div>
        <div class="form-group"><label class="form-label"><?= t('camp') ?></label>
          <select id="herd-camp" class="form-control"><option value="">– None –</option></select>
        </div>
        <div class="form-group"><label class="form-label"><?= t('breeding_bull') ?></label>
          <select id="herd-bull" class="form-control"><option value="">– None –</option></select>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label"><?= t('breeding_start') ?></label><input type="date" id="herd-bs" class="form-control"></div>
          <div class="form-group"><label class="form-label"><?= t('breeding_end') ?></label><input type="date" id="herd-be" class="form-control"></div>
        </div>
        <div class="form-group"><label class="form-label"><?= t('notes') ?></label><textarea id="herd-notes" class="form-control"></textarea></div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('herd-modal')"><?= t('cancel') ?></button>
      <button class="btn btn-primary" id="herd-save-btn"><?= t('save') ?></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const isAdmin   = <?= isSuperAdmin() ? 'true' : 'false' ?>;
const filterFarm = <?= $farmId ?>;
const COLORS = ['#2E7D32','#1565C0','#E65100','#6A1B9A','#AD1457','#00695C','#F9A825','#4E342E'];

function renderColorPicker(selected) {
  const cp = document.getElementById('color-picker');
  if (!cp) return;
  cp.innerHTML = COLORS.map(c =>
    `<div class="color-swatch ${c===selected?'selected':''}" style="background:${c}" data-color="${c}" onclick="selectColor('${c}')"></div>`
  ).join('');
}

function selectColor(c) {
  document.getElementById('herd-color').value = c;
  renderColorPicker(c);
}

function loadHerds() {
  const url = `/api/herds.php${filterFarm ? '?farm_id='+filterFarm : ''}`;
  fetch(url).then(r=>r.json()).then(res=>{
    const el = document.getElementById('herds-list');
    if (!res.success || !res.data.length) {
      el.innerHTML = `<div class="empty-state">
        <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="15" cy="8" r="3"/><path d="M1 18v-1c0-2.2 3.6-4 8-4s8 1.8 8 4v1H1zm14.3-4c2.5.4 4.7 1.7 4.7 3v1h-4v-1c0-1.1-.7-2.1-1.8-2.9l1.1-.1z"/></svg>
        <h3>No herds</h3><p>Add a herd to get started.</p></div>`;
      return;
    }
    el.innerHTML = '<div class="list-card">' + res.data.map(h => `
      <a href="/animals.php?herd_id=${h.id}" class="list-item">
        <div class="item-icon" style="background:${escHtml(h.color)}22">
          <svg viewBox="0 0 24 24" style="fill:${escHtml(h.color)}"><circle cx="9" cy="8" r="3"/><circle cx="15" cy="8" r="3"/><path d="M1 18v-1c0-2.2 3.6-4 8-4s8 1.8 8 4v1H1zm14.3-4c2.5.4 4.7 1.7 4.7 3v1h-4v-1c0-1.1-.7-2.1-1.8-2.9l1.1-.1z"/></svg>
        </div>
        <div class="item-body">
          <div class="item-title">${escHtml(h.name)}</div>
          <div class="item-sub">${escHtml(h.farm_name||'')} ${h.camp_name ? '· '+escHtml(h.camp_name) : ''} · ${h.animal_count||0} animals</div>
        </div>
        <div class="item-end">
          ${isAdmin ? `<button class="btn btn-sm btn-secondary" onclick="editHerd(event,${JSON.stringify(h).replace(/"/g,'&quot;')})">Edit</button>` : ''}
        </div>
        <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </a>
    `).join('') + '</div>';
  });
}

let farms = [], camps = [], bulls = [];

if (isAdmin) {
  Promise.all([
    fetch('/api/farms.php').then(r=>r.json()),
    fetch('/api/camps.php').then(r=>r.json()),
    fetch('/api/animals.php?category=breeding_bull&status=active').then(r=>r.json()),
  ]).then(([fr, cr, br]) => {
    farms = fr.data || [];
    camps = cr.data || [];
    bulls = br.data || [];
    populateFarmSelect();
  });

  document.getElementById('btn-add-herd').addEventListener('click', () => {
    document.getElementById('herd-id').value = '';
    document.getElementById('herd-name').value = '';
    document.getElementById('herd-bs').value = '';
    document.getElementById('herd-be').value = '';
    document.getElementById('herd-notes').value = '';
    renderColorPicker('#4CAF50');
    document.getElementById('herd-color').value = '#4CAF50';
    document.getElementById('herd-modal-title').textContent = '<?= t('add_herd') ?>';
    if (filterFarm) document.getElementById('herd-farm').value = filterFarm;
    openModal('herd-modal');
  });

  document.getElementById('herd-save-btn').addEventListener('click', saveHerd);
  document.getElementById('herd-farm').addEventListener('change', updateCamps);
}

function populateFarmSelect() {
  const sel = document.getElementById('herd-farm');
  sel.innerHTML = '<option value="">– Select Farm –</option>' +
    farms.map(f=>`<option value="${f.id}">${escHtml(f.name)}</option>`).join('');
  updateBulls();
}

function updateCamps() {
  const farmId = document.getElementById('herd-farm').value;
  const sel    = document.getElementById('herd-camp');
  sel.innerHTML = '<option value="">– None –</option>' +
    camps.filter(c=>!farmId||c.farm_id==farmId).map(c=>`<option value="${c.id}">${escHtml(c.name)}</option>`).join('');
}

function updateBulls() {
  const sel = document.getElementById('herd-bull');
  sel.innerHTML = '<option value="">– None –</option>' +
    bulls.map(b=>`<option value="${b.id}">${escHtml(b.ear_tag)}</option>`).join('');
}

function editHerd(e, herd) {
  e.preventDefault();
  document.getElementById('herd-id').value        = herd.id;
  document.getElementById('herd-name').value      = herd.name;
  document.getElementById('herd-farm').value      = herd.farm_id;
  document.getElementById('herd-bs').value        = herd.breeding_start || '';
  document.getElementById('herd-be').value        = herd.breeding_end || '';
  document.getElementById('herd-notes').value     = herd.notes || '';
  renderColorPicker(herd.color || '#4CAF50');
  document.getElementById('herd-color').value     = herd.color || '#4CAF50';
  document.getElementById('herd-modal-title').textContent = '<?= t('edit_herd') ?>';
  updateCamps();
  setTimeout(() => {
    document.getElementById('herd-camp').value = herd.camp_id || '';
    document.getElementById('herd-bull').value = herd.breeding_bull_id || '';
  }, 50);
  openModal('herd-modal');
}

function saveHerd() {
  const id   = document.getElementById('herd-id').value;
  const name = document.getElementById('herd-name').value.trim();
  if (!name) { alert('Herd name is required.'); return; }
  const body = {
    name,
    color:             document.getElementById('herd-color').value,
    farm_id:           document.getElementById('herd-farm').value,
    camp_id:           document.getElementById('herd-camp').value || null,
    breeding_bull_id:  document.getElementById('herd-bull').value || null,
    breeding_start:    document.getElementById('herd-bs').value || null,
    breeding_end:      document.getElementById('herd-be').value || null,
    notes:             document.getElementById('herd-notes').value.trim(),
  };
  const method = id ? 'PUT' : 'POST';
  const url    = id ? `/api/herds.php?id=${id}` : '/api/herds.php';
  fetch(url, { method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) })
    .then(r=>r.json())
    .then(res => {
      if (res.success) { closeModal('herd-modal'); loadHerds(); }
      else alert(res.message || 'Error saving herd.');
    });
}

function escHtml(s) { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

loadHerds();
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
