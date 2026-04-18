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

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-bell"></i> <?= t('alerts') ?></h1>
</div>

<div class="section-header"><h2><?= t('losing_weight') ?></h2></div>
<div id="weight-loss" class="list-card list-card-inset" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2><?= t('poor_calving_interval') ?></h2></div>
<div id="poor-calving" class="list-card list-card-inset" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2><?= t('bad_pregnancy_rate') ?></h2></div>
<div id="bad-pregnancy" class="list-card list-card-inset" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2><?= t('overdue') ?></h2></div>
<div id="vacc-overdue" class="list-card list-card-inset" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2><?= t('due_this_week') ?></h2></div>
<div id="vacc-due" class="list-card list-card-inset" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2><?= t('upcoming_calvings') ?></h2></div>
<div id="calvings" class="list-card list-card-inset" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<div class="section-header"><h2><?= t('grazing_warning') ?></h2></div>
<div id="grazing-alerts" class="list-card list-card-inset" style="margin:0 16px 16px"><div class="page-loader"><div class="spinner"></div></div></div>

<script>
var IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
const T = <?= json_encode([
  'no_weight_loss'    => t('no_weight_loss'),
  'no_animals_flagged'=> t('no_animals_flagged'),
  'no_herds_flagged'  => t('no_herds_flagged'),
  'no_calvings_due'   => t('no_calvings_due'),
  'last_weighed'      => t('last_weighed'),
  'avg_interval_label'=> t('avg_interval_label'),
  'tested_label'      => t('tested_label'),
  'due_date'          => t('due_date'),
  'overdue'           => t('overdue'),
  'due_soon'          => t('due_soon'),
  'poor_label'        => t('poor_label'),
  'bs_pregnant'       => t('bs_pregnant'),
  'due_now'           => t('due_now'),
  'due_in'            => t('due_in'),
  'days'              => t('days'),
  'day'               => t('day'),
  'mark_done'         => t('mark_done'),
  'select_all'        => t('select_all'),
  'mark_done_count'   => t('mark_done_count'),
  'items_selected'    => t('items_selected'),
  'overgrazed'        => t('overgrazed'),
  'days_left'         => t('days_left'),
  'move_out_by'       => t('move_out_by'),
  'no_grazing_alerts' => t('no_grazing_alerts'),
  'animals_count'     => t('animals_count'),
]) ?>;

function loadGrazingAlerts() {
  fetch('/api/camps.php')
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('grazing-alerts');
      const camps = (res.data || []).filter(c => c.grazing && c.grazing.current_animals > 0);
      const flagged = camps.filter(c => c.grazing.days_left !== null && c.grazing.days_left <= 21);
      flagged.sort((a,b) => a.grazing.days_left - b.grazing.days_left);

      if (!flagged.length) {
        el.innerHTML = `<div style="padding:14px 16px;color:var(--text-muted);font-size:0.875rem">${T.no_grazing_alerts}</div>`;
        return;
      }

      el.innerHTML = flagged.map(c => {
        const g = c.grazing;
        const isOver = g.days_left <= 0;
        const color  = isOver ? '#c62828' : g.days_left <= 7 ? '#e65100' : '#f57f17';
        const bg     = isOver ? '#ffebee' : g.days_left <= 7 ? '#fff3e0' : '#fffde7';
        const pct    = g.pct_used;
        const label  = isOver
          ? T.overgrazed
          : (g.days_left + ' ' + T.days_left);
        const moveDate = g.move_out_by
          ? new Date(g.move_out_by + 'T00:00:00').toLocaleDateString(undefined,{day:'numeric',month:'short'})
          : '';

        return `
          <a href="/camp-detail.php?id=${c.id}" class="list-item">
            <div class="item-icon" style="background:${bg};border:1px solid ${color}33">
              <svg viewBox="0 0 24 24" style="fill:none;stroke:${color};stroke-width:2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
            </div>
            <div class="item-body">
              <div class="item-title" style="color:${color}">${escHtml(c.name)}</div>
              <div class="item-sub">${escHtml(c.farm_name||'')} · ${g.current_animals} ${T.animals_count} · ${pct}% used</div>
              <div class="item-sub" style="margin-top:2px;font-weight:600;color:${color}">
                ${label}${moveDate && !isOver ? ' · ' + T.move_out_by + ' ' + moveDate : ''}
              </div>
            </div>
            <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
          </a>`;
      }).join('');
    });
}

