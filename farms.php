<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'nav_farms';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="<?= ($_GET['from'] ?? '') === 'quick' ? '/quick-actions.php' : '/index.php' ?>" class="btn-icon" aria-label="Back">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('farms') ?></h1>
  <?php if (isSuperAdmin()): ?>
  <button class="btn-icon" id="btn-add-farm" aria-label="Add farm">
    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
  </button>
  <?php endif; ?>
</header>

<div id="farms-list"><div class="page-loader"><div class="spinner"></div></div></div>

<?php if (isSuperAdmin()): ?>
<!-- Add/Edit Farm Modal -->
<div class="modal-overlay" id="farm-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title" id="farm-modal-title"><?= t('add_farm') ?></div>
    <div class="modal-body">
      <form id="farm-form">
        <input type="hidden" id="farm-id" name="id" value="">
        <div class="form-group">
          <label class="form-label" for="farm-name"><?= t('farm_name') ?> <span class="required">*</span></label>
          <input type="text" id="farm-name" name="name" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="farm-location"><?= t('location') ?></label>
          <input type="text" id="farm-location" name="location" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label" for="farm-size">Size (ha)</label>
          <input type="number" id="farm-size" name="size_ha" class="form-control" step="0.1" min="0" placeholder="e.g. 500">
        </div>
        <div class="form-group">
          <label class="form-label" for="farm-notes"><?= t('notes') ?></label>
          <textarea id="farm-notes" name="notes" class="form-control"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('farm-modal')"><?= t('cancel') ?></button>
      <button class="btn btn-primary" id="farm-save-btn"><?= t('save') ?></button>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const isAdmin = <?= isSuperAdmin() ? 'true' : 'false' ?>;
let farmsCache = [];

function renderFarms(farms) {
  farmsCache = farms;
  const el = document.getElementById('farms-list');
  if (!farms.length) {
    el.innerHTML = `<div class="empty-state">
      <svg viewBox="0 0 24 24"><path d="M19 9.3V4h-3v2.6L12 3 2 12h3v8h5v-5h4v5h5v-8h3l-3-2.7z"/></svg>
      <h3>No farms yet</h3>
      <p>Add your first farm to get started.</p>
    </div>`;
    return;
  }
  el.innerHTML = '<div class="list-card">' + farms.map(f => {
    const adminBtns = isAdmin
      ? `<div style="display:flex;gap:6px">
           <button class="btn btn-sm btn-secondary" onclick="editFarm(event,${f.id})">Edit</button>
           <button class="btn btn-sm btn-danger" onclick="deleteFarm(event,${f.id})">Delete</button>
         </div>`
      : '';
    const ha  = f.size_ha ? (parseFloat(f.size_ha) + ' ha') : '– ha';
    const sub = [f.location, ha, (f.animal_count ?? 0) + ' animals']
      .filter(Boolean).join(' · ');
    return `
    <a href="/camps.php?farm=${f.id}" class="list-item">
      <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M19 9.3V4h-3v2.6L12 3 2 12h3v8h5v-5h4v5h5v-8h3l-3-2.7z"/></svg></div>
      <div class="item-body">
        <div class="item-title">${escHtml(f.name)}</div>
        <div class="item-sub">${sub}</div>
      </div>
      <div class="item-end">${adminBtns}</div>
      <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    </a>`;
  }).join('') + '</div>';
}

function loadFarms() {
  fetch('/api/farms.php')
    .then(r => r.json())
    .then(res => res.success ? renderFarms(res.data) : showError())
    .catch(() => renderFarms([]));
}

loadFarms();

if (isAdmin) {
  document.getElementById('btn-add-farm').addEventListener('click', () => {
    document.getElementById('farm-id').value = '';
    document.getElementById('farm-name').value = '';
    document.getElementById('farm-location').value = '';
    document.getElementById('farm-size').value = '';
    document.getElementById('farm-notes').value = '';
    document.getElementById('farm-modal-title').textContent = '<?= t('add_farm') ?>';
    openModal('farm-modal');
  });

  document.getElementById('farm-save-btn').addEventListener('click', saveFarm);
}

function editFarm(e, id) {
  e.preventDefault();
  e.stopPropagation();
  const farm = farmsCache.find(f => f.id == id);
  if (!farm) return;
  document.getElementById('farm-id').value = farm.id;
  document.getElementById('farm-name').value = farm.name;
  document.getElementById('farm-location').value = farm.location || '';
  document.getElementById('farm-size').value = farm.size_ha || '';
  document.getElementById('farm-notes').value = farm.notes || '';
  document.getElementById('farm-modal-title').textContent = '<?= t('edit_farm') ?>';
  openModal('farm-modal');
}

function saveFarm() {
  const id    = document.getElementById('farm-id').value;
  const name  = document.getElementById('farm-name').value.trim();
  if (!name) { alert('Farm name is required.'); return; }
  const body  = {
    name,
    location: document.getElementById('farm-location').value.trim(),
    size_ha:  document.getElementById('farm-size').value || null,
    notes:    document.getElementById('farm-notes').value.trim(),
  };
  const method = id ? 'PUT' : 'POST';
  const url    = id ? `/api/farms.php?id=${id}` : '/api/farms.php';
  fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) })
    .then(r => r.text())
    .then(text => {
      let res;
      try { res = JSON.parse(text); } catch(e) { alert('Server error: ' + text.substring(0,200)); return; }
      if (res.success) { closeModal('farm-modal'); loadFarms(); }
      else alert(res.message || 'Error saving farm.');
    })
    .catch(e => alert('Network error: ' + e.message));
}

function deleteFarm(e, id) {
  e.preventDefault();
  e.stopPropagation();
  const farm = farmsCache.find(f => f.id == id);
  const name = farm ? farm.name : 'this farm';
  if (!confirm('Delete farm "' + name + '"?\n\nThis will not delete animals, but the farm will be removed.')) return;
  fetch('/api/farms.php?id=' + id, { method: 'DELETE' })
    .then(r => r.json())
    .then(res => {
      if (res.success) { showToast('Farm deleted'); loadFarms(); }
      else alert(res.message || 'Error deleting farm.');
    });
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function showError() { document.getElementById('farms-list').innerHTML = '<div class="empty-state"><p>Failed to load farms.</p></div>'; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
