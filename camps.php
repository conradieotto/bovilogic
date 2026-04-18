<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$farmId = (int)($_GET['farm'] ?? 0);
$farm   = $farmId ? DB::row('SELECT * FROM farms WHERE id = ?', [$farmId]) : null;
$farms  = DB::rows('SELECT id, name FROM farms WHERE is_active = 1 ORDER BY name');

$pageTitle = 'camps';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-map-location-dot"></i> <?= $farm ? htmlspecialchars($farm['name']) : t('camps') ?></h1>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-sm" id="btn-add-camp"><i class="fa-solid fa-plus"></i> <?= t('add_camp') ?></button>
  <?php endif; ?>
</div>

<!-- Quick links -->
<div class="dash-grid" style="margin-top:12px">
  <a href="/herds.php<?= $farmId ? "?farm=$farmId" : '' ?>" class="dash-btn">
    <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="15" cy="8" r="3"/><path d="M1 18v-1c0-2.2 3.6-4 8-4s8 1.8 8 4v1H1zm14.3-4c2.5.4 4.7 1.7 4.7 3v1h-4v-1c0-1.1-.7-2.1-1.8-2.9l1.1-.1z"/></svg>
    <?= t('herds') ?>
  </a>
  <a href="/animals.php<?= $farmId ? "?farm_id=$farmId" : '' ?>" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
    <?= t('animals') ?>
  </a>
</div>

<div class="section-header"><h2><?= t('camps') ?></h2></div>
<div id="camps-list"><div class="page-loader"><div class="spinner"></div></div></div>