function _markDoneIds(ids, onDone) {
  var today = new Date().toISOString().slice(0,10);
  var promises = ids.map(function(item) {
    return fetch('/api/vaccinations.php?id=' + item.id, {
      method: 'PUT',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ completed: true, completion_date: today, product: item.product, due_date: item.due })
    }).then(function(r) { return r.json(); }).then(function(res) {
      if (res.success) {
        var row = document.getElementById('vacc-row-' + item.id);
        if (row) row.remove();
      }
    });
  });
  Promise.all(promises).then(function() {
    showToast(T.mark_done);
    if (onDone) onDone();
  });
}

function bulkMarkDone(sectionId) {
  var boxes = document.querySelectorAll('.vacc-check-' + sectionId + ':checked');
  if (!boxes.length) return;
  var items = Array.from(boxes).map(function(cb) {
    return { id: parseInt(cb.dataset.id), product: cb.dataset.product, due: cb.dataset.due };
  });
  if (!confirm(T.mark_done_count + ' ' + items.length + ' ' + T.items_selected + '?')) return;
  _markDoneIds(items, function() { _updateBulkBtn(sectionId); });
}

function _toggleSelectAll(sectionId, checked) {
  document.querySelectorAll('.vacc-check-' + sectionId).forEach(function(cb) { cb.checked = checked; });
  _updateBulkBtn(sectionId);
}

function _onVaccCheck(sectionId) {
  var boxes = Array.from(document.querySelectorAll('.vacc-check-' + sectionId));
  var allChecked = boxes.every(function(cb) { return cb.checked; });
  var sa = document.getElementById('vacc-sa-' + sectionId);
  if (sa) sa.checked = allChecked;
  _updateBulkBtn(sectionId);
}

function _updateBulkBtn(sectionId) {
  var checked = Array.from(document.querySelectorAll('.vacc-check-' + sectionId + ':checked'));
  var btn = document.getElementById('vacc-bulk-' + sectionId);
  if (!btn) return;
  if (checked.length > 0) {
    btn.style.display = '';
    btn.textContent = '\u2713 ' + T.mark_done_count + ' (' + checked.length + ')';
  } else {
    btn.style.display = 'none';
  }
}

