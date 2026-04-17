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

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-calendar-days"></i> <?= t('summary') ?></h1>
</div>

<div class="tabs">
  <button class="tab-btn active" data-tab="summary-tab"><?= t('summary') ?></button>
  <button class="tab-btn" data-tab="reports-tab"><?= t('reports') ?></button>
</div>

<!-- Summary Tab -->
<div id="summary-tab" class="tab-panel active">
  <div style="padding:12px 16px 0">
    <input type="month" id="summary-month" class="form-control" style="max-width:200px"
           value="<?= date('Y-m') ?>">
  </div>
  <div id="summary-content"><div class="page-loader" style="min-height:300px"><div class="spinner"></div></div></div>
</div>

<!-- Reports Tab -->
<div id="reports-tab" class="tab-panel">
  <div class="tabs" style="margin:8px 16px 0;border-bottom:none">
    <button class="rep-tab-btn active" data-rep="rep-monthly"><?= t('monthly_report') ?></button>
    <button class="rep-tab-btn" data-rep="rep-herd"><?= t('herd_report') ?></button>
  </div>

  <div id="rep-monthly" class="rep-panel active" style="padding:16px">
    <div class="card mb-16">
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><?= t('from_date') ?></label>
            <input type="date" id="r-from" class="form-control" value="<?= date('Y-m-01') ?>">
          </div>
          <div class="form-group">
            <label class="form-label"><?= t('to_date') ?></label>
            <input type="date" id="r-to" class="form-control" value="<?= date('Y-m-t') ?>">
          </div>
        </div>
        <button class="btn btn-primary btn-full" onclick="loadMonthlyReport()"><?= t('generate_report') ?></button>
      </div>
    </div>
    <div id="rep-monthly-output"></div>
  </div>

  <div id="rep-herd" class="rep-panel" style="padding:16px;display:none">
    <div class="card mb-16">
      <div class="card-body">
        <div class="form-group">
          <label class="form-label"><?= t('herd') ?></label>
          <select id="r-herd" class="form-control">
            <option value="">– <?= t('herd') ?> –</option>
          </select>
        </div>
        <button class="btn btn-primary btn-full" onclick="loadHerdReport()"><?= t('generate_report') ?></button>
      </div>
    </div>
    <div id="rep-herd-output"></div>
  </div>
</div>

