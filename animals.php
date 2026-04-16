<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'nav_animals';
$searchQ   = htmlspecialchars($_GET['q'] ?? '');
$farmId    = (int)($_GET['farm_id'] ?? 0);
$herdId    = (int)($_GET['herd_id'] ?? 0);
$farmName  = '';
$herdName  = '';
if ($farmId || $herdId) {
    require_once __DIR__ . '/lib/db.php';
    if ($farmId) {
        $farmRow  = DB::row('SELECT name FROM farms WHERE id = ?', [$farmId]);
        $farmName = $farmRow['name'] ?? '';
    }
    if ($herdId) {
        $herdRow  = DB::row('SELECT name FROM herds WHERE id = ?', [$herdId]);
        $herdName = $herdRow['name'] ?? '';
    }
}
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-cow"></i> <?= $herdName ? htmlspecialchars($herdName) : ($farmName ? htmlspecialchars($farmName) . ' – ' . t('animals') : t('animals')) ?></h1>
  <?php if (isSuperAdmin()): ?>
  <a href="/animal-form.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> <?= t('add_animal') ?></a>
  <?php endif; ?>
</div>

<!-- Search -->
<div class="search-bar">
  <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
  <input type="search" id="animal-search" value="<?= $searchQ ?>" placeholder="<?= t('ear_tag') ?> / RFID..." autocomplete="off">
</div>

<!-- Status Filters -->
<div class="filter-row">
  <button class="filter-chip active" data-status=""><?= t('all') ?></button>
  <button class="filter-chip" data-status="active"><?= t('as_active') ?></button>
  <button class="filter-chip" data-status="sold"><?= t('as_sold') ?></button>
  <button class="filter-chip" data-status="dead"><?= t('as_dead') ?></button>
</div>

<!-- Category Filters -->
<div class="filter-row" id="cat-filters">
  <button class="filter-chip active" data-cat=""><?= t('all_categories') ?></button>
  <button class="filter-chip" data-cat="breeding_bull"><?= t('cat_breeding_bull') ?></button>
  <button class="filter-chip" data-cat="breeding_cow"><?= t('cat_breeding_cow') ?></button>
  <button class="filter-chip" data-cat="c_grade_cow"><?= t('cat_c_grade_cow') ?></button>
  <button class="filter-chip" data-cat="bull_calf"><?= t('cat_bull_calf') ?></button>
  <button class="filter-chip" data-cat="heifer_calf"><?= t('cat_heifer_calf') ?></button>
  <button class="filter-chip" data-cat="weaner"><?= t('cat_weaner') ?></button>
  <button class="filter-chip" data-cat="replacement_heifer"><?= t('cat_replacement_heifer') ?></button>
</div>

<div id="animals-list"><div class="page-loader"><div class="spinner"></div></div></div>

<?php if (isSuperAdmin()): ?>
<a href="/animal-form.php" class="fab" aria-label="Add animal">
  <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
</a>
<?php endif; ?>

<script>
const FARM_ID    = <?= $farmId ?>;
const HERD_ID    = <?= $herdId ?>;
let activeStatus = '';
let activeCat    = '';
let searchTimer  = null;

const T = <?= json_encode(['edit' => t('edit'), 'delete' => t('delete')]) ?>;

const categoryLabels = {
  breeding_bull:      '<?= t('cat_breeding_bull') ?>',
  breeding_cow:       '<?= t('cat_breeding_cow') ?>',
  c_grade_cow:        '<?= t('cat_c_grade_cow') ?>',
  bull_calf:          '<?= t('cat_bull_calf') ?>',
  heifer_calf:        '<?= t('cat_heifer_calf') ?>',
  weaner:             '<?= t('cat_weaner') ?>',
  replacement_heifer: '<?= t('cat_replacement_heifer') ?>',
};

const statusBadge = {
  active: 'badge-green',
  sold:   'badge-amber',
  dead:   'badge-red',
};

function loadAnimals() {
  const q = document.getElementById('animal-search').value.trim();
  let url = `/api/animals.php?`;
  if (FARM_ID)      url += `farm_id=${FARM_ID}&`;
  if (HERD_ID)      url += `herd_id=${HERD_ID}&`;
  if (q)            url += `q=${encodeURIComponent(q)}&`;
  if (activeStatus) url += `status=${activeStatus}&`;
  if (activeCat)    url += `category=${activeCat}&`;

  document.getElementById('animals-list').innerHTML = '<div class="page-loader"><div class="spinner"></div></div>';

  fetch(url)
    .then(r => r.json())
    .then(res => {
      if (!res.success) { renderAnimals([]); return; }
      renderAnimals(res.data);
    })
    .catch(() => renderAnimals([]));
}

function renderAnimals(animals) {
  const el = document.getElementById('animals-list');
  if (!animals.length) {
    el.innerHTML = `<div class="empty-state">
      <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
      <h3>No animals found</h3>
      <p>Try adjusting your filters or search.</p>
    </div>`;
    return;
  }
  const admin = <?= isSuperAdmin() ? 'true' : 'false' ?>;
  el.innerHTML = '<div class="list-card">' + animals.map(a => `
    <div class="list-item" style="cursor:default">
      <a href="/animal-detail.php?id=${a.id}" style="display:flex;align-items:center;gap:12px;flex:1;text-decoration:none;color:inherit;min-width:0">
        <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg></div>
        <div class="item-body">
          <div class="item-title">${escHtml(a.ear_tag)}</div>
          <div class="item-sub">${escHtml(categoryLabels[a.category] || a.category)} &middot; ${escHtml(a.breed || '')} &middot; ${escHtml(a.herd_name || '')}</div>
        </div>
        <div class="item-end">
          <span class="badge ${statusBadge[a.animal_status] || 'badge-grey'}">${escHtml(a.animal_status)}</span>
          ${a.last_weight_kg ? `<span class="text-xs text-muted">${a.last_weight_kg}kg</span>` : ''}
        </div>
      </a>
      ${admin ? `
      <div class="list-actions">
        <a href="/animal-form.php?id=${a.id}" class="btn btn-sm btn-secondary">${T.edit}</a>
        <button class="btn btn-sm btn-danger" onclick="deleteAnimal(${a.id},'${escHtml(a.ear_tag)}')">${T.delete}</button>
      </div>` : ''}
    </div>
  `).join('') + '</div>';
}

// Filter chips
document.querySelectorAll('[data-status]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('[data-status]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeStatus = btn.dataset.status;
    loadAnimals();
  });
});
document.querySelectorAll('[data-cat]').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('[data-cat]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeCat = btn.dataset.cat;
    loadAnimals();
  });
});

// Search with debounce
document.getElementById('animal-search').addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(loadAnimals, 300);
});

function deleteAnimal(id, tag) {
  if (!confirm(`Delete animal "${tag}"?\n\nThis will permanently remove the animal and all its records.`)) return;
  fetch(`/api/animals.php?id=${id}`, { method: 'DELETE' })
    .then(r => r.json())
    .then(res => {
      if (res.success) { showToast('Animal deleted'); loadAnimals(); }
      else alert(res.message || 'Error deleting animal.');
    });
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

loadAnimals();
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
