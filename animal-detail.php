<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /animals.php'); exit; }

$animal = DB::row(
    'SELECT a.*, f.name AS farm_name, h.name AS herd_name,
            m.ear_tag AS mother_tag, fa.ear_tag AS father_tag
     FROM animals a
     LEFT JOIN farms f ON f.id = a.farm_id
     LEFT JOIN herds h ON h.id = a.herd_id
     LEFT JOIN animals m ON m.id = a.mother_id
     LEFT JOIN animals fa ON fa.id = a.father_id
     WHERE a.id = ?',
    [$id]
);
if (!$animal) { header('Location: /animals.php'); exit; }

$offspring = DB::rows(
    'SELECT id, ear_tag FROM animals WHERE mother_id = ? ORDER BY dob ASC',
    [$id]
);

$pageTitle = 'animal';
require_once __DIR__ . '/templates/header.php';

$age = '';
if ($animal['dob']) {
    $dob  = new DateTime($animal['dob']);
    $now  = new DateTime();
    $diff = $now->diff($dob);
    $age  = $diff->y > 0 ? $diff->y . 'y ' . $diff->m . 'm' : $diff->m . ' months';
}

$categoryLabels = [
    'breeding_bull'      => t('cat_breeding_bull'),
    'breeding_cow'       => t('cat_breeding_cow'),
    'c_grade_cow'        => t('cat_c_grade_cow'),
    'bull_calf'          => t('cat_bull_calf'),
    'heifer_calf'        => t('cat_heifer_calf'),
    'weaner'             => t('cat_weaner'),
    'replacement_heifer' => t('cat_replacement_heifer'),
];
$statusClass = ['active' => 'badge-green', 'sold' => 'badge-amber', 'dead' => 'badge-red'];
?>

<div class="page-wrap">
<div class="page-header">
  <h1><?= beef_cow_icon() ?> <?= htmlspecialchars($animal['ear_tag']) ?></h1>
  <?php if (isSuperAdmin()): ?>
  <a href="/animal-form.php?id=<?= $id ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-pen"></i> <?= t('edit') ?></a>
  <?php endif; ?>
</div>

