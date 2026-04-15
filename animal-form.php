<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
requireRole('super_admin');
$user = currentUser();
loadLanguage($user['language']);

$id     = (int)($_GET['id'] ?? 0);
$isCalfMode = isset($_GET['calf']);
$animal = null;

if ($id) {
    $animal = DB::row('SELECT * FROM animals WHERE id = ?', [$id]);
    if (!$animal) { header('Location: /animals.php'); exit; }
}

$farms = DB::rows('SELECT id, name FROM farms WHERE is_active = 1 ORDER BY name');
$herds = DB::rows('SELECT h.id, h.name, h.farm_id FROM herds h WHERE h.is_active = 1 ORDER BY h.name');
$bulls = DB::rows("SELECT id, ear_tag FROM animals WHERE category = 'breeding_bull' AND animal_status = 'active' ORDER BY ear_tag");

$pageTitle = $id ? 'edit_animal' : ($isCalfMode ? 'add_calf' : 'add_animal');
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="<?= $id ? "/animal-detail.php?id=$id" : (($_GET['from'] ?? '') === 'quick' ? '/quick-actions.php' : '/animals.php') ?>" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= $id ? t('edit_animal') : ($isCalfMode ? t('add_calf') : t('add_animal')) ?></h1>
</header>

<div style="padding:16px;">
<form id="animal-form">
  <div class="form-group">
    <label class="form-label"><?= t('ear_tag') ?> <span class="required">*</span></label>
    <input type="text" id="ear_tag" name="ear_tag" class="form-control" value="<?= htmlspecialchars($animal['ear_tag'] ?? '') ?>" required>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('rfid') ?></label>
    <input type="text" id="rfid" name="rfid" class="form-control" value="<?= htmlspecialchars($animal['rfid'] ?? '') ?>">
  </div>
  <div class="form-row">
    <div class="form-group">
      <label class="form-label"><?= t('sex') ?> <span class="required">*</span></label>
      <select id="sex" name="sex" class="form-control">
        <option value="female" <?= ($animal['sex']??'female') === 'female' ? 'selected' : '' ?>><?= t('female') ?></option>
        <option value="male"   <?= ($animal['sex']??'') === 'male' ? 'selected' : '' ?>><?= t('male') ?></option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label"><?= t('dob') ?></label>
      <input type="date" id="dob" name="dob" class="form-control" value="<?= htmlspecialchars($animal['dob'] ?? '') ?>">
    </div>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('breed') ?></label>
    <input type="text" id="breed" name="breed" class="form-control" value="<?= htmlspecialchars($animal['breed'] ?? '') ?>" placeholder="e.g. Angus, Brahman">
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('category') ?> <span class="required">*</span></label>
    <select id="category" name="category" class="form-control">
      <?php $cats = ['breeding_bull','breeding_cow','c_grade_cow','bull_calf','heifer_calf','weaner','replacement_heifer']; ?>
      <?php foreach ($cats as $c): ?>
      <option value="<?= $c ?>" <?= ($animal['category'] ?? 'breeding_cow') === $c ? 'selected' : '' ?>><?= t('cat_'.$c) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('farm') ?></label>
    <select id="farm_id" name="farm_id" class="form-control" onchange="filterHerds()">
      <option value="">– Select Farm –</option>
      <?php foreach ($farms as $f): ?>
      <option value="<?= $f['id'] ?>" <?= ($animal['farm_id'] ?? '') == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('herd') ?></label>
    <select id="herd_id" name="herd_id" class="form-control">
      <option value="">– Select Herd –</option>
      <?php foreach ($herds as $h): ?>
      <option value="<?= $h['id'] ?>" data-farm="<?= $h['farm_id'] ?>" <?= ($animal['herd_id'] ?? '') == $h['id'] ? 'selected' : '' ?>><?= htmlspecialchars($h['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('mother') ?></label>
    <select id="mother_id" name="mother_id" class="form-control" <?= $isCalfMode ? '' : '' ?>>
      <option value="">– None –</option>
      <?php
      $cows = DB::rows("SELECT id, ear_tag FROM animals WHERE sex = 'female' AND animal_status = 'active' ORDER BY ear_tag");
      foreach ($cows as $c): ?>
      <option value="<?= $c['id'] ?>" <?= ($animal['mother_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['ear_tag']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('father') ?></label>
    <select id="father_id" name="father_id" class="form-control">
      <option value="">– None –</option>
      <?php foreach ($bulls as $b): ?>
      <option value="<?= $b['id'] ?>" <?= ($animal['father_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['ear_tag']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('breeding_status') ?></label>
    <select id="breeding_status" name="breeding_status" class="form-control" onchange="toggleBreedingDate()">
      <option value="open"     <?= ($animal['breeding_status'] ?? 'open') === 'open' ? 'selected' : '' ?>><?= t('bs_open') ?></option>
      <option value="pregnant" <?= ($animal['breeding_status'] ?? '') === 'pregnant' ? 'selected' : '' ?>><?= t('bs_pregnant') ?></option>
      <option value="calved"   <?= ($animal['breeding_status'] ?? '') === 'calved' ? 'selected' : '' ?>><?= t('bs_calved') ?></option>
    </select>
  </div>
  <div class="form-group" id="breeding-date-group" style="display:none">
    <label class="form-label">Breeding Date <span class="required">*</span></label>
    <input type="date" id="breeding_date" name="breeding_date" class="form-control" value="<?= htmlspecialchars($animal['breeding_date'] ?? '') ?>">
    <small class="text-muted text-xs" id="due-date-display" style="display:block;margin-top:4px"></small>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('animal_status') ?></label>
    <select id="animal_status" name="animal_status" class="form-control" onchange="toggleStatusFields()">
      <option value="active" <?= ($animal['animal_status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= t('as_active') ?></option>
      <option value="sold"   <?= ($animal['animal_status'] ?? '') === 'sold' ? 'selected' : '' ?>><?= t('as_sold') ?></option>
      <option value="dead"   <?= ($animal['animal_status'] ?? '') === 'dead' ? 'selected' : '' ?>><?= t('as_dead') ?></option>
    </select>
  </div>
  <div id="status-extra-group" style="display:none">
    <div class="form-group">
      <label class="form-label" id="status-date-label">Date</label>
      <input type="date" id="status_date" name="status_date" class="form-control"
             value="<?= htmlspecialchars($animal['status_date'] ?? date('Y-m-d')) ?>">
    </div>
    <div class="form-group">
      <label class="form-label" id="status-notes-label">Notes</label>
      <textarea id="status_notes" name="status_notes" class="form-control" rows="2"
                placeholder="e.g. sold to John, cause of death..."><?= htmlspecialchars($animal['status_notes'] ?? '') ?></textarea>
    </div>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('comments') ?></label>
    <textarea id="comments" name="comments" class="form-control"><?= htmlspecialchars($animal['comments'] ?? '') ?></textarea>
  </div>

  <button type="submit" class="btn btn-primary btn-full btn-lg"><?= t('save') ?></button>
  <a href="<?= $id ? "/animal-detail.php?id=$id" : '/animals.php' ?>" class="btn btn-secondary btn-full mt-12"><?= t('cancel') ?></a>
</form>
</div>

<script>
const herdsData = <?= json_encode($herds) ?>;

function toggleBreedingDate() {
  const status = document.getElementById('breeding_status').value;
  const group  = document.getElementById('breeding-date-group');
  group.style.display = status === 'pregnant' ? 'block' : 'none';
  if (status === 'pregnant') calcDueDate();
}

function calcDueDate() {
  const bd  = document.getElementById('breeding_date').value;
  const el  = document.getElementById('due-date-display');
  if (!bd) { el.textContent = ''; return; }
  const due = new Date(bd + 'T00:00:00');
  due.setDate(due.getDate() + 285);
  el.textContent = 'Expected calving: ' + due.toLocaleDateString();
}

document.getElementById('breeding_date')?.addEventListener('input', calcDueDate);

// Show on load if already pregnant
toggleBreedingDate();

function toggleStatusFields() {
  const status = document.getElementById('animal_status').value;
  const group  = document.getElementById('status-extra-group');
  group.style.display = (status === 'sold' || status === 'dead') ? 'block' : 'none';
  document.getElementById('status-date-label').textContent  = status === 'sold' ? 'Date Sold'  : 'Date of Death';
  document.getElementById('status-notes-label').textContent = status === 'sold' ? 'Sale Notes' : 'Cause / Notes';
}
toggleStatusFields();

function filterHerds() {
  const farmId = document.getElementById('farm_id').value;
  const sel    = document.getElementById('herd_id');
  sel.innerHTML = '<option value="">– Select Herd –</option>';
  herdsData.filter(h => !farmId || h.farm_id == farmId).forEach(h => {
    const opt = document.createElement('option');
    opt.value = h.id;
    opt.textContent = h.name;
    sel.appendChild(opt);
  });
}

// Auto-fill calf from mother's herd
<?php if ($isCalfMode): ?>
document.getElementById('mother_id').addEventListener('change', function() {
  const motherId = this.value;
  if (!motherId) return;
  fetch(`/api/animals.php?id=${motherId}`)
    .then(r => r.json())
    .then(res => {
      if (!res.success || !res.data) return;
      const m = res.data;
      if (m.herd_id) { document.getElementById('herd_id').value = m.herd_id; }
      if (m.farm_id) { document.getElementById('farm_id').value = m.farm_id; filterHerds(); document.getElementById('herd_id').value = m.herd_id; }
      // Auto-fill breeding bull as father (or show selection if multiple)
      fetch(`/api/herds.php?id=${m.herd_id}`)
        .then(r => r.json())
        .then(hr => {
          if (!hr.success || !hr.data) return;
          const bulls = hr.data.bulls || [];
          const fatherSel = document.getElementById('father_id');
          const fatherGroup = fatherSel.closest('.form-group');
          const oldPrompt = document.getElementById('bull-select-prompt');
          if (oldPrompt) oldPrompt.remove();
          if (bulls.length === 1) {
            fatherSel.value = bulls[0].id;
          } else if (bulls.length > 1) {
            fatherSel.innerHTML = '<option value="">– Select Father –</option>' +
              bulls.map(b => `<option value="${b.id}">${b.ear_tag}</option>`).join('');
            fatherSel.value = '';
            const prompt = document.createElement('div');
            prompt.id = 'bull-select-prompt';
            prompt.style.cssText = 'margin-top:6px;padding:8px 12px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;font-size:0.85rem;color:#856404;font-weight:600';
            prompt.textContent = 'Multiple bulls in this herd — please select the father above.';
            fatherGroup.appendChild(prompt);
          }
        });
    });
});
document.getElementById('dob').value = '<?= date('Y-m-d') ?>';
<?php endif; ?>

document.getElementById('animal-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const data = {
    ear_tag:         document.getElementById('ear_tag').value.trim(),
    rfid:            document.getElementById('rfid').value.trim(),
    sex:             document.getElementById('sex').value,
    dob:             document.getElementById('dob').value,
    breed:           document.getElementById('breed').value.trim(),
    category:        document.getElementById('category').value,
    farm_id:         document.getElementById('farm_id').value || null,
    herd_id:         document.getElementById('herd_id').value || null,
    mother_id:       document.getElementById('mother_id').value || null,
    father_id:       document.getElementById('father_id').value || null,
    breeding_status: document.getElementById('breeding_status').value,
    breeding_date:   document.getElementById('breeding_date').value || null,
    animal_status:   document.getElementById('animal_status').value,
    status_date:     document.getElementById('status_date').value || null,
    status_notes:    document.getElementById('status_notes').value.trim() || null,
    comments:        document.getElementById('comments').value.trim(),
  };
  if (!data.ear_tag) { alert('Ear tag is required.'); return; }

  const id     = <?= $id ?>;
  const method = id ? 'PUT' : 'POST';
  const url    = id ? `/api/animals.php?id=${id}` : '/api/animals.php';

  let res;
  try {
    const r = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
    res = await r.json();
  } catch(err) {
    alert('Server error saving animal. Check that the database category column has been updated.\n\nRun in phpMyAdmin:\nALTER TABLE animals MODIFY COLUMN category VARCHAR(50) NOT NULL DEFAULT \'breeding_cow\';');
    return;
  }
  if (res.success) {
    window.location = `/animal-detail.php?id=${res.data?.id || id}`;
  } else {
    alert(res.message || 'Error saving animal.');
  }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
