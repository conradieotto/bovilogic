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
const T = <?= json_encode([
  'total_active'      => t('total_animals'),
  'sales'             => t('sales'),
  'deaths'            => t('as_dead'),
  'newborns'          => t('born_label'),
  'purchased'         => t('purchased_in'),
  'by_category'       => t('by_category'),
  'farm_summary'      => t('farm_summary'),
  'total'             => t('total'),
  'animals_count'     => t('animals_count'),
  'no_data'           => t('no_data'),
  'as_sold'           => t('as_sold'),
  'as_dead'           => t('as_dead'),
  'born_label'        => t('born_label'),
  'avg_weight'        => 'Avg Weight',
  'pregnant'          => t('bs_pregnant'),
  'vacc_overdue'      => t('vaccines_overdue'),
  'vacc_due'          => t('vaccines_due_7d'),
  'cat_breeding_bull'      => t('cat_breeding_bull'),
  'cat_breeding_cow'       => t('cat_breeding_cow'),
  'cat_c_grade_cow'        => t('cat_c_grade_cow'),
  'cat_bull_calf'          => t('cat_bull_calf'),
  'cat_heifer_calf'        => t('cat_heifer_calf'),
  'cat_weaner'             => t('cat_weaner'),
  'cat_replacement_heifer' => t('cat_replacement_heifer'),
]) ?>;