function renderVacc(items, el, sectionId) {
  if (!items.length) { el.innerHTML = '<div class="p-16 text-muted text-sm" style="padding:16px">' + T.no_animals_flagged + '</div>'; return; }
  var today = new Date();
  var html = '';
  if (IS_ADMIN) {
    html += '<div style="display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid rgba(255,255,255,0.06)">'
      + '<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.85rem;color:var(--text-muted);user-select:none">'
      + '<input type="checkbox" id="vacc-sa-' + sectionId + '" onchange="_toggleSelectAll(\'' + sectionId + '\',this.checked)" style="width:16px;height:16px;cursor:pointer;accent-color:var(--blue)">'
      + T.select_all
      + '</label>'
      + '<button id="vacc-bulk-' + sectionId + '" onclick="bulkMarkDone(\'' + sectionId + '\')" class="btn btn-primary" style="display:none;padding:5px 12px;font-size:12px"></button>'
      + '</div>';
  }
  for (var i = 0; i < items.length; i++) {
    var v = items[i];
    var overdue = new Date(v.due_date) < today;
    var label = v.animal_tag || v.herd_name || 'Unknown';
    var linkHref = v.animal_id ? '/animal-detail.php?id=' + v.animal_id : (v.herd_id ? '/herds.php' : '');
    var checkbox = IS_ADMIN
      ? '<input type="checkbox" class="vacc-check-' + sectionId + '" data-id="' + v.id + '" data-product="' + esc(v.product) + '" data-due="' + v.due_date + '" onchange="_onVaccCheck(\'' + sectionId + '\')" style="width:16px;height:16px;cursor:pointer;accent-color:var(--blue);flex-shrink:0" onclick="event.stopPropagation()">'
      : '';
    html += '<div class="list-item" id="vacc-row-' + v.id + '" style="gap:10px">'
      + (IS_ADMIN ? '<div style="display:flex;align-items:center;padding-left:2px">' + checkbox + '</div>' : '')
      + '<div class="item-body" style="cursor:pointer" onclick="if(\'' + linkHref + '\')location.href=\'' + linkHref + '\'">'
      + '<div class="item-title">' + esc(v.product) + ' \u2014 ' + esc(label) + '</div>'
      + '<div class="item-sub">' + T.due_date + ': ' + fmtDate(v.due_date) + (v.dosage ? ' \u00b7 ' + esc(v.dosage) : '') + '</div>'
      + '</div>'
      + '<span class="badge ' + (overdue ? 'badge-red' : 'badge-amber') + '">' + (overdue ? T.overdue : T.due_soon) + '</span>'
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
      wlEl.innerHTML = '<div class="p-16 text-muted text-sm" style="padding:16px">' + T.no_weight_loss + '</div>';
    } else {
      var html = '';
      for (var i = 0; i < wl.length; i++) {
        var a = wl[i];
        var lost = Math.round((parseFloat(a.prev_kg) - parseFloat(a.latest_kg)) * 10) / 10;
        html += '<a href="/animal-detail.php?id=' + a.id + '" class="list-item">'
          + '<div class="item-body">'
          + '<div class="item-title">' + esc(a.ear_tag) + '</div>'
          + '<div class="item-sub">' + T.last_weighed + ': ' + fmtDate(a.latest_date) + ' &middot; ' + a.latest_kg + ' kg</div>'
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
      pcEl.innerHTML = '<div class="p-16 text-muted text-sm" style="padding:16px">' + T.no_animals_flagged + '</div>';
    } else {
      var html2 = '';
      for (var j = 0; j < pc.length; j++) {
        var c = pc[j];
        html2 += '<a href="/animal-detail.php?id=' + c.id + '" class="list-item">'
          + '<div class="item-body">'
          + '<div class="item-title">' + esc(c.ear_tag) + '</div>'
          + '<div class="item-sub">' + esc(c.herd_name || '') + ' &middot; ' + T.avg_interval_label + ': ' + Math.round(c.avg_calf_interval) + ' ' + T.days + '</div>'
          + '</div>'
          + '<span class="badge badge-red">' + T.poor_label + '</span>'
          + '</a>';
      }
      pcEl.innerHTML = html2;
    }

    // Bad pregnancy rate
    var bpEl = document.getElementById('bad-pregnancy');
    var bp = d.bad_pregnancy || [];
    if (!bp.length) {
      bpEl.innerHTML = '<div class="p-16 text-muted text-sm" style="padding:16px">' + T.no_herds_flagged + '</div>';
    } else {
      var html3 = '';
      for (var k = 0; k < bp.length; k++) {
        var h = bp[k];
        html3 += '<a href="/herds.php" class="list-item">'
          + '<div class="item-body">'
          + '<div class="item-title">' + esc(h.name) + '</div>'
          + '<div class="item-sub">' + esc(h.farm_name || '') + (h.last_pregnancy_test ? ' &middot; ' + T.tested_label + ': ' + fmtDate(h.last_pregnancy_test) : '') + '</div>'
          + '</div>'
          + '<span class="badge badge-red">' + h.pregnancy_rate + '%</span>'
          + '</a>';
      }
      bpEl.innerHTML = html3;
    }
  });

fetch('/api/vaccinations.php?overdue=1')
  .then(function(r){return r.json();})
  .then(function(res){ renderVacc(res.data||[], document.getElementById('vacc-overdue'), 'overdue'); });

fetch('/api/vaccinations.php?due_soon=1')
  .then(function(r){return r.json();})
  .then(function(res){ renderVacc(res.data||[], document.getElementById('vacc-due'), 'due'); });

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
    if (!list.length) { el.innerHTML = '<div class="p-16 text-muted text-sm" style="padding:16px">' + T.no_calvings_due + '</div>'; return; }
    var html = '';
    for (var i = 0; i < list.length; i++) {
      var a = list[i];
      var dueText = '';
      if (a.breeding_date) {
        var due = new Date(a.breeding_date + 'T00:00:00');
        due.setDate(due.getDate() + 285);
        var days = Math.ceil((due - today) / 86400000);
        dueText = days <= 0 ? T.due_now : T.due_in + ' ' + days + ' ' + (days !== 1 ? T.days : T.day);
      }
      html += '<a href="/animal-detail.php?id=' + a.id + '" class="list-item">'
        + '<div class="item-body">'
        + '<div class="item-title">' + esc(a.ear_tag) + '</div>'
        + '<div class="item-sub">' + esc(a.herd_name||'') + (a.breed?' \u00b7 '+esc(a.breed):'') + (dueText?' \u00b7 '+dueText:'') + '</div>'
        + '</div>'
        + '<span class="badge badge-blue">' + T.bs_pregnant + '</span>'
        + '</a>';
    }
    el.innerHTML = html;
  });

function esc(s) { var d=document.createElement('div'); d.textContent=String(s||''); return d.innerHTML; }
function escHtml(s) { return esc(s); }
function fmtDate(s) { if(!s)return''; return new Date(s+'T00:00:00').toLocaleDateString(); }

loadGrazingAlerts();
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
