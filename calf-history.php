<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
requireRole('super_admin');
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'add_calf_history';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-timeline"></i> <?= t('calf_history') ?></h1>
  <a href="/quick-actions.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> <?= t('back') ?></a>
</div>

<div style="padding:16px">

  <!-- Cow Search -->
  <div class="form-group">
    <label class="form-label"><?= t('search_breeding_cow') ?> <span class="required">*</span></label>
    <input type="text" id="cow-search" class="form-control" placeholder="<?= t('type_ear_tag_ph') ?>" oninput="searchCow()" autocomplete="off">
    <div id="cow-results" style="margin-top:6px"></div>
  </div>

  <!-- Selected Cow Card -->
  <div id="cow-card" style="display:none">
    <div class="card" style="margin-bottom:16px">
      <div class="card-body">
        <div class="flex-between">
          <div>
            <div id="cow-tag" style="font-size:1.2rem;font-weight:700"></div>
            <div id="cow-meta" class="text-muted text-sm"></div>
          </div>
          <button class="btn btn-secondary btn-sm" onclick="clearCow()"><?= t('change') ?></button>
        </div>

        <!-- Existing calves -->
        <div id="existing-calves" style="margin-top:12px"></div>

        <!-- Add calf button -->
        <button class="btn btn-primary btn-full mt-12" onclick="openAddCalf()">+ <?= t('add_prev_calf') ?></button>
      </div>
    </div>
  </div>

  <!-- Add Calf Form (hidden until button clicked) -->
  <div id="add-calf-form" style="display:none">
    <div class="card">
      <div class="card-body">
        <h3 style="margin:0 0 16px;font-size:1rem"><?= t('new_prev_calf') ?></h3>

        <div class="form-group">
          <label class="form-label"><?= t('ear_tag') ?> <span class="required">*</span></label>
          <input type="text" id="c-tag" class="form-control" placeholder="e.g. BV-042">
        </div>

        <div class="form-group">
          <label class="form-label"><?= t('dob') ?> <span class="required">*</span></label>
          <input type="date" id="c-dob" class="form-control">
        </div>

        <div class="form-group">
          <label class="form-label"><?= t('sex') ?> <span class="required">*</span></label>
          <div style="display:flex;gap:8px;margin-top:4px">
            <button type="button" id="sex-bull" class="btn btn-secondary" onclick="setSex('male')">♂ <?= t('cat_bull_calf') ?></button>
            <button type="button" id="sex-heifer" class="btn btn-secondary" onclick="setSex('female')">♀ <?= t('cat_heifer_calf') ?></button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label"><?= t('breed') ?></label>
          <input type="text" id="c-breed" class="form-control" placeholder="<?= t('none') ?>">
        </div>

        <div class="form-group">
          <label class="form-label"><?= t('status') ?> <span class="required">*</span></label>
          <div style="display:flex;gap:8px;margin-top:4px;flex-wrap:wrap">
            <button type="button" id="st-active" class="btn btn-primary" onclick="setStatus('active')"><?= t('as_active') ?></button>
            <button type="button" id="st-sold"   class="btn btn-secondary" onclick="setStatus('sold')"><?= t('as_sold') ?></button>
            <button type="button" id="st-dead"   class="btn btn-secondary" onclick="setStatus('dead')"><?= t('as_dead') ?></button>
          </div>
        </div>

        <div style="display:flex;gap:8px;margin-top:8px">
          <button class="btn btn-secondary" style="flex:1" onclick="cancelAddCalf()"><?= t('cancel') ?></button>
          <button class="btn btn-primary" style="flex:1" id="save-calf-btn" onclick="saveCalf()"><?= t('save_calf') ?></button>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
const T = <?= json_encode([
  'no_cows_found'    => t('no_cows_found'),
  'no_calves_yet'    => t('no_calves_yet'),
  'recorded_calves'  => t('recorded_calves'),
  'saving'           => t('saving'),
  'save_calf'        => t('save_calf'),
  'req_ear_tag'      => t('req_ear_tag'),
  'req_dob'          => t('req_dob'),
  'req_sex'          => t('req_sex'),
  'error_saving'     => t('error_saving'),
]) ?>;

let selectedCow    = null;
let selectedSex    = null;
let selectedStatus = 'active';
let searchTimer    = null;

function searchCow() {
  clearTimeout(searchTimer);
  const q = document.getElementById('cow-search').value.trim();
  if (!q) { document.getElementById('cow-results').innerHTML = ''; return; }
  searchTimer = setTimeout(() => {
    fetch(`/api/animals.php?q=${encodeURIComponent(q)}&status=active`)
      .then(r => r.json())
      .then(res => {
        const cows = (res.data || []).filter(a => a.category === 'breeding_cow' || a.category === 'c_grade_cow');
        const el = document.getElementById('cow-results');
        if (!cows.length) { el.innerHTML = `<p class="text-muted text-sm">${T.no_cows_found}</p>`; return; }
        el.innerHTML = '<div class="list-card">' + cows.slice(0, 8).map(a =>
          `<button class="list-item" style="width:100%;text-align:left;background:none;border:none;cursor:pointer"
            onclick="selectCow(${a.id}, '${escJs(a.ear_tag)}', '${escJs(a.herd_name||'')}', '${escJs(a.farm_name||'')}', '${escJs(a.breed||'')}')">
            <div class="item-body">
              <div class="item-title">${escHtml(a.ear_tag)}</div>
              <div class="item-sub">${escHtml(a.herd_name || a.farm_name || a.category)}</div>
            </div>
            <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </button>`
        ).join('') + '</div>';
      });
  }, 280);
}

