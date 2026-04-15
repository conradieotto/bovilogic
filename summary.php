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

<div style="padding:12px 16px 0">
  <input type="month" id="summary-month" class="form-control" style="max-width:200px"
         value="<?= date('Y-m') ?>">
</div>
<div id="summary-content"><div class="page-loader" style="min-height:300px"><div class="spinner"></div></div></div>

<script>
document.getElementById('summary-month').addEventListener('change', loadMonthly);

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

      ${(()=>{
        const today30 = new Date(); today30.setHours(0,0,0,0);
        const in30 = new Date(today30); in30.setDate(in30.getDate() + 30);
        const calvings30 = (d.calving_list||[]).filter(a => {
          if (!a.expected_calving) return false;
          const due = new Date(a.expected_calving);
          return due <= in30;
        });
        return calvings30.length ? `
      <div class="section-header"><h2>Upcoming Calvings</h2></div>
      <div class="list-card" style="margin:0 16px 16px">
        ${calvings30.map(a => {
          const due = a.expected_calving ? new Date(a.expected_calving) : null;
          const today = new Date(); today.setHours(0,0,0,0);
          const daysLeft = due ? Math.round((due - today) / 86400000) : null;
          const color = daysLeft === null ? '' : daysLeft <= 14 ? 'var(--red)' : daysLeft <= 30 ? '#f57f17' : 'var(--green)';
          return `<a href="/animal-detail.php?id=${a.id}" class="list-item">
            <div class="item-body">
              <div class="item-title">${escHtml(a.ear_tag)}</div>
              <div class="item-sub">${due ? 'Expected: ' + due.toLocaleDateString(undefined,{day:'numeric',month:'short',year:'numeric'}) : 'No breeding date set'}</div>
            </div>
            ${daysLeft !== null ? `<span style="font-size:12px;font-weight:700;color:${color}">${daysLeft > 0 ? daysLeft+'d' : 'Overdue'}</span>` : ''}
            <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </a>`;
        }).join('')}
      </div>` : '';
      })()}

      <div class="section-header"><h2>Farm Summary</h2></div>
      ${(d.farm_summary||[]).map(f=>`
        <div class="list-card" style="margin:0 16px 16px">
          <a href="/camps.php?farm=${f.id}" class="list-item" style="background:var(--surface-2,#f5f5f5)">
            <div class="item-body"><div class="item-title" style="font-weight:700">${escHtml(f.name)}</div></div>
            <strong>${f.animal_count} animals</strong>
            <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </a>
          ${(f.categories||[]).map(c=>`
            <div class="list-item" style="padding-left:24px">
              <div class="item-body"><div class="item-title text-sm">${escHtml(catLabel(c.category))}</div></div>
              <strong>${c.cnt}</strong>
            </div>
          `).join('')}
        </div>
      `).join('') || '<div class="list-card" style="margin:0 16px 16px"><div class="p-16 text-muted text-sm">No farms yet.</div></div>'}
    `;
    loadMonthly();
  });

function loadMonthly() {
  const month = document.getElementById('summary-month').value; // e.g. "2026-03"
  const from  = month + '-01';
  const to    = month + '-' + new Date(month.split('-')[0], month.split('-')[1], 0).getDate();
  fetch(`/api/reports.php?type=monthly&from=${from}&to=${to}`)
    .then(r=>r.json())
    .then(res=>{
      if(!res.success)return;
      const d = res.data;
      // Remove previously appended monthly sections
      document.querySelectorAll('.monthly-section').forEach(el=>el.remove());
      const existing = document.getElementById('summary-content');

      const deadSold = d.dead_sold || [];
      const dead = deadSold.filter(a=>a.animal_status==='dead');
      const sold = deadSold.filter(a=>a.animal_status==='sold');

      const monthLabel = new Date(from+'T00:00:00').toLocaleDateString(undefined,{month:'long',year:'numeric'});

      let html = `<div class="monthly-section">`;

      html += `
        <div class="section-header"><h2>By Category</h2></div>
        <div class="list-card" style="margin:0 16px 16px">
          ${(d.by_category||[]).map(c=>`
            <div class="list-item">
              <div class="item-body"><div class="item-title">${escHtml(catLabel(c.category))}</div></div>
              <strong>${c.cnt}</strong>
            </div>
          `).join('') || '<div style="padding:12px 16px;color:var(--text-muted,#888);font-size:0.85rem">No data.</div>'}
        </div>`;

      if (sold.length) {
        html += `
        <div class="section-header"><h2>Sold in ${monthLabel}</h2></div>
        <div class="list-card" style="margin:0 16px 16px">
          ${sold.map(a=>`
            <a href="/animal-detail.php?id=${a.id}" class="list-item">
              <div class="item-body">
                <div class="item-title">${escHtml(a.ear_tag)}</div>
                <div class="item-sub">${catLabel(a.category)} · ${a.status_date}${a.status_notes ? ' · '+escHtml(a.status_notes) : ''}</div>
              </div>
              <span style="font-size:11px;font-weight:700;color:#1565C0;background:#e3f2fd;padding:2px 8px;border-radius:20px">SOLD</span>
              <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>`).join('')}
        </div>`;
      }

      const newborns = d.newborns || [];
      if (newborns.length) {
        html += `
        <div class="section-header"><h2>Born in ${monthLabel}</h2></div>
        <div class="list-card" style="margin:0 16px 16px">
          ${newborns.map(a=>`
            <a href="/animal-detail.php?id=${a.id}" class="list-item">
              <div class="item-body">
                <div class="item-title">${escHtml(a.ear_tag)}</div>
                <div class="item-sub">${catLabel(a.category)}${a.dam_tag ? ' · Dam: '+escHtml(a.dam_tag) : ''} · ${a.dob}</div>
              </div>
              <span style="font-size:11px;font-weight:700;color:#2e7d32;background:#e8f5e9;padding:2px 8px;border-radius:20px">BORN</span>
              <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>`).join('')}
        </div>`;
      }

      const purchases = d.purchases || [];
      if (purchases.length) {
        const totalHead = purchases.reduce((s,p)=>s+parseInt(p.total_purchased),0);
        const totalVal  = purchases.reduce((s,p)=>s+parseFloat(p.price_zar),0);
        html += `
        <div class="section-header"><h2>Purchased in ${monthLabel}</h2></div>
        <div class="list-card" style="margin:0 16px 16px">
          ${purchases.map(p=>`
            <div class="list-item">
              <div class="item-body">
                <div class="item-title">${catLabel(p.category)} · ${p.total_purchased} head</div>
                <div class="item-sub">${escHtml(p.seller)} · ${p.date_purchased}</div>
              </div>
              <span style="font-size:12px;font-weight:700;color:#555">R ${parseFloat(p.price_zar).toLocaleString()}</span>
            </div>`).join('')}
          <div class="list-item" style="background:var(--surface-2,#f5f5f5)">
            <div class="item-body"><div class="item-title" style="font-weight:700">Total</div></div>
            <strong>${totalHead} head &nbsp;·&nbsp; R ${totalVal.toLocaleString()}</strong>
          </div>
        </div>`;
      }

      if (dead.length) {
        html += `
        <div class="section-header"><h2>Deaths in ${monthLabel}</h2></div>
        <div class="list-card" style="margin:0 16px 16px">
          ${dead.map(a=>`
            <a href="/animal-detail.php?id=${a.id}" class="list-item">
              <div class="item-body">
                <div class="item-title">${escHtml(a.ear_tag)}</div>
                <div class="item-sub">${catLabel(a.category)} · ${a.status_date}${a.status_notes ? ' · '+escHtml(a.status_notes) : ''}</div>
              </div>
              <span style="font-size:11px;font-weight:700;color:#b71c1c;background:#ffebee;padding:2px 8px;border-radius:20px">DEAD</span>
              <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>`).join('')}
        </div>`;
      }

      html += `</div>`;
      existing.insertAdjacentHTML('beforeend', html);
    });
}

function escHtml(s){const d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}
const CAT_LABELS = {
  breeding_bull:'Breeding Bull', breeding_cow:'Breeding Cow', c_grade_cow:'C-grade Cow',
  bull_calf:'Bull Calf', heifer_calf:'Heifer Calf', weaner:'Weaner', replacement_heifer:'Replacement Heifer'
};
function catLabel(c){ return CAT_LABELS[c] || c.replace(/_/g,' '); }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
