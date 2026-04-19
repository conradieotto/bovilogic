<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
requireRole('super_admin');
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'pregnancy_test';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-stethoscope"></i> <?= t('pregnancy_test') ?></h1>
  <a href="/quick-actions.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> <?= t('back') ?></a>
</div>

  <!-- Step 1: Choose herd -->
  <div id="step-herd">
    <p class="text-muted text-sm" style="margin-bottom:12px"><?= t('select_herd_to_test') ?></p>
    <div id="herd-loading" class="page-loader" style="min-height:120px"><div class="spinner"></div></div>
    <div id="herd-list" class="list-card" style="display:none"></div>
  </div>

  <!-- Step 2: Test cows -->
  <div id="step-test" style="display:none">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div>
        <div class="text-muted text-xs"><?= t('herd') ?></div>
        <strong id="chosen-herd-name"></strong>
      </div>
      <button class="btn btn-secondary btn-sm" onclick="showStep('step-herd')"><?= t('change') ?></button>
    </div>

    <!-- Running rate bar -->
    <div id="rate-bar" style="display:none;margin-bottom:16px;background:var(--surface-2,#f5f5f5);border-radius:12px;padding:14px 16px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <span class="text-sm text-muted"><?= t('pregnancy_rate') ?></span>
        <strong id="rate-pct" style="font-size:20px">–</strong>
      </div>
      <div style="background:#e0e0e0;border-radius:4px;height:8px;overflow:hidden">
        <div id="rate-fill" style="height:8px;border-radius:4px;background:var(--green);width:0%;transition:width .3s"></div>
      </div>
      <div id="rate-summary" class="text-xs text-muted" style="margin-top:6px"></div>
    </div>

    <div id="cow-list" class="list-card"></div>

    <div id="save-section" style="display:none;margin-top:16px">
      <button class="btn btn-primary btn-full btn-lg" id="save-btn" onclick="saveTest()"><?= t('save_pregnancy_test') ?></button>
    </div>
  </div>

  <div id="save-result" style="margin-top:16px"></div>

</div><!-- /.page-wrap -->

<script>
const T = <?= json_encode([
  'no_herds_found'       => t('no_herds_found'),
  'no_breeding_cows'     => t('no_breeding_cows'),
  'animals_count'        => t('animals_count'),
  'avg_interval_label'   => t('avg_interval_label'),
  'days'                 => t('days'),
  'bs_pregnant'          => t('bs_pregnant'),
  'not_pregnant_label'   => t('not_pregnant_label'),
  'months'               => t('months'),
  'not_pregnant_sum'     => t('not_pregnant_sum'),
  'not_yet_tested'       => t('not_yet_tested'),
  'saving'               => t('saving'),
  'save_pregnancy_test'  => t('save_pregnancy_test'),
  'test_saved'           => t('test_saved'),
  'cows_pregnant'        => t('cows_pregnant'),
  'pregnant_of'          => t('pregnant_of'),
  'add_another'          => t('add_another'),
  'quick_actions'        => t('nav_quick_actions'),
  'error_saving'         => t('error_saving'),
]) ?>;

let selectedHerd   = null;
let cowResults     = {}; // animal_id -> 'pregnant' | 'open'
let cowMonths      = {}; // animal_id -> months pregnant (number)
let cowList        = [];

// Load herds
fetch('/api/herds.php')
  .then(r => r.json())
  .then(res => {
    document.getElementById('herd-loading').style.display = 'none';
    const el = document.getElementById('herd-list');
    el.style.display = 'block';
    if (!res.data?.length) {
      el.innerHTML = `<div class="p-16 text-muted text-sm" style="padding:16px">${T.no_herds_found}</div>`;
      return;
    }
    el.innerHTML = res.data.map(h =>
      `<button class="list-item" data-id="${h.id}" data-name="${escHtml(h.name)}"
        onclick="selectHerd(this)" style="cursor:pointer;width:100%;text-align:left;background:none;border:none;">
        <div class="item-body">
          <div class="item-title">${escHtml(h.name)}</div>
          <div class="item-sub">${escHtml(h.farm_name || '')}${h.camp_name ? ' · ' + escHtml(h.camp_name) : ''} · ${h.animal_count} ${T.animals_count}</div>
        </div>
        <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
      </button>`
    ).join('');
  });

function selectHerd(btn) {
  selectedHerd = {id: parseInt(btn.dataset.id), name: btn.dataset.name};
  cowResults = {};
  document.getElementById('chosen-herd-name').textContent = selectedHerd.name;
  loadCows(selectedHerd.id);
  showStep('step-test');
}

function loadCows(herdId) {
  const el = document.getElementById('cow-list');
  el.innerHTML = '<div class="page-loader" style="min-height:80px"><div class="spinner"></div></div>';
  document.getElementById('rate-bar').style.display = 'none';
  document.getElementById('save-section').style.display = 'none';

  fetch(`/api/animals.php?herd_id=${herdId}&category=breeding_cow&status=active`)
    .then(r => r.json())
    .then(res => {
      cowList = res.data || [];
      if (!cowList.length) {
        el.innerHTML = `<div class="p-16 text-muted text-sm" style="padding:16px">${T.no_breeding_cows}</div>`;
        return;
      }
      renderCowList();
    });
}

