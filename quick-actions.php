<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
requireRole('super_admin');
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'quick_actions';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/index.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('quick_actions') ?></h1>
</header>

<div style="padding:16px;">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

  <a href="/animal-form.php" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    <?= t('add_animal') ?>
  </a>

  <a href="/animal-form.php?calf=1" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
    <?= t('add_calf') ?>
  </a>

  <button class="dash-btn" onclick="openModal('quick-weight-modal')">
    <svg viewBox="0 0 24 24"><path d="M12 3C8.59 3 5.69 4.07 3.8 6H20.2C18.31 4.07 15.41 3 12 3zm9 4H3C2.45 7 2 7.45 2 8v2c0 .55.45 1 1 1h1v10c0 .55.45 1 1 1h14c.55 0 1-.45 1-1V11h1c.55 0 1-.45 1-1V8c0-.55-.45-1-1-1z"/></svg>
    <?= t('add_weight') ?>
  </button>

  <button class="dash-btn" onclick="openModal('quick-treatment-modal')">
    <svg viewBox="0 0 24 24"><path d="M6.5 10h-2v5h2v-5zm4 0h-2v5h2v-5zm8.5 7H4v2h15v-2zm-4.5-7h-2v5h2v-5zM11.5 1L2 6v2h19V6l-9.5-5z"/></svg>
    <?= t('add_treatment') ?>
  </button>

  <button class="dash-btn" onclick="openModal('quick-vacc-modal')">
    <svg viewBox="0 0 24 24"><path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3zm-1 13-3-3 1.41-1.41L11 12.17l4.59-4.58L17 9l-6 6z"/></svg>
    <?= t('add_vaccination') ?>
  </button>

  <button class="dash-btn" onclick="openModal('quick-sale-modal')">
    <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1H8.32c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
    <?= t('add_sale') ?>
  </button>

  <button class="dash-btn" onclick="openModal('quick-event-modal')">
    <svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
    <?= t('add_event') ?>
  </button>

  <button class="dash-btn" onclick="openModal('quick-move-modal')">
    <svg viewBox="0 0 24 24"><path d="M10 9h4V6h3l-5-5-5 5h3v3zm-1 1H6V7l-5 5 5 5v-3h3v-4zm14 2l-5-5v3h-3v4h3v3l5-5zm-9 3h-4v3H7l5 5 5-5h-3v-3z"/></svg>
    <?= t('move_herd') ?>
  </button>

</div>
</div>

<!-- Quick Weight Modal -->
<div class="modal-overlay" id="quick-weight-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Quick Add Weight</div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Animal (Ear Tag) *</label><input type="text" id="qw-tag" class="form-control" placeholder="Type ear tag..."></div>
      <div class="form-group"><label class="form-label">Weight (kg) *</label><input type="number" id="qw-kg" class="form-control" step="0.1"></div>
      <div class="form-group"><label class="form-label">Date</label><input type="date" id="qw-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('quick-weight-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveQuickWeight()">Save</button>
    </div>
  </div>
</div>

<!-- Quick Sale Modal -->
<div class="modal-overlay" id="quick-sale-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Record Sale</div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Animal (Ear Tag) *</label><input type="text" id="qs-tag" class="form-control"></div>
      <div class="form-group"><label class="form-label">Price (R)</label><input type="number" id="qs-price" class="form-control" step="0.01"></div>
      <div class="form-group"><label class="form-label">Buyer</label><input type="text" id="qs-buyer" class="form-control"></div>
      <div class="form-group"><label class="form-label">Date</label><input type="date" id="qs-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('quick-sale-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveQuickSale()">Save</button>
    </div>
  </div>
</div>

<!-- Quick Event Modal -->
<div class="modal-overlay" id="quick-event-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Add Event</div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Animal (Ear Tag) *</label><input type="text" id="qe-tag" class="form-control"></div>
      <div class="form-group"><label class="form-label">Event Type</label>
        <select id="qe-type" class="form-control">
          <option>Branding</option><option>Dehorning</option><option>Castration</option><option>Weaning</option><option>Pregnancy Test</option><option>Other</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Date</label><input type="date" id="qe-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('quick-event-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveQuickEvent()">Save</button>
    </div>
  </div>
</div>

