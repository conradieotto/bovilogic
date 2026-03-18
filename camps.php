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

$pageTitle = 'camps';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/farms.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= $farm ? htmlspecialchars($farm['name']) : t('camps') ?></h1>
  <?php if (isSuperAdmin()): ?>
  <button class="btn-icon" id="btn-add-camp">
    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
  </button>
  <?php endif; ?>
</header>

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
<div class="modal-overlay" id="camp-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title" id="camp-modal-title"><?= t('add_camp') ?></div>
    <div class="modal-body">
      <input type="hidden" id="camp-id" value="">
      <div class="form-group"><label class="form-label">Camp Name <span class="required">*</span></label><input type="text" id="camp-name" class="form-control"></div>
      <div class="form-group"><label class="form-label">Size (ha)</label><input type="number" id="camp-size" class="form-control" step="0.1" min="0"></div>
      <div class="form-group"><label class="form-label">Notes</label><textarea id="camp-notes" class="form-control"></textarea></div>
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

function loadCamps() {
  const url = FARM_ID ? `/api/camps.php?farm_id=${FARM_ID}` : '/api/camps.php';
  fetch(url).then(r=>r.json()).then(res=>{
    const el = document.getElementById('camps-list');
    if (!res.data?.length) {
      el.innerHTML='<div class="empty-state"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/></svg><h3>No camps</h3><p>Add camps to track grazing.</p></div>';
      return;
    }
    el.innerHTML='<div class="list-card">'+res.data.map(c=>`
      <div class="list-item">
        <div class="item-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="2"/></svg></div>
        <div class="item-body">
          <div class="item-title">${escHtml(c.name)}</div>
          <div class="item-sub">${escHtml(c.farm_name||'')} ${c.size_ha ? '· '+c.size_ha+'ha' : ''}</div>
        </div>
        ${isAdmin?`<button class="btn btn-sm btn-secondary" onclick="editCamp(event,${JSON.stringify(c).replace(/"/g,'&quot;')})">Edit</button>`:''}
      </div>
    `).join('')+'</div>';
  });
}

if (isAdmin) {
  document.getElementById('btn-add-camp').addEventListener('click', () => {
    document.getElementById('camp-id').value='';
    document.getElementById('camp-name').value='';
    document.getElementById('camp-size').value='';
    document.getElementById('camp-notes').value='';
    document.getElementById('camp-modal-title').textContent='<?= t('add_camp') ?>';
    openModal('camp-modal');
  });
}

function editCamp(e, c) {
  e.stopPropagation();
  document.getElementById('camp-id').value  = c.id;
  document.getElementById('camp-name').value= c.name;
  document.getElementById('camp-size').value= c.size_ha||'';
  document.getElementById('camp-notes').value=c.notes||'';
  document.getElementById('camp-modal-title').textContent='<?= t('edit_camp') ?>';
  openModal('camp-modal');
}

function saveCamp() {
  const id   = document.getElementById('camp-id').value;
  const name = document.getElementById('camp-name').value.trim();
  if (!name) { alert('Camp name required.'); return; }
  const body = { name, farm_id: FARM_ID||undefined, size_ha: document.getElementById('camp-size').value||null, notes: document.getElementById('camp-notes').value };
  const method = id?'PUT':'POST';
  const url    = id?`/api/camps.php?id=${id}`:'/api/camps.php';
  fetch(url,{method,headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
    .then(r=>r.json()).then(res=>{
      if(res.success){closeModal('camp-modal');loadCamps();}
      else alert(res.message||'Error.');
    });
}

function escHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
loadCamps();
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
