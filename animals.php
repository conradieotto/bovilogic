<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'nav_animals';
$searchQ   = htmlspecialchars($_GET['q'] ?? '');
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/index.php" class="btn-icon" aria-label="Back">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('animals') ?></h1>
  <?php if (isSuperAdmin()): ?>
  <a href="/animal-form.php" class="btn-icon" aria-label="Add animal">
    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
  </a>
  <?php endif; ?>
</header>

<!-- Search -->
<div class="search-bar">
  <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
  <input type="search" id="animal-search" value="<?= $searchQ ?>" placeholder="<?= t('ear_tag') ?> / RFID..." autocomplete="off">
</div>

<!-- Status Filters -->
<div class="filter-row">
  <button class="filter-chip active" data-status="">All</button>
  <button class="filter-chip" data-status="active"><?= t('as_active') ?></button>
  <button class="filter-chip" data-status="sold"><?= t('as_sold') ?></button>
  <button class="filter-chip" data-status="dead"><?= t('as_dead') ?></button>
</div>

<!-- Category Filters -->
<div class="filter-row" id="cat-filters">
  <button class="filter-chip active" data-cat="">All Categories</button>
  <button class="filter-chip" data-cat="breeding_bull"><?= t('cat_breeding_bull') ?></button>
  <button class="filter-chip" data-cat="cow"><?= t('cat_cow') ?></button>
  <button class="filter-chip" data-cat="calf"><?= t('cat_calf') ?></button>
  <button class="filter-chip" data-cat="heifer"><?= t('cat_heifer') ?></button>
  <button class="filter-chip" data-cat="open_heifer"><?= t('cat_open_heifer') ?></button>
  <button class="filter-chip" data-cat="weaner"><?= t('cat_weaner') ?></button>
  <button class="filter-chip" data-cat="steer"><?= t('cat_steer') ?></button>
  <button class="filter-chip" data-cat="ox"><?= t('cat_ox') ?></button>
</div>

<div id="animals-list"><div class="page-loader"><div class="spinner"></div></div></div>

<?php if (isSuperAdmin()): ?>
<a href="/animal-form.php" class="fab" aria-label="Add animal">
  <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
</a>
<?php endif; ?>

<script>
let activeStatus = '';
let activeCat    = '';
let searchTimer  = null;

const categoryLabels = {
  breeding_bull: '<?= t('cat_breeding_bull') ?>',
  cow:           '<?= t('cat_cow') ?>',
  calf:          '<?= t('cat_calf') ?>',
  open_heifer:   '<?= t('cat_open_heifer') ?>',
  heifer:        '<?= t('cat_heifer') ?>',
  weaner:        '<?= t('cat_weaner') ?>',
  steer:         '<?= t('cat_steer') ?>',
  ox:            '<?= t('cat_ox') ?>',
};

const statusBadge = {
  active: 'badge-green',
  sold:   'badge-amber',
  dead:   'badge-red',
};

function loadAnimals() {
  const q = document.getElementById('animal-search').value.trim();
  let url = `/api/animals.php?`;
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
  el.innerHTML = '<div class="list-card">' + animals.map(a => `
    <a href="/animal-detail.php?id=${a.id}" class="list-item">
      <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg></div>
      <div class="item-body">
        <div class="item-title">${escHtml(a.ear_tag)}</div>
        <div class="item-sub">${escHtml(categoryLabels[a.category] || a.category)} &middot; ${escHtml(a.breed || '')} &middot; ${escHtml(a.herd_name || '')}</div>
      </div>
      <div class="item-end">
        <span class="badge ${statusBadge[a.animal_status] || 'badge-grey'}">${escHtml(a.animal_status)}</span>
        ${a.last_weight_kg ? `<span class="text-xs text-muted">${a.last_weight_kg}kg</span>` : ''}
      </div>
      <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
    </a>
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

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

loadAnimals();
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