<!-- Animal Summary Card -->
<div class="card" style="margin-top:4px">
  <div class="card-body">
    <div class="flex-between mb-8">
      <div>
        <h2 style="font-size:1.5rem"><?= htmlspecialchars($animal['ear_tag']) ?></h2>
        <?php if ($animal['rfid']): ?>
        <span class="text-muted text-sm">RFID: <?= htmlspecialchars($animal['rfid']) ?></span>
        <?php endif; ?>
      </div>
      <span class="badge <?= $statusClass[$animal['animal_status']] ?? 'badge-grey' ?>"><?= htmlspecialchars($animal['animal_status']) ?></span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
      <div><span class="text-muted text-xs"><?= t('category') ?></span><br><strong><?= $categoryLabels[$animal['category']] ?? $animal['category'] ?></strong></div>
      <div><span class="text-muted text-xs"><?= t('breed') ?></span><br><strong><?= htmlspecialchars($animal['breed'] ?: '–') ?></strong></div>
      <div><span class="text-muted text-xs"><?= t('sex') ?></span><br><strong><?= $animal['sex'] === 'male' ? t('male') : t('female') ?></strong></div>
      <div><span class="text-muted text-xs"><?= t('age') ?></span><br><strong><?= htmlspecialchars($age ?: '–') ?></strong></div>
      <div><span class="text-muted text-xs"><?= t('farm') ?></span><br><strong><?= htmlspecialchars($animal['farm_name'] ?: '–') ?></strong></div>
      <div><span class="text-muted text-xs"><?= t('herd') ?></span><br><strong><?= htmlspecialchars($animal['herd_name'] ?: '–') ?></strong></div>
      <?php if ($animal['mother_tag']): ?>
      <div><span class="text-muted text-xs"><?= t('mother') ?></span><br><a href="/animal-detail.php?id=<?= $animal['mother_id'] ?>"><strong><?= htmlspecialchars($animal['mother_tag']) ?></strong></a></div>
      <?php endif; ?>
      <?php if ($animal['father_tag']): ?>
      <div><span class="text-muted text-xs"><?= t('father') ?></span><br><a href="/animal-detail.php?id=<?= $animal['father_id'] ?>"><strong><?= htmlspecialchars($animal['father_tag']) ?></strong></a></div>
      <?php endif; ?>
      <?php if ($animal['breeding_status']): ?>
      <div><span class="text-muted text-xs"><?= t('breeding_status') ?></span><br><strong><?= t('bs_' . $animal['breeding_status']) ?></strong></div>
      <?php endif; ?>
      <?php if ($animal['breeding_status'] === 'pregnant' && $animal['breeding_date']): ?>
      <?php $dueDate = date('d M Y', strtotime($animal['breeding_date'] . ' +285 days')); ?>
      <div><span class="text-muted text-xs"><?= t('expected_calving') ?></span><br><strong><?= $dueDate ?></strong></div>
      <?php endif; ?>
      <?php if ($animal['category'] === 'breeding_cow' && $animal['last_calving_date']): ?>
      <div><span class="text-muted text-xs"><?= t('last_calving_date') ?></span><br><strong><?= date('d M Y', strtotime($animal['last_calving_date'])) ?></strong></div>
      <?php endif; ?>
      <?php if ($animal['category'] === 'breeding_cow' && $animal['avg_calf_interval']): ?>
      <?php
        $avgDays = round($animal['avg_calf_interval']);
        if ($avgDays <= 365) {
            $badge = ['label' => t('excellent'), 'bg' => '#e8f5e9', 'color' => '#2e7d32'];
        } elseif ($avgDays <= 420) {
            $badge = ['label' => t('good'),      'bg' => '#fff8e1', 'color' => '#f57f17'];
        } else {
            $badge = ['label' => t('poor_label'),'bg' => '#ffebee', 'color' => '#c62828'];
        }
      ?>
      <div>
        <span class="text-muted text-xs"><?= t('avg_calving_interval_label') ?></span><br>
        <strong><?= $avgDays ?> <?= t('days') ?></strong>
        <span style="display:inline-block;margin-left:6px;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:700;background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>"><?= $badge['label'] ?></span>
      </div>
      <?php endif; ?>
    </div>
      <?php
      $totalCalves = DB::val('SELECT COUNT(*) FROM calving WHERE dam_id = ?', [$id]);
      if ($totalCalves): ?>
      <div style="grid-column:1/-1">
        <span class="text-muted text-xs"><?= t('calves') ?></span><br>
        <strong><?= $totalCalves ?> <?= $totalCalves == 1 ? t('calf_single') : t('calves') ?></strong>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($animal['comments']): ?>
    <p class="text-muted text-sm mt-12"><?= htmlspecialchars($animal['comments']) ?></p>
    <?php endif; ?>
  </div>
</div>

<!-- Tabs -->
<div class="tabs" id="detail-tabs">
  <button class="tab-btn active" data-tab="weights"><?= t('weights') ?></button>
  <button class="tab-btn" data-tab="vaccinations"><?= t('tab_vaccines') ?></button>
  <button class="tab-btn" data-tab="treatments"><?= t('treatments') ?></button>
  <button class="tab-btn" data-tab="events"><?= t('events') ?></button>
  <button class="tab-btn" data-tab="calving"><?= t('calving') ?></button>
</div>

<div id="tab-weights" class="tab-panel active" style="padding:16px">
  <div id="weights-content"><div class="page-loader"><div class="spinner"></div></div></div>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-full mt-12" onclick="openAddModal('weight')">+ <?= t('add_weight') ?></button>
  <?php endif; ?>
</div>

<div id="tab-vaccinations" class="tab-panel" style="padding:16px">
  <div id="vacc-content"><div class="page-loader"><div class="spinner"></div></div></div>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-full mt-12" onclick="openAddModal('vaccination')">+ <?= t('add_vaccination') ?></button>
  <?php endif; ?>
</div>

<div id="tab-treatments" class="tab-panel" style="padding:16px">
  <div id="treat-content"><div class="page-loader"><div class="spinner"></div></div></div>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-full mt-12" onclick="openAddModal('treatment')">+ <?= t('add_treatment') ?></button>
  <?php endif; ?>
</div>

<div id="tab-events" class="tab-panel" style="padding:16px">
  <div id="events-content"><div class="page-loader"><div class="spinner"></div></div></div>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-full mt-12" onclick="openAddModal('event')">+ <?= t('add_event') ?></button>
  <?php endif; ?>
