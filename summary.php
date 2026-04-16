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

<div style="padding:12px 16px 0">
  <input type="month" id="summary-month" class="form-control" style="max-width:200px"
         value="<?= date('Y-m') ?>">
</div>
<div id="summary-content"><div class="page-loader" style="min-height:300px"><div class="spinner"></div></div></div>

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
        html += `
        <div class="section-header"><h2>${T.born_in} ${monthLabel}</h2></div>
        <div class="list-card list-card-inset" style="margin:0 16px 16px">
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
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