<?php if (isSuperAdmin()): ?>
<!-- Add / Edit Camp Modal -->
<div class="modal-overlay" id="camp-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title" id="camp-modal-title"><?= t('add_camp') ?></div>
    <div class="modal-body">
      <input type="hidden" id="camp-id" value="">

      <div class="form-group" id="farm-select-group" <?= $farmId ? 'style="display:none"' : '' ?>>
        <label class="form-label"><?= t('farm') ?> <span class="required">*</span></label>
        <select id="camp-farm" class="form-control">
          <option value="">– Select Farm –</option>
          <?php foreach ($farms as $f): ?>
          <option value="<?= $f['id'] ?>" <?= $f['id'] == $farmId ? 'selected' : '' ?>>
            <?= htmlspecialchars($f['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label"><?= t('camp') ?> <?= t('name') ?> <span class="required">*</span></label>
        <input type="text" id="camp-name" class="form-control" placeholder="e.g. Camp A, North Paddock">
      </div>

      <div class="form-group">
        <label class="form-label"><?= t('size_ha') ?></label>
        <input type="number" id="camp-size" class="form-control" step="0.1" min="0" placeholder="e.g. 45.5">
      </div>

      <div class="form-group">
        <label class="form-label"><?= t('stocking_rate') ?></label>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="font-size:1rem;font-weight:600;white-space:nowrap">1 :</span>
          <input type="number" id="camp-ratio" class="form-control" step="0.5" min="1" placeholder="e.g. 10">
          <span style="color:var(--text-muted);font-size:0.82rem;white-space:nowrap"><?= t('ha_per_animal') ?></span>
        </div>
        <div style="color:var(--text-muted);font-size:0.78rem;margin-top:4px"><?= t('stocking_rate_hint') ?></div>
      </div>

      <div class="form-group">
        <label class="form-label"><?= t('assign_herd') ?></label>
        <select id="camp-herd" class="form-control">
          <option value="">– No herd –</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label"><?= t('notes') ?></label>
        <textarea id="camp-notes" class="form-control"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('camp-modal')"><?= t('cancel') ?></button>
      <button class="btn btn-primary" onclick="saveCamp()"><?= t('save') ?></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const FARM_ID = <?= $farmId ?>;
const isAdmin = <?= isSuperAdmin() ? 'true' : 'false' ?>;
const T = <?= json_encode([
  'no_camps_yet'      => t('no_camps_yet'),
  'add_camp'          => t('add_camp'),
  'edit'              => t('edit'),
  'delete'            => t('delete'),
  'no_herd_assigned'  => t('no_herd_assigned'),
  'days_left'         => t('days_left'),
  'move_out_by'       => t('move_out_by'),
  'overgrazed'        => t('overgrazed'),
  'no_stocking_rate'  => t('no_stocking_rate'),
  'animals_count'     => t('animals_count'),
]) ?>;

let allHerds = [];
fetch('/api/herds.php').then(r=>r.json()).then(res => {
  allHerds = res.data || [];
  populateHerdDropdown(FARM_ID);
});

function populateHerdDropdown(farmId) {
  const sel = document.getElementById('camp-herd');
  if (!sel) return;
  const filtered = farmId ? allHerds.filter(h => h.farm_id == farmId) : allHerds;
  sel.innerHTML = '<option value="">– No herd –</option>' +
    filtered.map(h => `<option value="${h.id}">${escHtml(h.name)}</option>`).join('');
}

function grazingBar(g) {
  if (!g) return `<div class="item-sub" style="margin-top:4px;font-style:italic;color:var(--text-muted)">${T.no_stocking_rate}</div>`;

  const pct      = g.pct_used;
  const barColor = pct >= 100 ? '#c62828' : pct >= 80 ? '#e65100' : pct >= 60 ? '#f57f17' : '#2e7d32';
  const bgColor  = pct >= 100 ? '#ffebee' : pct >= 80 ? '#fff3e0' : pct >= 60 ? '#fffde7' : '#e8f5e9';

  let statusLine = '';
  if (g.current_animals > 0) {
    if (g.days_left <= 0) {
      statusLine = `<span style="color:#c62828;font-weight:700">${T.overgrazed}</span>`;
    } else {
      const d = g.days_left === 1 ? '1 day' : g.days_left + ' ' + T.days_left;
      const moveDate = new Date(g.move_out_by).toLocaleDateString(undefined,{day:'numeric',month:'short'});
      statusLine = `<span style="color:${barColor};font-weight:600">${d} left</span> · ${T.move_out_by} ${moveDate}`;
    }
  } else {
    statusLine = `${g.remaining.toLocaleString()} animal-days remaining`;
  }

  return `
    <div style="margin-top:6px">
      <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:var(--text-muted);margin-bottom:3px">
        <span>${statusLine}</span>
        <span>${pct}% used</span>
      </div>
      <div style="height:6px;border-radius:3px;background:${bgColor};overflow:hidden">
        <div style="height:100%;width:${Math.min(100,pct)}%;background:${barColor};border-radius:3px;transition:width 0.4s"></div>
      </div>
    </div>`;
}

function loadCamps() {
  const url = FARM_ID ? `/api/camps.php?farm_id=${FARM_ID}` : '/api/camps.php';
  fetch(url).then(r=>r.json()).then(res => {
    const el = document.getElementById('camps-list');
    if (!res.data?.length) {
      el.innerHTML = `<div class="empty-state">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
        <h3>${T.no_camps_yet}</h3>
        <p>${T.add_camp}</p>
        ${isAdmin ? `<button class="btn btn-primary" onclick="document.getElementById('btn-add-camp').click()">${T.add_camp}</button>` : ''}
      </div>`;
      return;
    }

    el.innerHTML = '<div class="list-card">' + res.data.map(c => {
      const herd = allHerds.find(h => h.camp_id == c.id);
      return `
        <a href="/camp-detail.php?id=${c.id}" class="list-item" style="flex-direction:column;align-items:stretch;padding:12px 16px;gap:0">
          <div style="display:flex;align-items:center;gap:12px">
            <div class="item-icon" style="flex-shrink:0">
              <svg viewBox="0 0 24 24" style="fill:none;stroke:var(--green);stroke-width:2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
            </div>
            <div class="item-body" style="min-width:0">
              <div class="item-title">${escHtml(c.name)}</div>
              <div class="item-sub">
                ${c.size_ha ? c.size_ha + ' ha' : ''}
                ${c.stocking_ratio ? ' · 1:' + c.stocking_ratio : ''}
                ${c.size_ha || c.stocking_ratio ? ' · ' : ''}
                ${herd
                  ? `<span style="color:var(--green);font-weight:600">${escHtml(herd.name)}</span> (${herd.animal_count} ${T.animals_count})`
                  : `<span style="color:var(--text-muted)">${T.no_herd_assigned}</span>`}
              </div>
            </div>
            ${isAdmin ? `
            <div class="list-actions" style="flex-shrink:0" onclick="event.preventDefault()">
              <button class="btn btn-sm btn-secondary" onclick="editCamp(event,${JSON.stringify(c).replace(/"/g,'&quot;')})">${T.edit}</button>
              <button class="btn btn-sm btn-danger"    onclick="deleteCamp(event,${c.id},'${escHtml(c.name)}')">${T.delete}</button>
            </div>` : ''}
            <svg class="chevron" viewBox="0 0 24 24" style="flex-shrink:0"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </div>
          ${grazingBar(c.grazing)}
        </a>
      `;
    }).join('') + '</div>';
  });
}