</div>

<div id="tab-calving" class="tab-panel" style="padding:16px">
  <div id="calving-content"><div class="page-loader"><div class="spinner"></div></div></div>
</div>

<!-- Quick Add Modals -->
<div class="modal-overlay" id="weight-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title" id="weight-modal-title"><?= t('add_weight') ?></div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label"><?= t('weight_kg') ?> <span class="required">*</span></label><input type="number" id="w-kg" class="form-control" step="0.1" min="0"></div>
      <div class="form-group"><label class="form-label"><?= t('date') ?> <span class="required">*</span></label><input type="date" id="w-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="form-group"><label class="form-label"><?= t('notes') ?></label><textarea id="w-notes" class="form-control"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('weight-modal')"><?= t('cancel') ?></button>
      <button class="btn btn-primary" onclick="saveWeight()"><?= t('save') ?></button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="vaccination-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title"><?= t('add_vaccination') ?></div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label"><?= t('product') ?> <span class="required">*</span></label><input type="text" id="v-product" class="form-control"></div>
      <div class="form-group"><label class="form-label"><?= t('dosage') ?></label><input type="text" id="v-dosage" class="form-control" placeholder="e.g. 2ml"></div>
      <div class="form-group"><label class="form-label"><?= t('due_date') ?> <span class="required">*</span></label><input type="date" id="v-due" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="form-group"><label class="form-label"><?= t('notes') ?></label><textarea id="v-notes" class="form-control"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('vaccination-modal')"><?= t('cancel') ?></button>
      <button class="btn btn-primary" onclick="saveVaccination()"><?= t('save') ?></button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="treatment-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title"><?= t('add_treatment') ?></div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label"><?= t('product') ?> <span class="required">*</span></label><input type="text" id="t-product" class="form-control"></div>
      <div class="form-group"><label class="form-label"><?= t('dosage') ?></label><input type="text" id="t-dosage" class="form-control"></div>
      <div class="form-group"><label class="form-label"><?= t('date') ?> <span class="required">*</span></label><input type="date" id="t-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="form-group"><label class="form-label"><?= t('notes') ?></label><textarea id="t-notes" class="form-control"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('treatment-modal')"><?= t('cancel') ?></button>
      <button class="btn btn-primary" onclick="saveTreatment()"><?= t('save') ?></button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="event-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title"><?= t('add_event') ?></div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label"><?= t('event_type') ?> <span class="required">*</span></label>
        <select id="ev-type" class="form-control">
          <option value="branding"><?= t('ev_branding') ?></option>
          <option value="dehorning"><?= t('ev_dehorning') ?></option>
          <option value="castration"><?= t('ev_castration') ?></option>
          <option value="weaning"><?= t('ev_weaning') ?></option>
          <option value="pregnancy_test"><?= t('pregnancy_test') ?></option>
          <option value="other"><?= t('ev_other') ?></option>
        </select>
      </div>
      <div class="form-group"><label class="form-label"><?= t('date') ?> <span class="required">*</span></label><input type="date" id="ev-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="form-group"><label class="form-label"><?= t('notes') ?></label><textarea id="ev-notes" class="form-control"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('event-modal')"><?= t('cancel') ?></button>
      <button class="btn btn-primary" onclick="saveEvent()"><?= t('save') ?></button>
    </div>
  </div>
</div>

<script>
const ANIMAL_ID = <?= $id ?>;
const T = <?= json_encode([
  'no_weights'         => t('no_weights'),
  'no_vaccinations_rec'=> t('no_vaccinations_rec'),
  'no_treatments_rec'  => t('no_treatments_rec'),
  'no_events_rec'      => t('no_events_rec'),
  'no_calving_rec'     => t('no_calving_rec'),
  'pending_label'      => t('pending_label'),
  'history_label'      => t('history_label'),
  'mark_done'          => t('mark_done'),
  'overdue'            => t('overdue'),
  'due_date'           => t('due_date'),
  'add_weight'         => t('add_weight'),
  'edit_weight'        => t('edit_weight'),
  'cat_bull_calf'      => t('cat_bull_calf'),
  'cat_heifer_calf'    => t('cat_heifer_calf'),
  'ev_branding'        => t('ev_branding'),
  'ev_dehorning'       => t('ev_dehorning'),
  'ev_castration'      => t('ev_castration'),
  'ev_weaning'         => t('ev_weaning'),
  'ev_other'           => t('ev_other'),
  'pregnancy_test'     => t('pregnancy_test'),
  'save'               => t('save'),
  'cancel'             => t('cancel'),
  'pick_a_date'        => t('pick_a_date'),
  'weight_date_required'=> t('weight_date_required'),
  'product_due_required'=> t('product_due_required'),
  'product_date_required'=> t('product_date_required'),
  'type_date_required' => t('type_date_required'),
  'error_saving'       => t('error_saving'),
]) ?>;

// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    loadTab(btn.dataset.tab);
  });
});

// Load initial tab
loadTab('weights');

function loadTab(tab) {
  switch(tab) {
    case 'weights':     loadWeights();     break;
    case 'vaccinations':loadVaccinations(); break;
    case 'treatments':  loadTreatments();  break;
    case 'events':      loadEvents();      break;
    case 'calving':     loadCalving();     break;
  }
}

function loadWeights() {
  fetch(`/api/weights.php?animal_id=${ANIMAL_ID}`)
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('weights-content');
      if (!res.data?.length) { el.innerHTML = `<p class="text-muted text-sm">${T.no_weights}</p>`; return; }
      el.innerHTML = '<div class="list-card">' + res.data.map(w => {
        let gain = '';
        if (w.adg != null) {
          const color = w.adg >= 0 ? 'var(--green)' : 'var(--red)';
          const sign  = w.adg >= 0 ? '+' : '';
          gain = `<span class="text-xs" style="color:${color}"> ${sign}${w.kg_gained}kg total &nbsp;·&nbsp; ${sign}${w.adg} kg/day</span>`;
        }
        return `<div class="list-item">
          <div class="item-body">
            <div class="item-title">${w.weight_kg} kg ${gain}</div>
            <div class="item-sub">${formatDate(w.weigh_date)}${w.notes ? ' · ' + escHtml(w.notes) : ''}</div>
          </div>
          <?php if (isSuperAdmin()): ?>
          <button class="btn btn-sm btn-secondary" style="flex-shrink:0"
            data-id="${w.id}" data-kg="${w.weight_kg}" data-date="${w.weigh_date}" data-notes="${escHtml(w.notes||'')}"
            onclick="editWeightFromBtn(this)">${T.edit}</button>
          <?php endif; ?>
        </div>`;
      }).join('') + '</div>';
    });
}

function loadVaccinations() {
  fetch(`/api/vaccinations.php?animal_id=${ANIMAL_ID}`)
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('vacc-content');
      if (!res.data?.length) { el.innerHTML = `<p class="text-muted text-sm">${T.no_vaccinations_rec}</p>`; return; }
      const today   = new Date();
      const pending = res.data.filter(v => !v.completed);
      const history = res.data.filter(v =>  v.completed);

      let html = '';

      if (pending.length) {
        html += `<p class="text-xs text-muted" style="margin:0 0 6px">${T.pending_label}</p><div class="list-card" style="margin-bottom:16px">`;
        html += pending.map(v => {
          const overdue = new Date(v.due_date) < today;
          return `<div class="list-item">
            <div class="item-body">
              <div class="item-title">${escHtml(v.product)}</div>
              <div class="item-sub">${T.due_date}: ${formatDate(v.due_date)}${v.dosage ? ' · ' + escHtml(v.dosage) : ''}</div>
            </div>
            <span class="badge ${overdue ? 'badge-red' : 'badge-amber'}">${overdue ? T.overdue : T.pending_label}</span>
          </div>`;
        }).join('') + '</div>';
      }

      if (history.length) {
        html += `<p class="text-xs text-muted" style="margin:0 0 6px">${T.history_label}</p><div class="list-card">`;
        html += history.map(v => `
          <div class="list-item">
            <div class="item-body">
              <div class="item-title">${escHtml(v.product)}</div>
              <div class="item-sub">
                ${T.mark_done}: ${formatDate(v.completion_date || v.due_date)}
                ${v.dosage ? ' · ' + escHtml(v.dosage) : ''}
                ${v.notes  ? ' · ' + escHtml(v.notes)  : ''}
              </div>
            </div>
            <span class="badge badge-green">${T.mark_done}</span>
          </div>`).join('') + '</div>';
      }

      el.innerHTML = html || `<p class="text-muted text-sm">${T.no_vaccinations_rec}</p>`;
    });
}