const CAT_LABELS = {
  breeding_bull:      T.cat_breeding_bull,
  breeding_cow:       T.cat_breeding_cow,
  c_grade_cow:        T.cat_c_grade_cow,
  bull_calf:          T.cat_bull_calf,
  heifer_calf:        T.cat_heifer_calf,
  weaner:             T.cat_weaner,
  replacement_heifer: T.cat_replacement_heifer,
};
function catLabel(c){ return CAT_LABELS[c] || c.replace(/_/g,' '); }

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
      const dead = (d.dead_sold||[]).filter(a=>a.animal_status==='dead');
      const sold = (d.dead_sold||[]).filter(a=>a.animal_status==='sold');
      const purchases = d.purchases || [];
      const newborns  = d.newborns  || [];

      el.innerHTML = `
        <div class="stat-grid" style="padding:0 0 16px">
          <div class="stat-card"><span class="stat-val">${d.total_active}</span><span class="stat-label">${T.total_active}</span></div>
          <div class="stat-card"><span class="stat-val">${sold.length}</span><span class="stat-label">${T.sales}</span></div>
          <div class="stat-card"><span class="stat-val">${dead.length}</span><span class="stat-label">${T.deaths}</span></div>
          <div class="stat-card"><span class="stat-val">${newborns.length}</span><span class="stat-label">${T.newborns}</span></div>
        </div>

        <div class="section-header"><h2>${T.by_category}</h2></div>
        <div class="list-card mb-16">${(d.by_category||[]).map(c=>`
          <div class="list-item">
            <div class="item-body"><div class="item-title">${escHtml(catLabel(c.category))}</div></div>
            <strong>${c.cnt}</strong>
          </div>`).join('') || `<div class="p-16 text-muted text-sm">${T.no_data}</div>`}</div>

        <div class="section-header"><h2>${T.farm_summary}</h2></div>
        <div class="list-card mb-16">${(d.farm_summary||[]).map(f=>`
          <div class="list-item">
            <div class="item-body"><div class="item-title">${escHtml(f.name)}</div></div>
            <strong>${f.animal_count} ${T.animals_count}</strong>
          </div>`).join('') || `<div class="p-16 text-muted text-sm">${T.no_data}</div>`}</div>

        ${sold.length ? `
        <div class="section-header"><h2>${T.sales} (R ${(d.sales_total||0).toFixed(2)})</h2></div>
        <div class="list-card mb-16">${sold.map(s=>`
          <a href="/animal-detail.php?id=${s.id}" class="list-item">
            <div class="item-body">
              <div class="item-title">${escHtml(s.ear_tag)}</div>
              <div class="item-sub">${escHtml(catLabel(s.category))} · ${s.status_date}${s.status_notes?' · '+escHtml(s.status_notes):''}</div>
            </div>
            <span style="font-size:11px;font-weight:700;color:#1565C0;background:#e3f2fd;padding:2px 8px;border-radius:20px">${T.as_sold.toUpperCase()}</span>
          </a>`).join('')}</div>` : ''}

        ${purchases.length ? `
        <div class="section-header"><h2>${T.purchased}</h2></div>
        <div class="list-card mb-16">
          ${purchases.map(p=>`
          <div class="list-item">
            <div class="item-body">
              <div class="item-title">${escHtml(catLabel(p.category))} · ${p.total_purchased} ${T.animals_count}</div>
              <div class="item-sub">${escHtml(p.seller||'–')} · ${p.date_purchased}</div>
            </div>
            <strong>R ${parseFloat(p.price_zar).toLocaleString()}</strong>
          </div>`).join('')}
          <div class="list-item" style="background:var(--surface-2,rgba(255,255,255,0.05))">
            <div class="item-body"><div class="item-title" style="font-weight:700">${T.total}</div></div>
            <strong>${purchases.reduce((s,p)=>s+parseInt(p.total_purchased),0)} ${T.animals_count}</strong>
          </div>
        </div>` : ''}

        ${newborns.length ? `
        <div class="section-header"><h2>${T.newborns} (${newborns.length})</h2></div>
        <div class="list-card mb-16">${newborns.map(n=>`
          <a href="/animal-detail.php?id=${n.id}" class="list-item">
            <div class="item-body">
              <div class="item-title">${escHtml(n.ear_tag)}</div>
              <div class="item-sub">${escHtml(catLabel(n.category))}${n.dam_tag?' · Dam: '+escHtml(n.dam_tag):''} · ${n.dob}</div>
            </div>
            <span style="font-size:11px;font-weight:700;color:#2e7d32;background:#e8f5e9;padding:2px 8px;border-radius:20px">${T.born_label.toUpperCase()}</span>
          </a>`).join('')}</div>` : ''}

        ${dead.length ? `
        <div class="section-header"><h2>${T.deaths} (${dead.length})</h2></div>
        <div class="list-card mb-16">${dead.map(a=>`
          <a href="/animal-detail.php?id=${a.id}" class="list-item">
            <div class="item-body">
              <div class="item-title">${escHtml(a.ear_tag)}</div>
              <div class="item-sub">${escHtml(catLabel(a.category))} · ${a.status_date}${a.status_notes?' · '+escHtml(a.status_notes):''}</div>
            </div>
            <span style="font-size:11px;font-weight:700;color:#b71c1c;background:#ffebee;padding:2px 8px;border-radius:20px">${T.as_dead.toUpperCase()}</span>
          </a>`).join('')}</div>` : ''}

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
          <div class="stat-card"><span class="stat-val">${d.total_active}</span><span class="stat-label">${T.total_active}</span></div>
          <div class="stat-card"><span class="stat-val">${d.pregnant_cows}</span><span class="stat-label">${T.pregnant}</span></div>
          <div class="stat-card alert"><span class="stat-val">${d.vaccinations_overdue}</span><span class="stat-label">${T.vacc_overdue}</span></div>
          <div class="stat-card"><span class="stat-val">${d.vaccinations_due}</span><span class="stat-label">${T.vacc_due}</span></div>
        </div>
        <div class="section-header"><h2>${T.by_category}</h2></div>
        <div class="list-card mb-16">${(d.by_category||[]).map(c=>`
          <div class="list-item">
            <div class="item-body"><div class="item-title">${escHtml(catLabel(c.category))}</div></div>
            <strong>${c.cnt}</strong>
          </div>`).join('') || `<div class="p-16 text-muted text-sm">${T.no_data}</div>`}</div>
        <div class="section-header"><h2>${T.avg_weight}</h2></div>
        <div class="list-card mb-16">${(d.avg_weight_by_cat||[]).map(w=>`
          <div class="list-item">
            <div class="item-body"><div class="item-title">${escHtml(catLabel(w.category))}</div></div>
            <strong>${w.avg_weight} kg</strong>
          </div>`).join('') || `<div class="p-16 text-muted text-sm">${T.no_data}</div>`}</div>
      `;
    });
}

function exportCSV(type, from, to) {
  window.location = `/api/reports.php?type=${type}&from=${from}&to=${to}&format=csv`;
}
function escHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