<!-- Move Herd Modal -->
<div class="modal-overlay" id="quick-move-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Move Herd</div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Herd *</label><select id="qm-herd" class="form-control"><option value="">Loading...</option></select></div>
      <div class="form-group"><label class="form-label">To Camp *</label><select id="qm-camp" class="form-control"><option value="">Loading...</option></select></div>
      <div class="form-group"><label class="form-label">Move Date</label><input type="date" id="qm-date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('quick-move-modal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveHerdMove()">Move</button>
    </div>
  </div>
</div>

<script>
// Load herds and camps for move modal
Promise.all([fetch('/api/herds.php').then(r=>r.json()), fetch('/api/camps.php').then(r=>r.json())])
  .then(([hr, cr]) => {
    const hs = document.getElementById('qm-herd');
    hs.innerHTML = '<option value="">– Select Herd –</option>' +
      (hr.data||[]).map(h=>`<option value="${h.id}">${h.name}</option>`).join('');
    const cs = document.getElementById('qm-camp');
    cs.innerHTML = '<option value="">– Select Camp –</option>' +
      (cr.data||[]).map(c=>`<option value="${c.id}">${c.name} (${c.farm_name||''})</option>`).join('');
  });

async function lookupAnimal(tag) {
  const res = await fetch(`/api/animals.php?q=${encodeURIComponent(tag)}`).then(r=>r.json());
  return res.data?.find(a => a.ear_tag.toLowerCase() === tag.toLowerCase()) || null;
}

async function saveQuickWeight() {
  const tag = document.getElementById('qw-tag').value.trim();
  const kg  = document.getElementById('qw-kg').value;
  if (!tag || !kg) { alert('Animal tag and weight required.'); return; }
  const animal = await lookupAnimal(tag);
  if (!animal) { alert(`Animal "${tag}" not found.`); return; }
  const res = await fetch('/api/weights.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ animal_id: animal.id, weight_kg: parseFloat(kg), weigh_date: document.getElementById('qw-date').value })
  }).then(r=>r.json());
  if (res.success) { showToast('Weight saved'); closeModal('quick-weight-modal'); }
  else alert(res.message);
}

async function saveQuickSale() {
  const tag = document.getElementById('qs-tag').value.trim();
  if (!tag) { alert('Animal tag required.'); return; }
  const animal = await lookupAnimal(tag);
  if (!animal) { alert(`Animal "${tag}" not found.`); return; }
  const res = await fetch('/api/sales.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      animal_id: animal.id,
      sale_date: document.getElementById('qs-date').value,
      price: document.getElementById('qs-price').value || 0,
      buyer: document.getElementById('qs-buyer').value,
    })
  }).then(r=>r.json());
  if (res.success) { showToast('Sale recorded'); closeModal('quick-sale-modal'); }
  else alert(res.message);
}

async function saveQuickEvent() {
  const tag = document.getElementById('qe-tag').value.trim();
  if (!tag) { alert('Animal tag required.'); return; }
  const animal = await lookupAnimal(tag);
  if (!animal) { alert(`Animal "${tag}" not found.`); return; }
  const res = await fetch('/api/events.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      animal_id: animal.id,
      event_type: document.getElementById('qe-type').value,
      event_date: document.getElementById('qe-date').value,
    })
  }).then(r=>r.json());
  if (res.success) { showToast('Event added'); closeModal('quick-event-modal'); }
  else alert(res.message);
}

async function saveHerdMove() {
  const herdId = document.getElementById('qm-herd').value;
  const campId = document.getElementById('qm-camp').value;
  const date   = document.getElementById('qm-date').value;
  if (!herdId || !campId) { alert('Herd and camp required.'); return; }
  // Get current camp of herd
  const herdRes = await fetch(`/api/herds.php?id=${herdId}`).then(r=>r.json());
  const fromCamp = herdRes.data?.camp_id || null;
  // Record movement
  const res = await fetch('/api/movements.php', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ herd_id: herdId, from_camp_id: fromCamp, to_camp_id: campId, move_date: date })
  }).then(r=>r.json());
  if (res.success) {
    // Update herd camp
    await fetch(`/api/herds.php?id=${herdId}`, {
      method:'PUT', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ ...herdRes.data, camp_id: campId })
    });
    showToast('Herd moved');
    closeModal('quick-move-modal');
  } else alert(res.message);
}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
