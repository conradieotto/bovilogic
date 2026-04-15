<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);
$isAdmin = isSuperAdmin();

$pageTitle = 'alerts';
require_once __DIR__ . '/templates/header.php';
?>

<header class="page-header">
  <a href="/index.php" class="btn-icon">
    <svg viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
  </a>
  <h1><?= t('alerts') ?></h1>
</header>

<div class="section-header"><h2>Losing Weight</h2></div>
<div id="weight-loss" class="list-card" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2>Poor Calving Interval</h2></div>
<div id="poor-calving" class="list-card" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2>Bad Pregnancy Rate</h2></div>
<div id="bad-pregnancy" class="list-card" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2>Vaccines Overdue</h2></div>
<div id="vacc-overdue" class="list-card" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2>Due This Week</h2></div>
<div id="vacc-due" class="list-card" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2>Upcoming Calvings</h2></div>
<div id="calvings" class="list-card" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<script>
var IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;

function markDone(id, product) {
  if (!confirm('Mark "' + product + '" as done today?')) return;
  fetch('/api/vaccinations.php?id=' + id, {
    method: 'PUT',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      completed: true,
      completion_date: new Date().toISOString().slice(0,10),
      product: product,
      due_date: document.getElementById('due-' + id).dataset.due
    })
  })
  .then(function(r) { return r.text(); })
  .then(function(text) {
    var res;
    try { res = JSON.parse(text); } catch(e) { alert('Error: ' + text.substring(0,200)); return; }
    if (res.success) {
      var row = document.getElementById('vacc-row-' + id);
      if (row) row.remove();
      showToast('Vaccination marked as done');
    } else {
      alert(res.message || 'Error');
    }
  })
  .catch(function(e) { alert('Network error: ' + e.message); });
}

function renderVacc(items, el) {
  if (!items.length) { el.innerHTML = '<div class="p-16 text-muted text-sm">None.</div>'; return; }
  var today = new Date();
  var html = '';
  for (var i = 0; i < items.length; i++) {
    var v = items[i];
    var overdue = new Date(v.due_date) < today;
    var label = v.animal_tag || (v.herd_id ? 'Herd #' + v.herd_id : 'Unknown');
    var doneBtn = IS_ADMIN
      ? '<button onclick="markDone(' + v.id + ', \'' + esc(v.product) + '\')" class="btn btn-primary" style="padding:6px 10px;font-size:12px;white-space:nowrap">\u2713 Done</button>'
      : '';
    html += '<div class="list-item" id="vacc-row-' + v.id + '">'
      + '<span id="due-' + v.id + '" data-due="' + v.due_date + '" style="display:none"></span>'
      + '<div class="item-body" style="cursor:pointer" onclick="if(' + (v.animal_id||0) + ')location.href=\'/animal-detail.php?id=' + (v.animal_id||0) + '\'">'
      + '<div class="item-title">' + esc(v.product) + ' \u2014 ' + esc(label) + '</div>'
      + '<div class="item-sub">Due: ' + fmtDate(v.due_date) + (v.dosage ? ' \u00b7 ' + esc(v.dosage) : '') + '</div>'
      + '</div>'
      + '<div style="display:flex;align-items:center;gap:8px">'
      + '<span class="badge ' + (overdue ? 'badge-red' : 'badge-amber') + '">' + (overdue ? 'Overdue' : 'Due soon') + '</span>'
      + doneBtn
      + '</div>'
      + '</div>';
  }
  el.innerHTML = html;
}