function selectCow(id, tag, herd, farm, breed) {
  selectedCow = { id, tag, herd, farm, breed };
  document.getElementById('cow-search').value = tag;
  document.getElementById('cow-results').innerHTML = '';
  document.getElementById('cow-tag').textContent = tag;
  document.getElementById('cow-meta').textContent = [herd, farm, breed].filter(Boolean).join(' · ');
  document.getElementById('cow-card').style.display = 'block';
  document.getElementById('add-calf-form').style.display = 'none';
  loadExistingCalves(id);
}

function clearCow() {
  selectedCow = null;
  document.getElementById('cow-search').value = '';
  document.getElementById('cow-card').style.display = 'none';
  document.getElementById('add-calf-form').style.display = 'none';
  document.getElementById('existing-calves').innerHTML = '';
}

function loadExistingCalves(cowId) {
  fetch(`/api/calving.php?dam_id=${cowId}`)
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('existing-calves');
      if (!res.data?.length) { el.innerHTML = `<p class="text-muted text-sm" style="margin:0">${T.no_calves_yet}</p>`; return; }
      const ordinals = ['1st','2nd','3rd','4th','5th','6th','7th','8th','9th','10th'];
      el.innerHTML = `<p class="text-xs text-muted" style="margin:0 0 6px">${T.recorded_calves}</p><div class="list-card">`
        + res.data.map((c, i) => {
            const sex = c.calf_sex === 'male' ? '♂' : c.calf_sex === 'female' ? '♀' : '';
            const link = c.calf_id
              ? `<a href="/animal-detail.php?id=${c.calf_id}" style="font-weight:600">${escHtml(c.calf_tag||'Unknown')}</a>`
              : escHtml(c.calf_tag || 'Unknown');
            return `<div class="list-item">
              <div class="item-icon" style="font-size:0.9rem;font-weight:700;color:var(--green);min-width:36px;text-align:center">${ordinals[i]||i+1+'th'}</div>
              <div class="item-body">
                <div class="item-title">${link} ${sex}</div>
                <div class="item-sub">${formatDate(c.calving_date)}</div>
              </div>
            </div>`;
          }).join('')
        + '</div>';
    });
}

function openAddCalf() {
  document.getElementById('add-calf-form').style.display = 'block';
  document.getElementById('c-tag').value   = '';
  document.getElementById('c-dob').value   = '';
  document.getElementById('c-breed').value = '';
  setSex(null);
  setStatus('active');
  document.getElementById('c-tag').focus();
  document.getElementById('add-calf-form').scrollIntoView({behavior:'smooth'});
}

function cancelAddCalf() {
  document.getElementById('add-calf-form').style.display = 'none';
}

function setSex(val) {
  selectedSex = val;
  document.getElementById('sex-bull').className   = 'btn ' + (val === 'male'   ? 'btn-primary' : 'btn-secondary');
  document.getElementById('sex-heifer').className = 'btn ' + (val === 'female' ? 'btn-primary' : 'btn-secondary');
}

function setStatus(val) {
  selectedStatus = val;
  document.getElementById('st-active').className = 'btn ' + (val === 'active' ? 'btn-primary' : 'btn-secondary');
  document.getElementById('st-sold').className   = 'btn ' + (val === 'sold'   ? 'btn-primary' : 'btn-secondary');
  document.getElementById('st-dead').className   = 'btn ' + (val === 'dead'   ? 'btn-primary' : 'btn-secondary');
}

function saveCalf() {
  const tag   = document.getElementById('c-tag').value.trim();
  const dob   = document.getElementById('c-dob').value;
  const breed = document.getElementById('c-breed').value.trim();

  if (!tag)         { alert(T.req_ear_tag); return; }
  if (!dob)         { alert(T.req_dob);     return; }
  if (!selectedSex) { alert(T.req_sex);     return; }

  const btn = document.getElementById('save-calf-btn');
  btn.disabled = true;
  btn.textContent = T.saving;

  const category = selectedSex === 'male' ? 'bull_calf' : 'heifer_calf';

  fetch('/api/animals.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      ear_tag:   tag,
      dob:       dob,
      sex:       selectedSex,
      category:  category,
      breed:     breed || null,
      farm_id:   null,
      herd_id:   null,
      mother_id: selectedCow.id,
      animal_status: selectedStatus,
      breeding_status: 'open',
    })
  })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      btn.textContent = T.save_calf;
      if (res.success) {
        document.getElementById('add-calf-form').style.display = 'none';
        loadExistingCalves(selectedCow.id);
      } else {
        alert(res.message || T.error_saving);
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.textContent = T.save_calf;
      alert(T.error_saving + ' ' + err.message);
    });
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }
function escJs(s)   { return String(s||'').replace(/'/g, "\\'"); }
function formatDate(s) { if (!s) return ''; return new Date(s+'T00:00:00').toLocaleDateString(); }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
