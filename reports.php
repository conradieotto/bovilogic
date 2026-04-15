<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'nav_reports';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-chart-bar"></i> <?= t('reports') ?></h1>
</div>

<!-- Report type tabs -->
<div class="tabs">
  <button class="tab-btn active" data-tab="monthly"><?= t('monthly_report') ?></button>
  <button class="tab-btn" data-tab="herd"><?= t('herd_report') ?></button>
</div>

<!-- Monthly Report -->
<div id="tab-monthly" class="tab-panel active" style="padding:16px">
  <div class="card mb-16">
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label"><?= t('from_date') ?></label>
          <input type="date" id="m-from" class="form-control" value="<?= date('Y-m-01') ?>">
        </div>
        <div class="form-group">
          <label class="form-label"><?= t('to_date') ?></label>
          <input type="date" id="m-to" class="form-control" value="<?= date('Y-m-t') ?>">
        </div>
      </div>
      <button class="btn btn-primary btn-full" onclick="loadMonthly()">Generate Report</button>
    </div>
  </div>
  <div id="monthly-output"></div>
</div>

<!-- Herd Report -->
<div id="tab-herd" class="tab-panel" style="padding:16px">
  <div class="card mb-16">
    <div class="card-body">
      <div class="form-group">
        <label class="form-label"><?= t('herd') ?></label>
        <select id="h-herd" class="form-control">
          <option value="">– Select Herd –</option>
        </select>
      </div>
      <button class="btn btn-primary btn-full" onclick="loadHerdReport()">Generate Report</button>
    </div>
  </div>
  <div id="herd-output"></div>
</div>

<script>
// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});

// Load herds for dropdown
fetch('/api/herds.php').then(r=>r.json()).then(res=>{
  const sel = document.getElementById('h-herd');
  (res.data||[]).forEach(h=>{
    const o = document.createElement('option');
    o.value = h.id; o.textContent = h.name;
    sel.appendChild(o);
  });
});

function loadMonthly() {
  const from = document.getElementById('m-from').value;
  const to   = document.getElementById('m-to').value;
  const el   = document.getElementById('monthly-output');
  el.innerHTML = '<div class="page-loader"><div class="spinner"></div></div>';

  fetch(`/api/reports.php?type=monthly&from=${from}&to=${to}`)
    .then(r=>r.json())
    .then(res => {
      if (!res.success) { el.innerHTML='<p class="text-muted">Failed to load report.</p>'; return; }
      const d = res.data;
      el.innerHTML = `
        <div class="stat-grid" style="padding:0 0 16px">
          <div class="stat-card"><span class="stat-val">${d.total_active}</span><span class="stat-label">Total Active</span></div>
          <div class="stat-card"><span class="stat-val">${d.sales?.length||0}</span><span class="stat-label">Sales</span></div>
          <div class="stat-card"><span class="stat-val">${d.mortality?.length||0}</span><span class="stat-label">Deaths</span></div>
          <div class="stat-card"><span class="stat-val">${d.newborns?.length||0}</span><span class="stat-label">Newborns</span></div>
        </div>

        <div class="section-header"><h2>By Category</h2></div>
        <div class="list-card mb-16">${(d.by_category||[]).map(c=>`
          <div class="list-item"><div class="item-body"><div class="item-title">${escHtml(c.category)}</div></div><strong>${c.cnt}</strong></div>
        `).join('') || '<div class="empty-state"><p>No data.</p></div>'}</div>

        <div class="section-header"><h2>Farm Summary</h2></div>
        <div class="list-card mb-16">${(d.farm_summary||[]).map(f=>`
          <div class="list-item"><div class="item-body"><div class="item-title">${escHtml(f.name)}</div></div><strong>${f.animal_count}</strong></div>
        `).join('') || '<div class="empty-state"><p>No farms.</p></div>'}</div>

        ${d.sales?.length ? `
        <div class="section-header"><h2>Sales (R ${d.sales_total?.toFixed(2)||'0.00'})</h2></div>
        <div class="list-card mb-16">${d.sales.map(s=>`
          <div class="list-item"><div class="item-body">
            <div class="item-title">${escHtml(s.ear_tag||s.animal_id)}</div>
            <div class="item-sub">${s.sale_date} · ${escHtml(s.buyer||'–')}</div>
          </div><strong>R ${parseFloat(s.price).toFixed(2)}</strong></div>
        `).join('')}</div>` : ''}

        ${d.newborns?.length ? `
        <div class="section-header"><h2>Newborns (${d.newborns.length})</h2></div>
        <div class="list-card mb-16">${d.newborns.map(n=>`
          <div class="list-item"><div class="item-body">
            <div class="item-title">${escHtml(n.ear_tag)}</div>
            <div class="item-sub">${n.dob} · Dam: ${escHtml(n.dam_tag||'–')}</div>
          </div></div>
        `).join('')}</div>` : ''}

        <button class="btn btn-secondary btn-full" onclick="exportCSV('monthly','${from}','${to}')">Export CSV</button>
      `;
    });
}

function loadHerdReport() {
  const herdId = document.getElementById('h-herd').value;
  if (!herdId) { alert('Select a herd.'); return; }
  const el = document.getElementById('herd-output');
  el.innerHTML = '<div class="page-loader"><div class="spinner"></div></div>';

  fetch(`/api/reports.php?type=herd&herd_id=${herdId}`)
    .then(r=>r.json())
    .then(res => {
      if (!res.success) { el.innerHTML='<p class="text-muted">Failed.</p>'; return; }
      const d = res.data;
      el.innerHTML = `
        <div class="stat-grid" style="padding:0 0 16px">
          <div class="stat-card"><span class="stat-val">${d.total_active}</span><span class="stat-label">Animals</span></div>
          <div class="stat-card"><span class="stat-val">${d.pregnant_cows}</span><span class="stat-label">Pregnant</span></div>
          <div class="stat-card alert"><span class="stat-val">${d.vaccinations_overdue}</span><span class="stat-label">Vacc Overdue</span></div>
          <div class="stat-card"><span class="stat-val">${d.vaccinations_due}</span><span class="stat-label">Vacc Due</span></div>
        </div>
        <div class="section-header"><h2>Category Breakdown</h2></div>
        <div class="list-card mb-16">${(d.by_category||[]).map(c=>`
          <div class="list-item"><div class="item-body"><div class="item-title">${escHtml(c.category)}</div></div><strong>${c.cnt}</strong></div>
        `).join('')}</div>
        <div class="section-header"><h2>Avg Weight by Category</h2></div>
        <div class="list-card mb-16">${(d.avg_weight_by_cat||[]).map(w=>`
          <div class="list-item"><div class="item-body"><div class="item-title">${escHtml(w.category)}</div></div><strong>${w.avg_weight} kg</strong></div>
        `).join('') || '<div class="p-16 text-muted text-sm">No weight data.</div>'}</div>
      `;
    });
}

function exportCSV(type, from, to) {
  window.location = `/api/reports.php?type=${type}&from=${from}&to=${to}&format=csv`;
}
function escHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