function renderCowList() {
  const el = document.getElementById('cow-list');
  el.innerHTML = cowList.map(a => {
    const result = cowResults[a.id];
    return `
      <div class="list-item" id="cow-row-${a.id}" style="flex-wrap:wrap;gap:8px;padding:12px 16px">
        <div class="item-body">
          <div class="item-title">${escHtml(a.ear_tag)}</div>
          ${a.avg_calf_interval ? `<div class="item-sub">${T.avg_interval_label}: ${Math.round(a.avg_calf_interval)} ${T.days}</div>` : ''}
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0;align-items:center;flex-wrap:wrap">
          <button id="btn-preg-${a.id}" onclick="setResult(${a.id},'pregnant')"
            class="btn btn-sm ${result === 'pregnant' ? 'btn-primary' : 'btn-secondary'}"
            style="${result === 'pregnant' ? 'background:var(--green)' : ''}">
            ${T.bs_pregnant}
          </button>
          <div id="months-wrap-${a.id}" style="display:${result === 'pregnant' ? 'flex' : 'none'};align-items:center;gap:4px">
            <input type="number" id="months-${a.id}" min="1" max="9"
              value="${cowMonths[a.id] || ''}"
              placeholder="?" oninput="setMonths(${a.id},this.value)"
              style="width:48px;padding:4px 6px;border:1px solid var(--border);border-radius:6px;font-size:13px;text-align:center">
            <span class="text-xs text-muted">${T.months}</span>
          </div>
          <button id="btn-open-${a.id}" onclick="setResult(${a.id},'open')"
            class="btn btn-sm ${result === 'open' ? 'btn-danger' : 'btn-secondary'}">
            ${T.not_pregnant_label}
          </button>
        </div>
      </div>`;
  }).join('');
}

function setResult(animalId, status) {
  cowResults[animalId] = status;

  const bPreg = document.getElementById('btn-preg-' + animalId);
  const bOpen = document.getElementById('btn-open-' + animalId);
  bPreg.className = 'btn btn-sm ' + (status === 'pregnant' ? 'btn-primary' : 'btn-secondary');
  bPreg.style.background = status === 'pregnant' ? 'var(--green)' : '';
  bOpen.className = 'btn btn-sm ' + (status === 'open' ? 'btn-danger' : 'btn-secondary');

  const wrap = document.getElementById('months-wrap-' + animalId);
  if (wrap) wrap.style.display = status === 'pregnant' ? 'flex' : 'none';
  if (status !== 'pregnant') delete cowMonths[animalId];

  updateRate();
}

function setMonths(animalId, val) {
  const m = parseInt(val);
  if (m >= 1 && m <= 9) cowMonths[animalId] = m;
  else delete cowMonths[animalId];
}

function updateRate() {
  const tested   = Object.keys(cowResults).length;
  const pregnant = Object.values(cowResults).filter(v => v === 'pregnant').length;
  const total    = cowList.length;
  const rate     = tested > 0 ? Math.round((pregnant / tested) * 100) : 0;

  document.getElementById('rate-bar').style.display = 'block';
  document.getElementById('rate-pct').textContent   = tested > 0 ? rate + '%' : '–';
  document.getElementById('rate-fill').style.width  = rate + '%';
  document.getElementById('rate-fill').style.background =
    rate >= 80 ? 'var(--green)' : rate >= 60 ? '#f57f17' : '#c62828';
  document.getElementById('rate-summary').textContent =
    `${pregnant} ${T.bs_pregnant.toLowerCase()} · ${tested - pregnant} ${T.not_pregnant_sum} · ${total - tested} ${T.not_yet_tested}`;

  document.getElementById('save-section').style.display = tested === total ? 'block' : 'none';
}

async function saveTest() {
  const pregnant = Object.values(cowResults).filter(v => v === 'pregnant').length;
  const total    = cowList.length;
  const rate     = Math.round((pregnant / total) * 100 * 10) / 10;

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  btn.textContent = T.saving;

  try {
    const res = await fetch('/api/pregnancy_tests.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        herd_id:         selectedHerd.id,
        test_date:       new Date().toISOString().slice(0, 10),
        results:         cowResults,
        months:          cowMonths,
        total_tested:    total,
        total_pregnant:  pregnant,
        pregnancy_rate:  rate,
      })
    }).then(r => r.json());

    if (res.success) {
      document.getElementById('step-test').style.display  = 'none';
      document.getElementById('save-result').innerHTML = `
        <div class="card" style="text-align:center;padding:24px">
          <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:var(--green);margin-bottom:12px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14l-4-4 1.41-1.41L10 13.17l6.59-6.59L18 8l-8 8z"/></svg>
          <h3>${T.test_saved}</h3>
          <p class="text-muted">${escHtml(selectedHerd.name)}:<br>
            <strong style="font-size:28px;color:${rate >= 80 ? 'var(--green)' : rate >= 60 ? '#f57f17' : '#c62828'}">${rate}%</strong>
          </p>
          <p class="text-muted text-sm">${pregnant} ${T.pregnant_of} ${total} ${T.cows_pregnant}</p>
          <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap">
            <button class="btn btn-primary" onclick="location.reload()">${T.add_another}</button>
            <a href="/quick-actions.php" class="btn btn-secondary">${T.quick_actions}</a>
          </div>
        </div>`;
    } else {
      alert(res.message || T.error_saving);
      btn.disabled = false;
      btn.textContent = T.save_pregnancy_test;
    }
  } catch(e) {
    alert(T.error_saving + ' ' + e.message);
    btn.disabled = false;
    btn.textContent = T.save_pregnancy_test;
  }
}

function showStep(id) {
  document.getElementById('step-herd').style.display = id === 'step-herd' ? 'block' : 'none';
  document.getElementById('step-test').style.display = id === 'step-test' ? 'block' : 'none';
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