<script>
const T = <?= json_encode([
  'total_animals'    => t('total_animals'),
  'vaccines_overdue' => t('vaccines_overdue'),
  'vaccines_due_7d'  => t('vaccines_due_7d'),
  'upcoming_calvings'=> t('upcoming_calvings'),
  'farm_summary'     => t('farm_summary'),
  'by_category'      => t('by_category'),
  'sold_in'          => t('sold_in'),
  'born_in'          => t('born_in'),
  'purchased_in'     => t('purchased_in'),
  'deaths_in'        => t('deaths_in'),
  'expected_label'   => t('expected_label'),
  'no_breeding_date' => t('no_breeding_date'),
  'no_farms_yet'     => t('no_farms_yet'),
  'no_data'          => t('no_data'),
  'animals_count'    => t('animals_count'),
  'total'            => t('total'),
  'as_sold'          => t('as_sold'),
  'as_dead'          => t('as_dead'),
  'born_label'       => t('born_label'),
  'overdue'          => t('overdue'),
  'cat_breeding_bull'      => t('cat_breeding_bull'),
  'cat_breeding_cow'       => t('cat_breeding_cow'),
  'cat_c_grade_cow'        => t('cat_c_grade_cow'),
  'cat_bull_calf'          => t('cat_bull_calf'),
  'cat_heifer_calf'        => t('cat_heifer_calf'),
  'cat_weaner'             => t('cat_weaner'),
  'cat_replacement_heifer' => t('cat_replacement_heifer'),
  'bs_pregnant'            => t('bs_pregnant'),
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

document.getElementById('summary-month').addEventListener('change', loadMonthly);

fetch('/api/dashboard.php')
  .then(r=>r.json())
  .then(res=>{
    if(!res.success){document.getElementById('summary-content').innerHTML='<div class="empty-state"><p>Failed to load.</p></div>';return;}
    const d = res.data;
    document.getElementById('summary-content').innerHTML = `
      <div class="stat-grid">
        <div class="stat-card"><span class="stat-val">${d.total_animals}</span><span class="stat-label">${T.total_animals}</span></div>
        <div class="stat-card alert"><span class="stat-val">${d.vaccines_overdue}</span><span class="stat-label">${T.vaccines_overdue}</span></div>
        <div class="stat-card"><span class="stat-val">${d.vaccines_due}</span><span class="stat-label">${T.vaccines_due_7d}</span></div>
        <div class="stat-card"><span class="stat-val">${d.upcoming_calvings}</span><span class="stat-label">${T.upcoming_calvings}</span></div>
      </div>

      ${(()=>{
        const today30 = new Date(); today30.setHours(0,0,0,0);
        const in30 = new Date(today30); in30.setDate(in30.getDate() + 30);
        const calvings30 = (d.calving_list||[]).filter(a => {
          if (!a.expected_calving) return false;
          const due = new Date(a.expected_calving);
          return due <= in30;
        });
        return calvings30.length ? `
      <div class="section-header"><h2>${T.upcoming_calvings}</h2></div>
      <div class="list-card list-card-inset" style="margin:0 16px 16px">
        ${calvings30.map(a => {
          const due = a.expected_calving ? new Date(a.expected_calving) : null;
          const today = new Date(); today.setHours(0,0,0,0);
          const daysLeft = due ? Math.round((due - today) / 86400000) : null;
          const color = daysLeft === null ? '' : daysLeft <= 14 ? 'var(--red)' : daysLeft <= 30 ? '#f57f17' : 'var(--green)';
          return `<a href="/animal-detail.php?id=${a.id}" class="list-item">
            <div class="item-body">
              <div class="item-title">${escHtml(a.ear_tag)}</div>
              <div class="item-sub">${due ? T.expected_label + ': ' + due.toLocaleDateString(undefined,{day:'numeric',month:'short',year:'numeric'}) : T.no_breeding_date}</div>
            </div>
            ${daysLeft !== null ? `<span style="font-size:12px;font-weight:700;color:${color}">${daysLeft > 0 ? daysLeft+'d' : T.overdue}</span>` : ''}
            <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </a>`;
        }).join('')}
      </div>` : '';
      })()}

      <div class="section-header"><h2>${T.farm_summary}</h2></div>
      ${(d.farm_summary||[]).map(f=>`
        <div class="list-card list-card-inset" style="margin:0 16px 16px">
          <a href="/camps.php?farm=${f.id}" class="list-item" style="background:var(--surface-2,#f5f5f5)">
            <div class="item-body"><div class="item-title" style="font-weight:700">${escHtml(f.name)}</div></div>
            <strong>${f.animal_count} ${T.animals_count}</strong>
            <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </a>
          ${(f.categories||[]).map(c=>`
            <div class="list-item" style="padding-left:24px">
              <div class="item-body"><div class="item-title text-sm">${escHtml(catLabel(c.category))}</div></div>
              <strong>${c.cnt}</strong>
            </div>
          `).join('')}
        </div>
      `).join('') || `<div class="list-card list-card-inset" style="margin:0 16px 16px"><div class="p-16 text-muted text-sm">${T.no_farms_yet}</div></div>`}
    `;
    loadMonthly();
  });

function loadMonthly() {
  const month = document.getElementById('summary-month').value;
  const from  = month + '-01';
  const to    = month + '-' + new Date(month.split('-')[0], month.split('-')[1], 0).getDate();
  fetch(`/api/reports.php?type=monthly&from=${from}&to=${to}`)
    .then(r=>r.json())
    .then(res=>{
      if(!res.success)return;
      const d = res.data;
      document.querySelectorAll('.monthly-section').forEach(el=>el.remove());
      const existing = document.getElementById('summary-content');

      const deadSold = d.dead_sold || [];
      const dead = deadSold.filter(a=>a.animal_status==='dead');
      const sold = deadSold.filter(a=>a.animal_status==='sold');

      const monthLabel = new Date(from+'T00:00:00').toLocaleDateString(undefined,{month:'long',year:'numeric'});

      let html = `<div class="monthly-section">`;

      html += `
        <div class="section-header"><h2>${T.by_category}</h2></div>
        <div class="list-card list-card-inset" style="margin:0 16px 16px">
          ${(d.by_category||[]).map(c=>`
            <div class="list-item">
              <div class="item-body"><div class="item-title">${escHtml(catLabel(c.category))}</div></div>
              <strong>${c.cnt}</strong>
            </div>
          `).join('') || `<div style="padding:12px 16px;color:var(--text-muted,#888);font-size:0.85rem">${T.no_data}</div>`}
        </div>`;

      if (sold.length) {
        html += `
        <div class="section-header"><h2>${T.sold_in} ${monthLabel}</h2></div>
        <div class="list-card list-card-inset" style="margin:0 16px 16px">
          ${sold.map(a=>`
            <a href="/animal-detail.php?id=${a.id}" class="list-item">
              <div class="item-body">
                <div class="item-title">${escHtml(a.ear_tag)}</div>
                <div class="item-sub">${catLabel(a.category)} · ${a.status_date}${a.status_notes ? ' · '+escHtml(a.status_notes) : ''}</div>
              </div>
              <span style="font-size:11px;font-weight:700;color:#1565C0;background:#e3f2fd;padding:2px 8px;border-radius:20px">${T.as_sold.toUpperCase()}</span>
              <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>`).join('')}
        </div>`;
      }

      const newborns = d.newborns || [];
      if (newborns.length) {
        const bornId = 'born-' + month.replace('-','');
        html += `
        <div class="section-header" style="cursor:pointer;user-select:none" onclick="toggleMonthSection('${bornId}',this)">
          <h2 style="display:flex;align-items:center;justify-content:space-between;width:100%">
            <span>${T.born_in} ${monthLabel}</span>
            <span style="display:flex;align-items:center;gap:8px">
              <span style="background:var(--blue);color:#fff;font-size:13px;font-weight:700;padding:2px 12px;border-radius:20px">${newborns.length}</span>
              <svg class="toggle-chev" viewBox="0 0 24 24" width="18" height="18" style="fill:currentColor;transition:transform 0.2s;flex-shrink:0"><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z"/></svg>
            </span>
          </h2>
        </div>
        <div id="${bornId}" class="list-card list-card-inset" style="margin:0 16px 16px;display:none">
          ${newborns.map(a=>`
            <a href="/animal-detail.php?id=${a.id}" class="list-item">
              <div class="item-body">
                <div class="item-title">${escHtml(a.ear_tag)}</div>
                <div class="item-sub">${catLabel(a.category)}${a.dam_tag ? ' · Dam: '+escHtml(a.dam_tag) : ''} · ${a.dob}</div>
              </div>
              <span style="font-size:11px;font-weight:700;color:#2e7d32;background:#e8f5e9;padding:2px 8px;border-radius:20px">${T.born_label.toUpperCase()}</span>
              <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>`).join('')}
        </div>`;
      }

      const purchases = d.purchases || [];
      if (purchases.length) {
        const totalHead = purchases.reduce((s,p)=>s+parseInt(p.total_purchased),0);
        const totalVal  = purchases.reduce((s,p)=>s+parseFloat(p.price_zar),0);
        html += `
        <div class="section-header"><h2>${T.purchased_in} ${monthLabel}</h2></div>
        <div class="list-card list-card-inset" style="margin:0 16px 16px">
          ${purchases.map(p=>`
            <div class="list-item">
              <div class="item-body">
                <div class="item-title">${catLabel(p.category)} · ${p.total_purchased} ${T.animals_count}</div>
                <div class="item-sub">${escHtml(p.seller)} · ${p.date_purchased}</div>
              </div>
              <span style="font-size:12px;font-weight:700;color:#555">R ${parseFloat(p.price_zar).toLocaleString()}</span>
            </div>`).join('')}
          <div class="list-item" style="background:var(--surface-2,#f5f5f5)">
            <div class="item-body"><div class="item-title" style="font-weight:700">${T.total}</div></div>
            <strong>${totalHead} ${T.animals_count} &nbsp;·&nbsp; R ${totalVal.toLocaleString()}</strong>
          </div>
        </div>`;
      }

      if (dead.length) {
        html += `
        <div class="section-header"><h2>${T.deaths_in} ${monthLabel}</h2></div>
        <div class="list-card list-card-inset" style="margin:0 16px 16px">
          ${dead.map(a=>`
            <a href="/animal-detail.php?id=${a.id}" class="list-item">
              <div class="item-body">
                <div class="item-title">${escHtml(a.ear_tag)}</div>
                <div class="item-sub">${catLabel(a.category)} · ${a.status_date}${a.status_notes ? ' · '+escHtml(a.status_notes) : ''}</div>
              </div>
              <span style="font-size:11px;font-weight:700;color:#b71c1c;background:#ffebee;padding:2px 8px;border-radius:20px">${T.as_dead.toUpperCase()}</span>
              <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>`).join('')}
        </div>`;
      }

      html += `</div>`;
      existing.insertAdjacentHTML('beforeend', html);
    });
}

function escHtml(s){const d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}

// ── Top-level tabs (Summary / Reports) ───────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
  });
});