fetch('/api/alerts.php')
  .then(function(r){return r.json();})
  .then(function(res) {
    var d = res.data || {};

    // Weight loss
    var wlEl = document.getElementById('weight-loss');
    var wl = d.weight_loss || [];
    if (!wl.length) {
      wlEl.innerHTML = '<div class="p-16 text-muted text-sm" style="padding:16px">No animals losing weight.</div>';
    } else {
      var html = '';
      for (var i = 0; i < wl.length; i++) {
        var a = wl[i];
        var lost = Math.round((parseFloat(a.prev_kg) - parseFloat(a.latest_kg)) * 10) / 10;
        html += '<a href="/animal-detail.php?id=' + a.id + '" class="list-item">'
          + '<div class="item-body">'
          + '<div class="item-title">' + esc(a.ear_tag) + '</div>'
          + '<div class="item-sub">Last weighed: ' + fmtDate(a.latest_date) + ' &middot; ' + a.latest_kg + ' kg</div>'
          + '</div>'
          + '<span class="badge badge-red">-' + lost + ' kg</span>'
          + '</a>';
      }
      wlEl.innerHTML = html;
    }

    // Poor calving interval
    var pcEl = document.getElementById('poor-calving');
    var pc = d.poor_calving || [];
    if (!pc.length) {
      pcEl.innerHTML = '<div class="p-16 text-muted text-sm" style="padding:16px">No animals flagged.</div>';
    } else {
      var html2 = '';
      for (var j = 0; j < pc.length; j++) {
        var c = pc[j];
        html2 += '<a href="/animal-detail.php?id=' + c.id + '" class="list-item">'
          + '<div class="item-body">'
          + '<div class="item-title">' + esc(c.ear_tag) + '</div>'
          + '<div class="item-sub">' + esc(c.herd_name || '') + ' &middot; Avg interval: ' + Math.round(c.avg_calf_interval) + ' days</div>'
          + '</div>'
          + '<span class="badge badge-red">Poor</span>'
          + '</a>';
      }
      pcEl.innerHTML = html2;
    }

    // Bad pregnancy rate
    var bpEl = document.getElementById('bad-pregnancy');
    var bp = d.bad_pregnancy || [];
    if (!bp.length) {
      bpEl.innerHTML = '<div class="p-16 text-muted text-sm" style="padding:16px">No herds flagged.</div>';
    } else {
      var html3 = '';
      for (var k = 0; k < bp.length; k++) {
        var h = bp[k];
        html3 += '<a href="/herds.php" class="list-item">'
          + '<div class="item-body">'
          + '<div class="item-title">' + esc(h.name) + '</div>'
          + '<div class="item-sub">' + esc(h.farm_name || '') + (h.last_pregnancy_test ? ' &middot; Tested: ' + fmtDate(h.last_pregnancy_test) : '') + '</div>'
          + '</div>'
          + '<span class="badge badge-red">' + h.pregnancy_rate + '%</span>'
          + '</a>';
      }
      bpEl.innerHTML = html3;
    }
  });

fetch('/api/vaccinations.php?overdue=1')
  .then(function(r){return r.json();})
  .then(function(res){ renderVacc(res.data||[], document.getElementById('vacc-overdue')); });

fetch('/api/vaccinations.php?due_soon=1')
  .then(function(r){return r.json();})
  .then(function(res){ renderVacc(res.data||[], document.getElementById('vacc-due')); });

fetch('/api/animals.php?status=active')
  .then(function(r){return r.json();})
  .then(function(res) {
    var today = new Date(); today.setHours(0,0,0,0);
    var in30  = new Date(); in30.setDate(in30.getDate() + 30);
    var list  = (res.data||[]).filter(function(a) {
      if (a.breeding_status !== 'pregnant') return false;
      if (!a.breeding_date) return true;
      var due = new Date(a.breeding_date + 'T00:00:00');
      due.setDate(due.getDate() + 285);
      return due <= in30;
    });
    var el = document.getElementById('calvings');
    if (!list.length) { el.innerHTML = '<div class="p-16 text-muted text-sm">No calvings due within 30 days.</div>'; return; }
    var html = '';
    for (var i = 0; i < list.length; i++) {
      var a = list[i];
      var dueText = '';
      if (a.breeding_date) {
        var due = new Date(a.breeding_date + 'T00:00:00');
        due.setDate(due.getDate() + 285);
        var days = Math.ceil((due - today) / 86400000);
        dueText = days <= 0 ? 'Due now' : 'Due in ' + days + ' day' + (days !== 1 ? 's' : '');
      }
      html += '<a href="/animal-detail.php?id=' + a.id + '" class="list-item">'
        + '<div class="item-body">'
        + '<div class="item-title">' + esc(a.ear_tag) + '</div>'
        + '<div class="item-sub">' + esc(a.herd_name||'') + (a.breed?' \u00b7 '+esc(a.breed):'') + (dueText?' \u00b7 '+dueText:'') + '</div>'
        + '</div>'
        + '<span class="badge badge-blue">Pregnant</span>'
        + '</a>';
    }
    el.innerHTML = html;
  });

function esc(s) { var d=document.createElement('div'); d.textContent=String(s||''); return d.innerHTML; }
function fmtDate(s) { if(!s)return''; return new Date(s+'T00:00:00').toLocaleDateString(); }
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
