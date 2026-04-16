<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'recent_activity';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-clock-rotate-left"></i> <?= t('recent_activity') ?></h1>
</div>

<div id="activity-list"><div class="page-loader"><div class="spinner"></div></div></div>
<div style="padding:0 16px 16px;text-align:center">
  <button id="load-more-btn" class="btn btn-secondary" style="display:none"><?= t('load_more') ?></button>
</div>

<script>
let offset = 0;
const limit = 20;

function loadActivity(reset=false) {
  if(reset){ offset=0; document.getElementById('activity-list').innerHTML='<div class="page-loader"><div class="spinner"></div></div>'; }
  fetch(`/api/activity-log.php?limit=${limit}&offset=${offset}`)
    .then(r=>r.json())
    .then(res=>{
      const el = document.getElementById('activity-list');
      const noActivity = '<?= t('no_activity') ?>';
      if(!res.success){el.innerHTML=`<div class="empty-state"><p>${noActivity}</p></div>`;return;}
      const items = res.data.items||[];
      const total = res.data.total||0;
      if(!items.length){el.innerHTML=`<div class="empty-state"><p>${noActivity}</p></div>`;return;}
      const html = '<div class="list-card list-card-inset" style="margin:0 16px 16px">'+items.map(a=>`
        <div class="list-item">
          <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M13 2.05V4.05C17.39 4.59 20.5 8.58 19.96 12.97C19.5 16.61 16.64 19.5 13 19.93V21.93C18.5 21.38 22.5 16.5 21.95 11C21.5 6.25 17.73 2.5 13 2.05M11 2.06C9.05 2.25 7.19 3 5.67 4.26L7.1 5.74C8.22 4.84 9.57 4.26 11 4.06V2.06"/></svg></div>
          <div class="item-body">
            <div class="item-title">${escHtml(a.description||a.action)}</div>
            <div class="item-sub">${escHtml(a.entity_type)} · ${escHtml(a.user_name||'System')} · ${formatDate(a.created_at)}</div>
          </div>
          <span class="badge ${badgeClass(a.action)}">${escHtml(a.action)}</span>
        </div>
      `).join('')+'</div>';
      if(reset) el.innerHTML=html; else el.insertAdjacentHTML('beforeend',html.replace('<div class="list-card"','<div').replace('</div>',''));
      offset += items.length;
      document.getElementById('load-more-btn').style.display = offset < total ? 'inline-flex' : 'none';
    });
}

document.getElementById('load-more-btn').addEventListener('click', ()=>loadActivity(false));

function badgeClass(a){return{create:'badge-green',update:'badge-blue',delete:'badge-red'}[a]||'badge-grey';}
function escHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
function formatDate(s){if(!s)return'';const d=new Date(s);return d.toLocaleDateString()+' '+d.toLocaleTimeString();}

loadActivity(true);
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
