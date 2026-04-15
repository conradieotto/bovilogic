<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
requireRole('super_admin');
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'add_purchase';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/quick-actions.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1>Add Purchase</h1>
</header>

<div style="padding:16px;">

  <div class="form-group">
    <label class="form-label">Date Purchased <span class="required">*</span></label>
    <input type="date" id="p-date" class="form-control" value="<?= date('Y-m-d') ?>">
  </div>

  <div class="form-group">
    <label class="form-label">Purchase Price (ZAR) <span class="required">*</span></label>
    <div style="position:relative">
      <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-weight:600">R</span>
      <input type="number" id="p-price" class="form-control" step="0.01" min="0" placeholder="0.00" style="padding-left:28px">
    </div>
  </div>

  <div class="form-group">
    <label class="form-label">Seller <span class="required">*</span></label>
    <input type="text" id="p-seller" class="form-control" placeholder="Seller name or business">
  </div>

  <div class="form-group">
    <label class="form-label">Animal Category <span class="required">*</span></label>
    <div id="cat-list" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px"></div>
  </div>

  <div class="form-group">
    <label class="form-label">Total Purchased <span class="required">*</span></label>
    <input type="number" id="p-total" class="form-control" min="1" placeholder="e.g. 10">
  </div>

  <div class="form-group">
    <label class="form-label">New Ear Tag Numbers</label>
    <input type="text" id="tag-search" class="form-control" placeholder="Search ear tag to add..." oninput="searchTags()">
    <div id="tag-results" style="margin-top:6px"></div>
    <div id="tag-list" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px"></div>
  </div>

  <button class="btn btn-primary btn-full btn-lg" id="save-btn" onclick="savePurchase()">Save Purchase</button>
  <div id="save-result" style="margin-top:16px"></div>

</div>

<script>
const CATEGORIES = [
  {val:'breeding_bull',      label:'Breeding Bull'},
  {val:'breeding_cow',       label:'Breeding Cow'},
  {val:'c_grade_cow',        label:'C-grade Cow'},
  {val:'bull_calf',          label:'Bull Calf'},
  {val:'heifer_calf',        label:'Heifer Calf'},
  {val:'weaner',             label:'Weaner'},
  {val:'replacement_heifer', label:'Replacement Heifer'},
];

let selectedCategory = null;
let selectedTags = [];
let tagTimer = null;

// Render category chips
document.getElementById('cat-list').innerHTML = CATEGORIES.map(c =>
  `<button type="button" class="btn btn-secondary btn-sm" id="cat-${c.val}" onclick="selectCat('${c.val}')">${c.label}</button>`
).join('');

function selectCat(val) {
  selectedCategory = val;
  CATEGORIES.forEach(c => {
    document.getElementById('cat-' + c.val).className =
      c.val === val ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm';
  });
}

function searchTags() {
  clearTimeout(tagTimer);
  const q = document.getElementById('tag-search').value.trim();
  if (!q) { document.getElementById('tag-results').innerHTML = ''; return; }
  tagTimer = setTimeout(() => {
    fetch(`/api/animals.php?q=${encodeURIComponent(q)}&status=active`)
      .then(r => r.json())
      .then(res => {
        const el = document.getElementById('tag-results');
        if (!res.data?.length) { el.innerHTML = '<p class="text-muted text-sm">No animals found.</p>'; return; }
        el.innerHTML = '<div class="list-card">' + res.data.slice(0, 8).map(a =>
          `<button class="list-item" data-id="${a.id}" data-tag="${escHtml(a.ear_tag)}" onclick="addTagFromBtn(this)"
            style="cursor:pointer;width:100%;text-align:left;background:none;border:none;">
            <div class="item-body"><div class="item-title">${escHtml(a.ear_tag)}</div></div>
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--green);flex-shrink:0"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
          </button>`
        ).join('') + '</div>';
      });
  }, 300);
}

function addTagFromBtn(btn) {
  const id  = parseInt(btn.dataset.id);
  const tag = btn.dataset.tag;
  if (selectedTags.find(t => t.id === id)) return;
  selectedTags.push({id, tag});
  document.getElementById('tag-search').value = '';
  document.getElementById('tag-results').innerHTML = '';
  renderTagList();
}

function removeTag(id) {
  selectedTags = selectedTags.filter(t => t.id !== id);
  renderTagList();
}

function renderTagList() {
  document.getElementById('tag-list').innerHTML = selectedTags.map(t =>
    `<span style="display:inline-flex;align-items:center;gap:4px;background:var(--surface-2,#f0f0f0);
      padding:4px 10px;border-radius:20px;font-size:13px;font-weight:600">
      ${escHtml(t.tag)}
      <button onclick="removeTag(${t.id})" style="background:none;border:none;cursor:pointer;padding:0 0 0 4px;font-size:16px;line-height:1;color:var(--text-muted)">×</button>
    </span>`
  ).join('');
}

async function savePurchase() {
  const date   = document.getElementById('p-date').value;
  const price  = document.getElementById('p-price').value;
  const seller = document.getElementById('p-seller').value.trim();
  const total  = document.getElementById('p-total').value;

  if (!date)             { alert('Please select a date.'); return; }
  if (!price)            { alert('Please enter a purchase price.'); return; }
  if (!seller)           { alert('Please enter a seller name.'); return; }
  if (!selectedCategory) { alert('Please select an animal category.'); return; }
  if (!total)            { alert('Please enter the total purchased.'); return; }

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  btn.textContent = 'Saving…';

  try {
    const res = await fetch('/api/purchases.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        date_purchased:  date,
        price_zar:       parseFloat(price),
        seller,
        category:        selectedCategory,
        total_purchased: parseInt(total),
        animal_ids:      selectedTags.map(t => t.id),
      })
    }).then(r => r.json());

    if (res.success) {
      document.querySelector('.btn-primary.btn-full').style.display = 'none';
      document.getElementById('save-result').innerHTML = `
        <div class="card" style="text-align:center;padding:24px">
          <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:var(--green);margin-bottom:12px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z"/></svg>
          <h3>Purchase saved!</h3>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap">
            <button class="btn btn-primary" onclick="location.reload()">Add Another</button>
            <a href="/quick-actions.php" class="btn btn-secondary">Quick Actions</a>
          </div>
        </div>`;
    } else {
      alert(res.message || 'Error saving purchase.');
      btn.disabled = false;
      btn.textContent = 'Save Purchase';
    }
  } catch(e) {
    alert('Error: ' + e.message);
    btn.disabled = false;
    btn.textContent = 'Save Purchase';
  }
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