// ── Inner report sub-tabs (Monthly / Herd) ───────────────────────────────────
document.querySelectorAll('.rep-tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.rep-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.rep-panel').forEach(p => p.style.display = 'none');
    btn.classList.add('active');
    document.getElementById(btn.dataset.rep).style.display = '';
  });
});

// Load herds into the herd selector
fetch('/api/herds.php').then(r=>r.json()).then(res=>{
  const sel = document.getElementById('r-herd');
  (res.data||[]).forEach(h => {
    const o = document.createElement('option');
    o.value = h.id; o.textContent = h.name;
    sel.appendChild(o);
  });
});

function loadMonthlyReport() {
  const from = document.getElementById('r-from').value;
  const to   = document.getElementById('r-to').value;
  const el   = document.getElementById('rep-monthly-output');
  el.innerHTML = '<div class="page-loader"><div class="spinner"></div></div>';
  fetch(`/api/reports.php?type=monthly&from=${from}&to=${to}`)
    .then(r=>r.json())
    .then(res => {
      if (!res.success) { el.innerHTML='<p class="text-muted" style="padding:16px">Failed to load.</p>'; return; }
      const d = res.data;
      const dead      = (d.dead_sold||[]).filter(a=>a.animal_status==='dead');
      const sold      = (d.dead_sold||[]).filter(a=>a.animal_status==='sold');
      const purchases = d.purchases || [];
      const newborns  = d.newborns  || [];
      const monthLabel = new Date(from+'T00:00:00').toLocaleDateString(undefined,{month:'long',year:'numeric'});
      el.innerHTML = `
        <div class="stat-grid" style="padding:0 0 16px">
          <div class="stat-card"><span class="stat-val">${d.total_active}</span><span class="stat-label">${T.total_animals}</span></div>
          <div class="stat-card"><span class="stat-val">${sold.length}</span><span class="stat-label">${T.as_sold}</span></div>
          <div class="stat-card"><span class="stat-val">${dead.length}</span><span class="stat-label">${T.as_dead}</span></div>
          <div class="stat-card"><span class="stat-val">${newborns.length}</span><span class="stat-label">${T.born_label}</span></div>
        </div>

        <div class="section-header"><h2>${T.by_category}</h2></div>
        <div class="list-card list-card-inset mb-16" style="margin:0 16px 16px">
          ${(d.by_category||[]).map(c=>`
            <div class="list-item">
              <div class="item-body"><div class="item-title">${escHtml(catLabel(c.category))}</div></div>
              <strong>${c.cnt}</strong>
            </div>`).join('') || `<div class="p-16 text-muted text-sm">${T.no_data}</div>`}
        </div>

        ${sold.length ? `
        <div class="section-header"><h2>${T.sold_in} ${monthLabel}</h2></div>
        <div class="list-card list-card-inset mb-16" style="margin:0 16px 16px">
          ${sold.map(a=>`
            <a href="/animal-detail.php?id=${a.id}" class="list-item">
              <div class="item-body">
                <div class="item-title">${escHtml(a.ear_tag)}</div>
                <div class="item-sub">${escHtml(catLabel(a.category))} · ${a.status_date}${a.status_notes?' · '+escHtml(a.status_notes):''}</div>
              </div>
              <span style="font-size:11px;font-weight:700;color:#1565C0;background:#e3f2fd;padding:2px 8px;border-radius:20px">${T.as_sold.toUpperCase()}</span>
              <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>`).join('')}
        </div>` : ''}

        ${purchases.length ? `
        <div class="section-header"><h2>${T.purchased_in} ${monthLabel}</h2></div>
        <div class="list-card list-card-inset mb-16" style="margin:0 16px 16px">
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
        <div class="section-header"><h2>${T.born_in} ${monthLabel} (${newborns.length})</h2></div>
        <div class="list-card list-card-inset mb-16" style="margin:0 16px 16px">
          ${newborns.map(a=>`
            <a href="/animal-detail.php?id=${a.id}" class="list-item">
              <div class="item-body">
                <div class="item-title">${escHtml(a.ear_tag)}</div>
                <div class="item-sub">${escHtml(catLabel(a.category))}${a.dam_tag?' · Dam: '+escHtml(a.dam_tag):''} · ${a.dob}</div>
              </div>
              <span style="font-size:11px;font-weight:700;color:#2e7d32;background:#e8f5e9;padding:2px 8px;border-radius:20px">${T.born_label.toUpperCase()}</span>
              <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>`).join('')}
        </div>` : ''}

        ${dead.length ? `
        <div class="section-header"><h2>${T.deaths_in} ${monthLabel} (${dead.length})</h2></div>
        <div class="list-card list-card-inset mb-16" style="margin:0 16px 16px">
          ${dead.map(a=>`
            <a href="/animal-detail.php?id=${a.id}" class="list-item">
              <div class="item-body">
                <div class="item-title">${escHtml(a.ear_tag)}</div>
                <div class="item-sub">${escHtml(catLabel(a.category))} · ${a.status_date}${a.status_notes?' · '+escHtml(a.status_notes):''}</div>
              </div>
              <span style="font-size:11px;font-weight:700;color:#b71c1c;background:#ffebee;padding:2px 8px;border-radius:20px">${T.as_dead.toUpperCase()}</span>
              <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>`).join('')}
        </div>` : ''}
      `;
    });
}