function loadTreatments() {
  fetch(`/api/treatments.php?animal_id=${ANIMAL_ID}`)
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('treat-content');
      if (!res.data?.length) { el.innerHTML = `<p class="text-muted text-sm">${T.no_treatments_rec}</p>`; return; }
      el.innerHTML = '<div class="list-card">' + res.data.map(t => `
        <div class="list-item">
          <div class="item-body">
            <div class="item-title">${escHtml(t.product)}</div>
            <div class="item-sub">${formatDate(t.treat_date)}${t.dosage ? ' · ' + escHtml(t.dosage) : ''}</div>
          </div>
        </div>
      `).join('') + '</div>';
    });
}

function loadEvents() {
  fetch(`/api/events.php?animal_id=${ANIMAL_ID}`)
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('events-content');
      if (!res.data?.length) { el.innerHTML = `<p class="text-muted text-sm">${T.no_events_rec}</p>`; return; }
      const evLabels = {
        branding: T.ev_branding, dehorning: T.ev_dehorning, castration: T.ev_castration,
        weaning: T.ev_weaning, pregnancy_test: T.pregnancy_test, other: T.ev_other
      };
      el.innerHTML = '<div class="list-card">' + res.data.map(e => `
        <div class="list-item">
          <div class="item-body">
            <div class="item-title">${escHtml(evLabels[e.event_type] || e.event_type)}</div>
            <div class="item-sub">${formatDate(e.event_date)}${e.notes ? ' · ' + escHtml(e.notes) : ''}</div>
          </div>
        </div>
      `).join('') + '</div>';
    });
}

function loadCalving() {
  fetch(`/api/calving.php?dam_id=${ANIMAL_ID}`)
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('calving-content');
      if (!res.data?.length) { el.innerHTML = `<p class="text-muted text-sm">${T.no_calving_rec}</p>`; return; }
      const ordinals = ['1st','2nd','3rd','4th','5th','6th','7th','8th','9th','10th'];
      const total = res.data.length;
      // data is ASC so index 0 = first calf
      el.innerHTML = '<div class="list-card" id="calving-list">' + res.data.map((c, i) => {
        const num = ordinals[i] || (i + 1) + 'th';
        const sex = c.calf_sex ? (c.calf_sex === 'male' ? '♂ ' + T.cat_bull_calf : '♀ ' + T.cat_heifer_calf) : '';
        const calfLink = c.calf_id
          ? `<a href="/animal-detail.php?id=${c.calf_id}" style="font-weight:600;color:var(--green)">${escHtml(c.calf_tag || 'Unknown')}</a>`
          : escHtml(c.calf_tag || 'Unknown');
        return `
        <div class="list-item">
          <div class="item-icon" style="font-size:1.1rem;font-weight:700;color:var(--green);min-width:36px;text-align:center">${num}</div>
          <div class="item-body">
            <div class="item-title">${calfLink}</div>
            <div class="item-sub" id="calving-sub-${c.id}">${formatDate(c.calving_date)}${sex ? ' · ' + sex : ''}</div>
            <div id="calving-edit-${c.id}" style="display:none;margin-top:6px;display:none">
              <input type="date" id="calving-date-${c.id}" value="${c.calving_date}"
                style="padding:4px 8px;border:1px solid var(--border);border-radius:6px;font-size:0.85rem">
              <button onclick="saveCalvingDate(${c.id},'${c.calving_date}')"
                style="margin-left:6px;padding:4px 10px;background:var(--green);color:#fff;border:none;border-radius:6px;font-size:0.85rem;cursor:pointer">${T.save}</button>
              <button onclick="cancelCalvingEdit(${c.id})"
                style="margin-left:4px;padding:4px 10px;background:#eee;border:none;border-radius:6px;font-size:0.85rem;cursor:pointer">${T.cancel}</button>
            </div>
          </div>
          <button onclick="toggleCalvingEdit(${c.id})" title="Edit date"
            style="background:none;border:none;cursor:pointer;padding:4px;opacity:0.5">
            <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
          </button>
        </div>`;
      }).join('') + '</div>';
    });
}

