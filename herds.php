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

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-people-group"></i> <?= t('herds') ?></h1>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-sm" id="btn-add-herd"><i class="fa-solid fa-plus"></i> <?= t('add_herd') ?></button>
  <?php endif; ?>
</div>

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
        <div class="form-group">
          <label class="form-label"><?= t('breeding_bull') ?></label>
          <div id="bull-chips" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;min-height:4px"></div>
          <select id="herd-bull-select" class="form-control">
            <option value="">– Add Bull –</option>
          </select>
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
const COLORS = ['#2E7D32','#1565C0','#E65100','#6A1B9A','#AD1457','#00695C','#F9A825','#4E342E','#FFFFFF','#000000'];
const T = <?= json_encode([
  'no_herds_yet'    => t('no_herds_yet'),
  'add_herd'        => t('add_herd'),
  'edit'            => t('edit'),
  'delete'          => t('delete'),
  'animals_count'   => t('animals_count'),
  'breeding_bull'   => t('breeding_bull'),
  'breeding_start'  => t('breeding_start'),
  'breeding_end'    => t('breeding_end'),
  'pregnancy_rate'  => t('pregnancy_rate'),
  'excellent'       => t('excellent'),
  'good'            => t('good'),
  'poor_label'      => t('poor_label'),
]) ?>;

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
        <h3>${T.no_herds_yet}</h3><p>${T.add_herd}</p></div>`;
      return;
    }
    el.innerHTML = '<div class="list-card">' + res.data.map(h => `
      <a href="/animals.php?herd_id=${h.id}" class="list-item">
        <div class="item-icon" style="background:${escHtml(h.color)}22">
          <svg viewBox="0 0 24 24" style="fill:${escHtml(h.color)}"><circle cx="9" cy="8" r="3"/><circle cx="15" cy="8" r="3"/><path d="M1 18v-1c0-2.2 3.6-4 8-4s8 1.8 8 4v1H1zm14.3-4c2.5.4 4.7 1.7 4.7 3v1h-4v-1c0-1.1-.7-2.1-1.8-2.9l1.1-.1z"/></svg>
        </div>
        <div class="item-body">
          <div class="item-title">${escHtml(h.name)}</div>
          <div class="item-sub">${escHtml(h.farm_name||'')} ${h.camp_name ? '· '+escHtml(h.camp_name) : ''} · ${h.animal_count||0} ${T.animals_count}${h.bulls&&h.bulls.length ? ' · '+T.breeding_bull+': '+h.bulls.map(b=>escHtml(b.ear_tag)).join(', ') : ''}</div>
          ${(h.breeding_start || h.breeding_end) ? `<div class="item-sub" style="margin-top:2px">
            ${h.breeding_start ? T.breeding_start+': '+h.breeding_start : ''} ${h.breeding_end ? '· '+T.breeding_end+': '+h.breeding_end : ''}
          </div>` : ''}
          ${h.pregnancy_rate != null ? `<div class="item-sub" style="margin-top:2px">
            ${T.pregnancy_rate}: <strong>${h.pregnancy_rate}%</strong>
            ${(()=>{
              const r = h.pregnancy_rate;
              const label = r >= 86 ? T.excellent : r >= 75 ? T.good : T.poor_label;
              const bg    = r >= 86 ? '#e8f5e9'   : r >= 75 ? '#fff8e1' : '#ffebee';
              const color = r >= 86 ? '#2e7d32'   : r >= 75 ? '#f57f17' : '#c62828';
              return `<span style="display:inline-block;margin-left:6px;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700;background:${bg};color:${color}">${label}</span>`;
            })()}
            ${h.last_pregnancy_test ? '<span style="color:var(--text-muted);font-size:11px"> · '+h.last_pregnancy_test+'</span>' : ''}
          </div>` : ''}
        </div>
        <div class="item-end">
          ${isAdmin ? `
            <div style="display:flex;gap:6px">
              <button class="btn btn-sm btn-secondary" onclick="editHerd(event,${JSON.stringify(h).replace(/"/g,'&quot;')})">${T.edit}</button>
              <button class="btn btn-sm btn-danger"    onclick="deleteHerd(event,${h.id},${JSON.stringify(h.name).replace(/"/g,'&quot;')})">${T.delete}</button>
            </div>` : ''}
        </div>
        <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </a>
    `).join('') + '</div>';
  });
}

let farms = [], camps = [], bulls = [], selectedBullIds = [];

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
    selectedBullIds = []; renderBullChips();
    if (filterFarm) document.getElementById('herd-farm').value = filterFarm;
    openModal('herd-modal');
  });

  document.getElementById('herd-save-btn').addEventListener('click', saveHerd);
  document.getElementById('herd-farm').addEventListener('change', updateCamps);
  document.getElementById('herd-bull-select').addEventListener('change', function() { addBull(this.value); });
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
  const sel = document.getElementById('herd-bull-select');
  sel.innerHTML = '<option value="">– Add Bull –</option>' +
    bulls.map(b=>`<option value="${b.id}">${escHtml(b.ear_tag)}</option>`).join('');
}

function renderBullChips() {
  const container = document.getElementById('bull-chips');
  container.innerHTML = selectedBullIds.map(id => {
    const bull = bulls.find(b => b.id == id);
    const tag  = bull ? escHtml(bull.ear_tag) : id;
    return `<span style="display:inline-flex;align-items:center;gap:4px;background:var(--surface-2);border:1px solid var(--border);border-radius:999px;padding:4px 10px;font-size:0.8125rem;font-weight:600">
      ${tag}
      <button type="button" onclick="removeBull(${id})" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1rem;line-height:1;padding:0 2px">&times;</button>
    </span>`;
  }).join('');
}

function addBull(id) {
  id = parseInt(id);
  if (!id || selectedBullIds.includes(id)) return;
  selectedBullIds.push(id);
  renderBullChips();
  document.getElementById('herd-bull-select').value = '';
}

function removeBull(id) {
  selectedBullIds = selectedBullIds.filter(b => b !== id);
  renderBullChips();
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
    selectedBullIds = (herd.bulls && herd.bulls.length)
      ? herd.bulls.map(b => b.id)
      : (herd.breeding_bull_id ? [parseInt(herd.breeding_bull_id)] : []);
    renderBullChips();
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
    bull_ids:          selectedBullIds,
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
    })
    .catch(err => alert('Save failed: ' + err.message));
}

function deleteHerd(e, id, name) {
  e.preventDefault();
  if (!confirm(`Delete herd "${name}"?\n\nAnimals in this herd will not be deleted.`)) return;
  fetch(`/api/herds.php?id=${id}`, { method: 'DELETE' })
    .then(r => r.json())
    .then(res => {
      if (res.success) { showToast('Herd deleted'); loadHerds(); }
      else alert(res.message || 'Error deleting herd.');
    });
}

function escHtml(s) { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

loadHerds();
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
