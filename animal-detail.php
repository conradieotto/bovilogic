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
    'breeding_bull' => t('cat_breeding_bull'),
    'cow'           => t('cat_cow'),
    'calf'          => t('cat_calf'),
    'open_heifer'   => t('cat_open_heifer'),
    'heifer'        => t('cat_heifer'),
    'weaner'        => t('cat_weaner'),
    'steer'         => t('cat_steer'),
    'ox'            => t('cat_ox'),
];
$statusClass = ['active' => 'badge-green', 'sold' => 'badge-amber', 'dead' => 'badge-red'];
?>

<header class="page-header">
  <a href="/animals.php" class="btn-icon" aria-label="Back">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= htmlspecialchars($animal['ear_tag']) ?></h1>
  <?php if (isSuperAdmin()): ?>
  <a href="/animal-form.php?id=<?= $id ?>" class="btn-icon" aria-label="Edit">
    <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
  </a>
  <?php endif; ?>
</header>

<!-- Animal Summary Card -->
<div style="padding: 16px;">
<div class="card">
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
      <div><span class="text-muted text-xs">Category</span><br><strong><?= $categoryLabels[$animal['category']] ?? $animal['category'] ?></strong></div>
      <div><span class="text-muted text-xs">Breed</span><br><strong><?= htmlspecialchars($animal['breed'] ?: '–') ?></strong></div>
      <div><span class="text-muted text-xs">Sex</span><br><strong><?= ucfirst($animal['sex']) ?></strong></div>
      <div><span class="text-muted text-xs">Age</span><br><strong><?= htmlspecialchars($age ?: '–') ?></strong></div>
      <div><span class="text-muted text-xs">Farm</span><br><strong><?= htmlspecialchars($animal['farm_name'] ?: '–') ?></strong></div>
      <div><span class="text-muted text-xs">Herd</span><br><strong><?= htmlspecialchars($animal['herd_name'] ?: '–') ?></strong></div>
      <?php if ($animal['mother_tag']): ?>
      <div><span class="text-muted text-xs">Mother</span><br><a href="/animal-detail.php?id=<?= $animal['mother_id'] ?>"><strong><?= htmlspecialchars($animal['mother_tag']) ?></strong></a></div>
      <?php endif; ?>
      <?php if ($animal['father_tag']): ?>
      <div><span class="text-muted text-xs">Father</span><br><a href="/animal-detail.php?id=<?= $animal['father_id'] ?>"><strong><?= htmlspecialchars($animal['father_tag']) ?></strong></a></div>
      <?php endif; ?>
      <?php if ($animal['breeding_status']): ?>
      <div><span class="text-muted text-xs">Breeding Status</span><br><strong><?= htmlspecialchars($animal['breeding_status']) ?></strong></div>
      <?php endif; ?>
    </div>
    <?php if ($animal['comments']): ?>
    <p class="text-muted text-sm mt-12"><?= htmlspecialchars($animal['comments']) ?></p>
    <?php endif; ?>
  </div>
</div>
</div>

<!-- Tabs -->
<div class="tabs" id="detail-tabs">
  <button class="tab-btn active" data-tab="weights">Weights</button>
  <button class="tab-btn" data-tab="vaccinations">Vaccines</button>
  <button class="tab-btn" data-tab="treatments">Treatments</button>
  <button class="tab-btn" data-tab="events">Events</button>
  <button class="tab-btn" data-tab="calving">Calving</button>
</div>

<div id="tab-weights" class="tab-panel active" style="padding:16px">
  <div id="weights-content"><div class="page-loader"><div class="spinner"></div></div></div>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-full mt-12" onclick="openAddModal('weight')">+ Add Weight</button>
  <?php endif; ?>
</div>

<div id="tab-vaccinations" class="tab-panel" style="padding:16px">
  <div id="vacc-content"><div class="page-loader"><div class="spinner"></div></div></div>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-full mt-12" onclick="openAddModal('vaccination')">+ Add Vaccination</button>
  <?php endif; ?>
</div>

<div id="tab-treatments" class="tab-panel" style="padding:16px">
  <div id="treat-content"><div class="page-loader"><div class="spinner"></div></div></div>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-full mt-12" onclick="openAddModal('treatment')">+ Add Treatment</button>
  <?php endif; ?>
</div>

<div id="tab-events" class="tab-panel" style="padding:16px">
  <div id="events-content"><div class="page-loader"><div class="spinner"></div></div></div>
  <?php if (isSuperAdmin()): ?>
  <button class="btn btn-primary btn-full mt-12" onclick="openAddModal('event')">+ Add Event</button>
  <?php endif; ?>
</div>

<div id="tab-calving" class="tab-panel" style="padding:16px">
  <div id="calving-content"><div class="page-loader"><div class="spinner"></div></div></div>
</div>

