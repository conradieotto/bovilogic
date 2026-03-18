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
  <a href="<?= $id ? "/animal-detail.php?id=$id" : '/animals.php' ?>" class="btn-icon">
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
      <?php $cats = ['breeding_bull','cow','calf','open_heifer','heifer','weaner','steer','ox']; ?>
      <?php foreach ($cats as $c): ?>
      <option value="<?= $c ?>" <?= ($animal['category'] ?? ($isCalfMode ? 'calf' : 'calf')) === $c ? 'selected' : '' ?>><?= t('cat_'.$c) ?></option>
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
    <select id="breeding_status" name="breeding_status" class="form-control">
      <option value="open"     <?= ($animal['breeding_status'] ?? 'open') === 'open' ? 'selected' : '' ?>><?= t('bs_open') ?></option>
      <option value="pregnant" <?= ($animal['breeding_status'] ?? '') === 'pregnant' ? 'selected' : '' ?>><?= t('bs_pregnant') ?></option>
      <option value="calved"   <?= ($animal['breeding_status'] ?? '') === 'calved' ? 'selected' : '' ?>><?= t('bs_calved') ?></option>
    </select>
  </div>
  <div class="form-group">
    <label class="form-label"><?= t('animal_status') ?></label>
    <select id="animal_status" name="animal_status" class="form-control">
      <option value="active" <?= ($animal['animal_status'] ?? 'active') === 'active' ? 'selected' : '' ?>><?= t('as_active') ?></option>
      <option value="sold"   <?= ($animal['animal_status'] ?? '') === 'sold' ? 'selected' : '' ?>><?= t('as_sold') ?></option>
      <option value="dead"   <?= ($animal['animal_status'] ?? '') === 'dead' ? 'selected' : '' ?>><?= t('as_dead') ?></option>
    </select>
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
      // Auto-fill breeding bull as father
      fetch(`/api/herds.php?id=${m.herd_id}`)
        .then(r => r.json())
        .then(hr => {
          if (hr.success && hr.data?.breeding_bull_id) {
            document.getElementById('father_id').value = hr.data.breeding_bull_id;
          }
        });
    });
});
document.getElementById('category').value = 'calf';
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
    animal_status:   document.getElementById('animal_status').value,
    comments:        document.getElementById('comments').value.trim(),
  };
  if (!data.ear_tag) { alert('Ear tag is required.'); return; }

  const id     = <?= $id ?>;
  const method = id ? 'PUT' : 'POST';
  const url    = id ? `/api/animals.php?id=${id}` : '/api/animals.php';

  const res = await fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) }).then(r => r.json());
  if (res.success) {
    window.location = `/animal-detail.php?id=${res.data?.id || id}`;
  } else {
    alert(res.message || 'Error saving animal.');
  }
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