if (isAdmin) {
  document.getElementById('btn-add-camp').addEventListener('click', () => {
    document.getElementById('camp-id').value    = '';
    document.getElementById('camp-name').value  = '';
    document.getElementById('camp-size').value  = '';
    document.getElementById('camp-ratio').value = '';
    document.getElementById('camp-notes').value = '';
    document.getElementById('camp-modal-title').textContent = '<?= t('add_camp') ?>';
    if (FARM_ID) document.getElementById('camp-farm').value = FARM_ID;
    populateHerdDropdown(FARM_ID);
    document.getElementById('camp-herd').value = '';
    openModal('camp-modal');
  });

  document.getElementById('camp-farm')?.addEventListener('change', function() {
    populateHerdDropdown(this.value);
  });
}

function editCamp(e, c) {
  e.stopPropagation();
  document.getElementById('camp-id').value    = c.id;
  document.getElementById('camp-name').value  = c.name;
  document.getElementById('camp-size').value  = c.size_ha || '';
  document.getElementById('camp-ratio').value = c.stocking_ratio || '';
  document.getElementById('camp-notes').value = c.notes || '';
  document.getElementById('camp-modal-title').textContent = '<?= t('edit_camp') ?>';
  const farmId = c.farm_id || FARM_ID;
  if (document.getElementById('camp-farm')) document.getElementById('camp-farm').value = farmId;
  populateHerdDropdown(farmId);
  const herd = allHerds.find(h => h.camp_id == c.id);
  document.getElementById('camp-herd').value = herd ? herd.id : '';
  openModal('camp-modal');
}

async function saveCamp() {
  const id     = document.getElementById('camp-id').value;
  const name   = document.getElementById('camp-name').value.trim();
  const farmId = document.getElementById('camp-farm')?.value || FARM_ID;
  const herdId = document.getElementById('camp-herd').value;

  if (!name)   { alert('Camp name is required.'); return; }
  if (!farmId) { alert('Please select a farm.'); return; }

  const body = {
    name,
    farm_id:         farmId,
    size_ha:         document.getElementById('camp-size').value || null,
    stocking_ratio:  document.getElementById('camp-ratio').value || null,
    notes:           document.getElementById('camp-notes').value.trim(),
  };

  const method = id ? 'PUT' : 'POST';
  const url    = id ? `/api/camps.php?id=${id}` : '/api/camps.php';
  const res    = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) }).then(r=>r.json());

  if (!res.success) { alert(res.message || 'Error saving camp.'); return; }

  const campId = id || res.data?.id;

  if (herdId && campId) {
    const herd = allHerds.find(h => h.id == herdId);
    if (herd) {
      await fetch(`/api/herds.php?id=${herdId}`, {
        method: 'PUT',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ ...herd, camp_id: campId, farm_id: farmId }),
      });
    }
  }

  if (!herdId && id) {
    const prev = allHerds.find(h => h.camp_id == id);
    if (prev) {
      await fetch(`/api/herds.php?id=${prev.id}`, {
        method: 'PUT',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ ...prev, camp_id: null }),
      });
    }
  }

  closeModal('camp-modal');
  const hr = await fetch('/api/herds.php').then(r=>r.json());
  allHerds = hr.data || [];
  loadCamps();
}

function deleteCamp(e, id, name) {
  e.stopPropagation();
  if (!confirm(`Delete camp "${name}"?\n\nAny herd assigned to this camp will be unassigned.`)) return;
  fetch(`/api/camps.php?id=${id}`, { method: 'DELETE' })
    .then(r=>r.json())
    .then(res => {
      if (res.success) { showToast('Camp deleted'); loadCamps(); }
      else alert(res.message || 'Error deleting camp.');
    });
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

loadCamps();
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