<!-- Quick Add Modals -->
<div class="modal-overlay" id="weight-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add Weight</div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Weight (kg) <span class="required">*</span></label><input type="number" id="w-kg" class="form-control" step="0.1" min="0"></div>
      <div class="form-group"><label class="form-label">Date <span class="required">*</span></label><input type="date" id="w-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="form-group"><label class="form-label">Notes</label><textarea id="w-notes" class="form-control"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('weight-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveWeight()">Save</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="vaccination-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add Vaccination</div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Product <span class="required">*</span></label><input type="text" id="v-product" class="form-control"></div>
      <div class="form-group"><label class="form-label">Dosage</label><input type="text" id="v-dosage" class="form-control" placeholder="e.g. 2ml"></div>
      <div class="form-group"><label class="form-label">Due Date <span class="required">*</span></label><input type="date" id="v-due" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="form-group"><label class="form-label">Notes</label><textarea id="v-notes" class="form-control"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('vaccination-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveVaccination()">Save</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="treatment-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add Treatment</div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Product <span class="required">*</span></label><input type="text" id="t-product" class="form-control"></div>
      <div class="form-group"><label class="form-label">Dosage</label><input type="text" id="t-dosage" class="form-control"></div>
      <div class="form-group"><label class="form-label">Date <span class="required">*</span></label><input type="date" id="t-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="form-group"><label class="form-label">Notes</label><textarea id="t-notes" class="form-control"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('treatment-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveTreatment()">Save</button>
    </div>
  </div>
</div>

<div class="modal-overlay" id="event-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add Event</div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Event Type <span class="required">*</span></label>
        <select id="ev-type" class="form-control">
          <option value="branding">Branding</option>
          <option value="dehorning">Dehorning</option>
          <option value="castration">Castration</option>
          <option value="weaning">Weaning</option>
          <option value="pregnancy_test">Pregnancy Test</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Date <span class="required">*</span></label><input type="date" id="ev-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="form-group"><label class="form-label">Notes</label><textarea id="ev-notes" class="form-control"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('event-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveEvent()">Save</button>
    </div>
  </div>
</div>

<script>
const ANIMAL_ID = <?= $id ?>;

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
      if (!res.data?.length) { el.innerHTML = '<p class="text-muted text-sm">No weights recorded.</p>'; return; }
      let prev = null;
      el.innerHTML = '<div class="list-card">' + res.data.map(w => {
        let gain = '';
        if (prev && w.weight_kg && prev.weight_kg) {
          const diff = parseFloat(w.weight_kg) - parseFloat(prev.weight_kg);
          gain = `<span class="text-xs ${diff >= 0 ? 'text-muted' : 'badge-red'}" style="color:${diff>=0?'var(--green)':'var(--red)'}"> ${diff >= 0 ? '+' : ''}${diff.toFixed(1)}kg</span>`;
        }
        prev = w;
        return `<div class="list-item">
          <div class="item-body">
            <div class="item-title">${w.weight_kg} kg ${gain}</div>
            <div class="item-sub">${formatDate(w.weigh_date)}${w.notes ? ' · ' + escHtml(w.notes) : ''}</div>
          </div>
        </div>`;
      }).join('') + '</div>';
    });
}

function loadVaccinations() {
  fetch(`/api/vaccinations.php?animal_id=${ANIMAL_ID}`)
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('vacc-content');
      if (!res.data?.length) { el.innerHTML = '<p class="text-muted text-sm">No vaccinations recorded.</p>'; return; }
      const today = new Date();
      el.innerHTML = '<div class="list-card">' + res.data.map(v => {
        const due = new Date(v.due_date);
        const overdue = !v.completed && due < today;
        return `<div class="list-item">
          <div class="item-body">
            <div class="item-title">${escHtml(v.product)}</div>
            <div class="item-sub">Due: ${formatDate(v.due_date)}${v.dosage ? ' · ' + escHtml(v.dosage) : ''}</div>
          </div>
          <div class="item-end">
            <span class="badge ${v.completed ? 'badge-green' : overdue ? 'badge-red' : 'badge-amber'}">
              ${v.completed ? 'Done' : overdue ? 'Overdue' : 'Pending'}
            </span>
          </div>
        </div>`;
      }).join('') + '</div>';
    });
}

function loadTreatments() {
  fetch(`/api/treatments.php?animal_id=${ANIMAL_ID}`)
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('treat-content');
      if (!res.data?.length) { el.innerHTML = '<p class="text-muted text-sm">No treatments recorded.</p>'; return; }
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
      if (!res.data?.length) { el.innerHTML = '<p class="text-muted text-sm">No events recorded.</p>'; return; }
      el.innerHTML = '<div class="list-card">' + res.data.map(e => `
        <div class="list-item">
          <div class="item-body">
            <div class="item-title">${escHtml(e.event_type)}</div>
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
      if (!res.data?.length) { el.innerHTML = '<p class="text-muted text-sm">No calving records.</p>'; return; }
      el.innerHTML = '<div class="list-card">' + res.data.map(c => `
        <div class="list-item">
          <div class="item-body">
            <div class="item-title">Calf: ${escHtml(c.calf_tag || 'Unknown')}</div>
            <div class="item-sub">${formatDate(c.calving_date)}</div>
          </div>
        </div>
      `).join('') + '</div>';
    });
}

function openAddModal(type) {
  openModal(type + '-modal');
}

function saveWeight() {
  const kg   = document.getElementById('w-kg').value;
  const date = document.getElementById('w-date').value;
  if (!kg || !date) { alert('Weight and date are required.'); return; }
  fetch('/api/weights.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ animal_id: ANIMAL_ID, weight_kg: parseFloat(kg), weigh_date: date, notes: document.getElementById('w-notes').value })
  }).then(r => r.json()).then(res => {
    if (res.success) { closeModal('weight-modal'); loadWeights(); }
    else alert(res.message);
  });
}

function saveVaccination() {
  const product = document.getElementById('v-product').value.trim();
  const due     = document.getElementById('v-due').value;
  if (!product || !due) { alert('Product and due date are required.'); return; }
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
  if (!product || !date) { alert('Product and date are required.'); return; }
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
  if (!type || !date) { alert('Type and date are required.'); return; }
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