function toggleCalvingEdit(id) {
  const box = document.getElementById('calving-edit-' + id);
  box.style.display = box.style.display === 'none' ? 'block' : 'none';
}

function cancelCalvingEdit(id) {
  document.getElementById('calving-edit-' + id).style.display = 'none';
}

function saveCalvingDate(id, original) {
  const date = document.getElementById('calving-date-' + id).value;
  if (!date) { alert(T.pick_a_date); return; }
  fetch(`/api/calving.php?id=${id}`, {
    method: 'PUT',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ calving_date: date })
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        document.getElementById('calving-edit-' + id).style.display = 'none';
        loadCalving(); // refresh the list
      } else {
        alert(res.message || T.error_saving);
      }
    });
}

function openAddModal(type) {
  if (type === 'weight') {
    document.getElementById('weight-modal').dataset.editId = '';
    document.getElementById('weight-modal-title').textContent = T.add_weight;
    document.getElementById('w-kg').value = '';
    document.getElementById('w-date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('w-notes').value = '';
  }
  openModal(type + '-modal');
}

function editWeightFromBtn(btn) {
  editWeight(parseInt(btn.dataset.id), parseFloat(btn.dataset.kg), btn.dataset.date, btn.dataset.notes);
}

function editWeight(id, kg, date, notes) {
  document.getElementById('w-kg').value    = kg;
  document.getElementById('w-date').value  = date;
  document.getElementById('w-notes').value = notes || '';
  // Store edit id on modal and switch save button behaviour
  document.getElementById('weight-modal').dataset.editId = id;
  document.getElementById('weight-modal-title').textContent = T.edit_weight;
  openModal('weight-modal');
}

function saveWeight() {
  const kg     = document.getElementById('w-kg').value;
  const date   = document.getElementById('w-date').value;
  const editId = document.getElementById('weight-modal').dataset.editId;
  if (!kg || !date) { alert(T.weight_date_required); return; }
  const method = editId ? 'PUT' : 'POST';
  const url    = editId ? `/api/weights.php?id=${editId}` : '/api/weights.php';
  const body   = editId
    ? { weight_kg: parseFloat(kg), weigh_date: date, notes: document.getElementById('w-notes').value }
    : { animal_id: ANIMAL_ID, weight_kg: parseFloat(kg), weigh_date: date, notes: document.getElementById('w-notes').value };
  fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) })
    .then(r => r.json()).then(res => {
      if (res.success) {
        document.getElementById('weight-modal').dataset.editId = '';
        document.getElementById('weight-modal-title').textContent = T.add_weight;
        closeModal('weight-modal');
        loadWeights();
      } else alert(res.message);
    });
}

function saveVaccination() {
  const product = document.getElementById('v-product').value.trim();
  const due     = document.getElementById('v-due').value;
  if (!product || !due) { alert(T.product_due_required); return; }
  fetch('/api/vaccinations.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ animal_id: ANIMAL_ID, product, dosage: document.getElementById('v-dosage').value, due_date: due, notes: document.getElementById('v-notes').value })
  }).then(r => r.json()).then(res => {
    if (res.success) { closeModal('vaccination-modal'); loadVaccinations(); }
    else alert(res.message);
  });
}

function saveTreatment() {
  const product = document.getElementById('t-product').value.trim();
  const date    = document.getElementById('t-date').value;
  if (!product || !date) { alert(T.product_date_required); return; }
  fetch('/api/treatments.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ animal_id: ANIMAL_ID, product, dosage: document.getElementById('t-dosage').value, treat_date: date, notes: document.getElementById('t-notes').value })
  }).then(r => r.json()).then(res => {
    if (res.success) { closeModal('treatment-modal'); loadTreatments(); }
    else alert(res.message);
  });
}

function saveEvent() {
  const type = document.getElementById('ev-type').value;
  const date = document.getElementById('ev-date').value;
  if (!type || !date) { alert(T.type_date_required); return; }
  fetch('/api/events.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ animal_id: ANIMAL_ID, event_type: type, event_date: date, notes: document.getElementById('ev-notes').value })
  }).then(r => r.json()).then(res => {
    if (res.success) { closeModal('event-modal'); loadEvents(); }
    else alert(res.message);
  });
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function formatDate(s) { if (!s) return ''; return new Date(s + 'T00:00:00').toLocaleDateString(); }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
