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

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-bolt"></i> <?= t('quick_actions') ?></h1>
</div>

<div style="padding:16px;">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

  <a href="/farms.php?from=quick" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M19 9.3V4h-3v2.6L12 3 2 12h3v8h5v-5h4v5h5v-8h3l-3-2.7z"/></svg>
    <?= t('farms') ?>
  </a>

  <a href="/herds.php?from=quick" class="dash-btn">
    <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="15" cy="8" r="3"/><path d="M1 18v-1c0-2.2 3.6-4 8-4s8 1.8 8 4v1H1zm14.3-4c2.5.4 4.7 1.7 4.7 3v1h-4v-1c0-1.1-.7-2.1-1.8-2.9l1.1-.1z"/></svg>
    <?= t('herds') ?>
  </a>

  <a href="/animals.php?from=quick" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
    <?= t('animals') ?>
  </a>

<a href="/animal-form.php?calf=1&from=quick" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
    <?= t('add_calf') ?>
  </a>

  <a href="/calf-history.php" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
    <?= t('calf_history') ?>
  </a>

  <button class="dash-btn" onclick="toggleEventMenu()" id="btn-add-event">
    <svg viewBox="0 0 24 24"><path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/></svg>
    <?= t('add_event') ?>
  </button>

  <a href="/add-sale.php" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1H8.32c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
    <?= t('add_sale') ?>
  </a>

  <a href="/add-purchase.php" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
    <?= t('add_purchase') ?>
  </a>

  <a href="/move-herd.php" class="dash-btn">
    <svg viewBox="0 0 24 24"><path d="M10 9h4V6h3l-5-5-5 5h3v3zm-1 1H6V7l-5 5 5 5v-3h3v-4zm14 2l-5-5v3h-3v4h3v3l5-5zm-9 3h-4v3H7l5 5 5-5h-3v-3z"/></svg>
    <?= t('move_herd') ?>
  </a>

  <button class="dash-btn" onclick="openModal('calving-calc-modal')">
    <svg viewBox="0 0 24 24"><path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/></svg>
    <?= t('calving_calculator') ?>
  </button>

</div>

<!-- Calving Calculator Modal -->
<div class="modal-overlay" id="calving-calc-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title"><?= t('calving_due_calculator') ?></div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label"><?= t('conception_date') ?></label>
        <input type="date" id="calc-conception" class="form-control" oninput="calcCalvingDate()">
      </div>
      <div id="calc-result" style="display:none;margin-top:16px;padding:16px;background:#e8f5e9;border-radius:12px;text-align:center">
        <div style="font-size:0.8rem;color:#555;margin-bottom:4px"><?= t('expected_calving') ?></div>
        <div id="calc-due-date" style="font-size:1.6rem;font-weight:700;color:#2e7d32"></div>
        <div id="calc-days-left" style="font-size:0.85rem;color:#555;margin-top:4px"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary btn-full" onclick="closeModal('calving-calc-modal');document.getElementById('calc-conception').value='';document.getElementById('calc-result').style.display='none'">Close</button>
    </div>
  </div>
</div>

<!-- Add Event submenu -->
<div id="event-menu" style="display:none;margin-top:12px">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
    <a href="/add-vaccination.php" class="dash-btn">
      <svg viewBox="0 0 24 24"><path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3zm-1 13-3-3 1.41-1.41L11 12.17l4.59-4.58L17 9l-6 6z"/></svg>
      <?= t('add_vaccination') ?>
    </a>
    <a href="/animals.php" class="dash-btn">
      <svg viewBox="0 0 24 24"><path d="M12 3C8.59 3 5.69 4.07 3.8 6H20.2C18.31 4.07 15.41 3 12 3zm9 4H3C2.45 7 2 7.45 2 8v2c0 .55.45 1 1 1h1v10c0 .55.45 1 1 1h14c.55 0 1-.45 1-1V11h1c.55 0 1-.45 1-1V8c0-.55-.45-1-1-1z"/></svg>
      <?= t('add_weight') ?>
    </a>
    <a href="/animals.php" class="dash-btn">
      <svg viewBox="0 0 24 24"><path d="M6.5 10h-2v5h2v-5zm4 0h-2v5h2v-5zm8.5 7H4v2h15v-2zm-4.5-7h-2v5h2v-5zM11.5 1L2 6v2h19V6l-9.5-5z"/></svg>
      <?= t('add_treatment') ?>
    </a>
    <a href="/pregnancy-test.php" class="dash-btn">
      <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
      <?= t('pregnancy_test') ?>
    </a>
  </div>
</div>
</div>

<script>
const TC = <?= json_encode([
  'days_from_today' => t('days_from_today'),
  'due_today'       => t('due_today'),
  'days_overdue'    => t('days_overdue'),
]) ?>;

function calcCalvingDate() {
  const val = document.getElementById('calc-conception').value;
  if (!val) { document.getElementById('calc-result').style.display = 'none'; return; }
  const due = new Date(val + 'T00:00:00');
  due.setDate(due.getDate() + 285);
  const today = new Date(); today.setHours(0,0,0,0);
  const daysLeft = Math.round((due - today) / 86400000);
  document.getElementById('calc-due-date').textContent = due.toLocaleDateString(undefined, {day:'numeric', month:'long', year:'numeric'});
  document.getElementById('calc-days-left').textContent = daysLeft > 0
    ? `${daysLeft} ${TC.days_from_today}`
    : daysLeft === 0 ? TC.due_today : `${Math.abs(daysLeft)} ${TC.days_overdue}`;
  document.getElementById('calc-result').style.backgroundColor = daysLeft < 0 ? '#fff3cd' : '#e8f5e9';
  document.getElementById('calc-due-date').style.color = daysLeft < 0 ? '#e65100' : '#2e7d32';
  document.getElementById('calc-result').style.display = 'block';
}

function toggleEventMenu() {
  const menu = document.getElementById('event-menu');
  const btn  = document.getElementById('btn-add-event');
  const open = menu.style.display !== 'none';
  menu.style.display = open ? 'none' : 'block';
  btn.style.background = open ? '' : 'var(--green-light, #e8f5e9)';
}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
