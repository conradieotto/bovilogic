<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'alerts';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/index.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('alerts') ?></h1>
</header>

<!-- Vaccinations Overdue -->
<div class="section-header"><h2>Vaccines Overdue</h2></div>
<div id="vacc-overdue" class="list-card" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<!-- Vaccinations Due Soon -->
<div class="section-header"><h2>Due This Week</h2></div>
<div id="vacc-due" class="list-card" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<!-- Upcoming Calvings -->
<div class="section-header"><h2>Upcoming Calvings</h2></div>
<div id="calvings" class="list-card" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<script>
function renderVacc(items, el) {
  if (!items.length) { el.innerHTML='<div class="p-16 text-muted text-sm">None.</div>'; return; }
  const today = new Date();
  el.innerHTML = items.map(v => {
    const overdue = new Date(v.due_date) < today;
    return `<a href="${v.animal_id?'/animal-detail.php?id='+v.animal_id:'/herds.php'}" class="list-item">
      <div class="item-body">
        <div class="item-title">${escHtml(v.product)}</div>
        <div class="item-sub">Due: ${v.due_date}</div>
      </div>
      <span class="badge ${overdue?'badge-red':'badge-amber'}">${overdue?'Overdue':'Due soon'}</span>
    </a>`;
  }).join('');
}

fetch('/api/vaccinations.php?overdue=1')
  .then(r=>r.json())
  .then(res => renderVacc(res.data||[], document.getElementById('vacc-overdue')));

fetch('/api/vaccinations.php?due_soon=1')
  .then(r=>r.json())
  .then(res => renderVacc(res.data||[], document.getElementById('vacc-due')));

// Pregnant animals
fetch('/api/animals.php?status=active')
  .then(r=>r.json())
  .then(res => {
    const pregnant = (res.data||[]).filter(a=>a.breeding_status==='pregnant');
    const el = document.getElementById('calvings');
    if (!pregnant.length) { el.innerHTML='<div class="p-16 text-muted text-sm">No pregnant animals.</div>'; return; }
    el.innerHTML = pregnant.map(a=>`
      <a href="/animal-detail.php?id=${a.id}" class="list-item">
        <div class="item-body">
          <div class="item-title">${escHtml(a.ear_tag)}</div>
          <div class="item-sub">${escHtml(a.herd_name||'')} · ${escHtml(a.breed||'')}</div>
        </div>
        <span class="badge badge-blue">Pregnant</span>
      </a>
    `).join('');
  });

function escHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