function loadHerdReport() {
  const herdId = document.getElementById('r-herd').value;
  if (!herdId) { alert('<?= t('herd') ?>?'); return; }
  const el = document.getElementById('rep-herd-output');
  el.innerHTML = '<div class="page-loader"><div class="spinner"></div></div>';
  fetch(`/api/reports.php?type=herd&herd_id=${herdId}`)
    .then(r=>r.json())
    .then(res => {
      if (!res.success) { el.innerHTML='<p class="text-muted" style="padding:16px">Failed.</p>'; return; }
      const d = res.data;
      el.innerHTML = `
        <div class="stat-grid" style="padding:0 0 16px">
          <div class="stat-card"><span class="stat-val">${d.total_active}</span><span class="stat-label">${T.total_animals}</span></div>
          <div class="stat-card"><span class="stat-val">${d.pregnant_cows}</span><span class="stat-label">${T.bs_pregnant}</span></div>
          <div class="stat-card alert"><span class="stat-val">${d.vaccinations_overdue}</span><span class="stat-label">${T.vaccines_overdue}</span></div>
          <div class="stat-card"><span class="stat-val">${d.vaccinations_due}</span><span class="stat-label">${T.vaccines_due_7d}</span></div>
        </div>
        <div class="section-header"><h2>${T.by_category}</h2></div>
        <div class="list-card list-card-inset mb-16" style="margin:0 16px 16px">
          ${(d.by_category||[]).map(c=>`
            <div class="list-item">
              <div class="item-body"><div class="item-title">${escHtml(catLabel(c.category))}</div></div>
              <strong>${c.cnt}</strong>
            </div>`).join('') || `<div class="p-16 text-muted text-sm">${T.no_data}</div>`}
        </div>
        <div class="section-header"><h2><?= t('avg_interval_label') ?></h2></div>
        <div class="list-card list-card-inset mb-16" style="margin:0 16px 16px">
          ${(d.avg_weight_by_cat||[]).map(w=>`
            <div class="list-item">
              <div class="item-body"><div class="item-title">${escHtml(catLabel(w.category))}</div></div>
              <strong>${w.avg_weight} kg</strong>
            </div>`).join('') || `<div class="p-16 text-muted text-sm">${T.no_data}</div>`}
        </div>
      `;
    });
}

function toggleMonthSection(id, btn) {
  const list = document.getElementById(id);
  const isHidden = list.style.display === 'none';
  list.style.display = isHidden ? '' : 'none';
  const chev = btn.querySelector('.toggle-chev');
  if (chev) chev.style.transform = isHidden ? 'rotate(180deg)' : '';
}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
