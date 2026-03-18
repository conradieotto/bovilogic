<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'summary';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/index.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('summary') ?></h1>
</header>

<div id="summary-content"><div class="page-loader" style="min-height:300px"><div class="spinner"></div></div></div>

<script>
fetch('/api/dashboard.php')
  .then(r=>r.json())
  .then(res=>{
    if(!res.success){document.getElementById('summary-content').innerHTML='<div class="empty-state"><p>Failed to load.</p></div>';return;}
    const d = res.data;
    document.getElementById('summary-content').innerHTML = `
      <div class="stat-grid">
        <div class="stat-card"><span class="stat-val">${d.total_animals}</span><span class="stat-label">Total Animals</span></div>
        <div class="stat-card alert"><span class="stat-val">${d.vaccines_overdue}</span><span class="stat-label">Vaccines Overdue</span></div>
        <div class="stat-card"><span class="stat-val">${d.vaccines_due}</span><span class="stat-label">Vaccines Due (7d)</span></div>
        <div class="stat-card"><span class="stat-val">${d.upcoming_calvings}</span><span class="stat-label">Upcoming Calvings</span></div>
      </div>

      <div class="section-header"><h2>Farm Summary</h2></div>
      <div class="list-card" style="margin:0 16px 16px">
        ${(d.farm_summary||[]).map(f=>`
          <a href="/camps.php?farm=${escHtml(f.id||'')}" class="list-item">
            <div class="item-body"><div class="item-title">${escHtml(f.name)}</div></div>
            <strong>${f.animal_count} animals</strong>
            <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </a>
        `).join('') || '<div class="p-16 text-muted text-sm">No farms yet.</div>'}
      </div>
    `;
  });

// Load category breakdown
fetch('/api/reports.php?type=monthly')
  .then(r=>r.json())
  .then(res=>{
    if(!res.success)return;
    const d = res.data;
    const existing = document.getElementById('summary-content');
    const catHtml = `
      <div class="section-header"><h2>By Category</h2></div>
      <div class="list-card" style="margin:0 16px 16px">
        ${(d.by_category||[]).map(c=>`
          <div class="list-item">
            <div class="item-body"><div class="item-title">${escHtml(c.category.replace(/_/g,' '))}</div></div>
            <strong>${c.cnt}</strong>
          </div>
        `).join('')}
      </div>
    `;
    existing.insertAdjacentHTML('beforeend', catHtml);
  });

function escHtml(s){const d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
