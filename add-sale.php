<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
requireRole('super_admin');
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'add_sale';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-tag"></i> <?= t('add_sale') ?></h1>
  <a href="/quick-actions.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> <?= t('back') ?></a>
</div>

<div style="padding:16px;">

  <div class="form-group">
    <label class="form-label"><?= t('date_sold') ?> <span class="required">*</span></label>
    <input type="date" id="s-date" class="form-control" value="<?= date('Y-m-d') ?>">
  </div>

  <div class="form-group">
    <label class="form-label"><?= t('sold_price_zar') ?> <span class="required">*</span></label>
    <div style="position:relative">
      <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-weight:600">R</span>
      <input type="number" id="s-price" class="form-control" step="0.01" min="0" placeholder="0.00" style="padding-left:28px">
    </div>
  </div>

  <div class="form-group">
    <label class="form-label"><?= t('buyer') ?> <span class="required">*</span></label>
    <input type="text" id="s-buyer" class="form-control" placeholder="<?= t('buyer_ph') ?>">
  </div>

  <div class="form-group">
    <label class="form-label"><?= t('category') ?> <span class="required">*</span></label>
    <div id="cat-list" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px"></div>
  </div>

  <div class="form-group">
    <label class="form-label"><?= t('total_sold') ?> <span class="required">*</span></label>
    <input type="number" id="s-total" class="form-control" min="1" placeholder="e.g. 5">
  </div>

  <div class="form-group">
    <label class="form-label"><?= t('ear_tag_numbers') ?></label>
    <input type="text" id="tag-search" class="form-control" placeholder="<?= t('tag_search_ph') ?>" oninput="searchTags()">
    <div id="tag-results" style="margin-top:6px"></div>
    <div id="tag-list" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px"></div>
  </div>

  <button class="btn btn-primary btn-full btn-lg" id="save-btn" onclick="saveSale()"><?= t('save_sale') ?></button>
  <div id="save-result" style="margin-top:16px"></div>

</div>

<script>
const CATEGORIES = [
  {val:'breeding_bull',      label:<?= json_encode(t('cat_breeding_bull')) ?>},
  {val:'breeding_cow',       label:<?= json_encode(t('cat_breeding_cow')) ?>},
  {val:'c_grade_cow',        label:<?= json_encode(t('cat_c_grade_cow')) ?>},
  {val:'bull_calf',          label:<?= json_encode(t('cat_bull_calf')) ?>},
  {val:'heifer_calf',        label:<?= json_encode(t('cat_heifer_calf')) ?>},
  {val:'weaner',             label:<?= json_encode(t('cat_weaner')) ?>},
  {val:'replacement_heifer', label:<?= json_encode(t('cat_replacement_heifer')) ?>},
];

const T = <?= json_encode([
  'no_animals_found'  => t('no_animals_found'),
  'saving'            => t('saving'),
  'sale_saved'        => t('sale_saved'),
  'save_sale'         => t('save_sale'),
  'add_another'       => t('add_another'),
  'quick_actions'     => t('nav_quick_actions'),
  'req_select_date'   => t('req_select_date'),
  'req_sold_price'    => t('req_sold_price'),
  'req_buyer_name'    => t('req_buyer_name'),
  'req_category'      => t('req_category'),
  'req_total_sold'    => t('req_total_sold'),
  'error_saving'      => t('error_saving'),
]) ?>;

let selectedCategory = null;
let selectedTags = [];
let tagTimer = null;

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
        if (!res.data?.length) { el.innerHTML = `<p class="text-muted text-sm">${T.no_animals_found}</p>`; return; }
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

async function saveSale() {
  const date  = document.getElementById('s-date').value;
  const price = document.getElementById('s-price').value;
  const buyer = document.getElementById('s-buyer').value.trim();
  const total = document.getElementById('s-total').value;

  if (!date)             { alert(T.req_select_date); return; }
  if (!price)            { alert(T.req_sold_price);  return; }
  if (!buyer)            { alert(T.req_buyer_name);  return; }
  if (!selectedCategory) { alert(T.req_category);   return; }
  if (!total)            { alert(T.req_total_sold);  return; }

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  btn.textContent = T.saving;

  try {
    const res = await fetch('/api/sales.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        date_sold:    date,
        price_zar:    parseFloat(price),
        buyer,
        category:     selectedCategory,
        total_sold:   parseInt(total),
        animal_ids:   selectedTags.map(t => t.id),
      })
    }).then(r => r.json());

    if (res.success) {
      document.getElementById('save-btn').style.display = 'none';
      document.getElementById('save-result').innerHTML = `
        <div class="card" style="text-align:center;padding:24px">
          <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:var(--green);margin-bottom:12px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z"/></svg>
          <h3>${T.sale_saved}</h3>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap">
            <button class="btn btn-primary" onclick="location.reload()">${T.add_another}</button>
            <a href="/quick-actions.php" class="btn btn-secondary">${T.quick_actions}</a>
          </div>
        </div>`;
    } else {
      alert(res.message || T.error_saving);
      btn.disabled = false;
      btn.textContent = T.save_sale;
    }
  } catch(e) {
    alert(T.error_saving + ' ' + e.message);
    btn.disabled = false;
    btn.textContent = T.save_sale;
  }
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
