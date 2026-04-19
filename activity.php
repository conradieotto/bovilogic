<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

// Build description translation maps for the current language.
// The descriptions are stored in English in the DB so we translate at display time.
$isAf = $user['language'] === 'af';

$descPrefixes = $isAf ? [
    'Animal created: '                => 'Dier geskep: ',
    'Animal updated: '                => 'Dier verander: ',
    'Weight added: '                  => 'Gewig gevoeg: ',
    'Farm created: '                  => 'Plaas geskep: ',
    'Farm updated: '                  => 'Plaas verander: ',
    'Camp created: '                  => 'Kamp geskep: ',
    'Camp updated: '                  => 'Kamp verander: ',
    'Herd created: '                  => 'Trop geskep: ',
    'Herd updated: '                  => 'Trop verander: ',
    'Vaccination added: '             => 'Entstof gevoeg: ',
    'Treatment: '                     => 'Behandeling: ',
    'Sale: '                          => 'Verkoop: ',
    'Purchase: '                      => 'Aankoop: ',
    'Mortality recorded for animal #' => 'Sterfte vir dier #',
    'Pregnancy test: '                => 'Dragtigheidstoets: ',
    'User created: '                  => 'Gebruiker geskep: ',
    'User updated: '                  => 'Gebruiker verander: ',
    'User deleted: '                  => 'Gebruiker uitgevee: ',
    'Herd #'                          => 'Trop #',
    'Purged ('                        => 'Verwyder (',
    'Event: '                         => 'Geleentheid: ',
] : [];

$descExact = $isAf ? [
    'Animal deleted'      => 'Dier uitgevee',
    'Weight updated'      => 'Gewig verander',
    'Weight deleted'      => 'Gewig uitgevee',
    'Farm deleted'        => 'Plaas uitgevee',
    'Camp deleted'        => 'Kamp uitgevee',
    'Herd deleted'        => 'Trop uitgevee',
    'Vaccination updated' => 'Entstof verander',
    '2FA reset by admin'  => '2FA herstel deur admin',
] : [];

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
const T = <?= json_encode([
  'no_activity'    => t('no_activity'),
  'act_create'     => t('act_create'),
  'act_update'     => t('act_update'),
  'act_delete'     => t('act_delete'),
  'ent_animal'     => t('animal'),
  'ent_herd'       => t('herd'),
  'ent_farm'       => t('farm'),
  'ent_camp'       => t('camp'),
  'ent_vaccination'=> t('vaccinations'),
  'ent_treatment'  => t('treatments'),
  'ent_weight'     => t('weights'),
  'ent_event'      => t('events'),
  'ent_calving'    => t('calving'),
  'ent_sale'       => t('sales'),
  'ent_purchase'   => t('purchases'),
  'ent_mortality'  => t('mortality'),
  'ent_user'       => t('users'),
  'ent_movement'   => t('move_herd'),
  'animals_count'  => t('animals_count'),
  // event-type suffixes (for "Event: branding" etc.)
  'ev_branding'    => t('ev_branding'),
  'ev_dehorning'   => t('ev_dehorning'),
  'ev_castration'  => t('ev_castration'),
  'ev_weaning'     => t('ev_weaning'),
  'ev_other'       => t('ev_other'),
  'ev_pregnancy_test' => t('pregnancy_test'),
  // description translation maps
  'descPrefixes'   => $descPrefixes,
  'descExact'      => $descExact,
]) ?>;

const EV_MAP = {
  branding: T.ev_branding, dehorning: T.ev_dehorning,
  castration: T.ev_castration, weaning: T.ev_weaning,
  pregnancy_test: T.ev_pregnancy_test, other: T.ev_other,
};

function translateDesc(s) {
  if (!s) return s;
  // Exact match first
  if (T.descExact[s]) return T.descExact[s];
  // Prefix match
  for (const [en, tr] of Object.entries(T.descPrefixes)) {
    if (s.startsWith(en)) {
      const suffix = s.slice(en.length);
      // Translate event type suffix if applicable
      if (en === 'Event: ' || en === 'Geleentheid: ') {
        return tr + (EV_MAP[suffix] || suffix);
      }
      return tr + suffix;
    }
  }
  return s;
}

function entityLabel(type) { return T['ent_' + type] || type; }
function actionLabel(action) {
  return {create: T.act_create, update: T.act_update, delete: T.act_delete}[action] || action;
}
function badgeClass(a) {
  return {create:'badge-green', update:'badge-blue', delete:'badge-red'}[a] || 'badge-grey';
}

let offset = 0;
const limit = 20;

function loadActivity(reset=false) {
  if (reset) {
    offset = 0;
    document.getElementById('activity-list').innerHTML = '<div class="page-loader"><div class="spinner"></div></div>';
  }
  fetch(`/api/activity-log.php?limit=${limit}&offset=${offset}`)
    .then(r => r.json())
    .then(res => {
      const el = document.getElementById('activity-list');
      if (!res.success) { el.innerHTML = `<div class="empty-state"><p>${T.no_activity}</p></div>`; return; }
      const items = res.data.items || [];
      const total = res.data.total || 0;
      if (!items.length) { el.innerHTML = `<div class="empty-state"><p>${T.no_activity}</p></div>`; return; }

      const html = '<div class="list-card list-card-inset" style="margin:0 16px 16px">' + items.map(a => {
        const entLabel   = entityLabel(a.entity_type);
        const actLabel   = actionLabel(a.action);
        const desc       = translateDesc(a.description || a.action);
        const countBadge = (a.cnt > 1)
          ? ` <span style="font-size:0.75rem;color:var(--text-muted)">· ${a.cnt} ${T.animals_count}</span>`
          : '';
        return `
          <div class="list-item">
            <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M13 2.05V4.05C17.39 4.59 20.5 8.58 19.96 12.97C19.5 16.61 16.64 19.5 13 19.93V21.93C18.5 21.38 22.5 16.5 21.95 11C21.5 6.25 17.73 2.5 13 2.05M11 2.06C9.05 2.25 7.19 3 5.67 4.26L7.1 5.74C8.22 4.84 9.57 4.26 11 4.06V2.06"/></svg></div>
            <div class="item-body">
              <div class="item-title">${escHtml(desc)}${countBadge}</div>
              <div class="item-sub">${entLabel} · ${escHtml(a.user_name || 'System')} · ${formatDate(a.created_at)}</div>
            </div>
            <span class="badge ${badgeClass(a.action)}">${actLabel}</span>
          </div>`;
      }).join('') + '</div>';

      if (reset) el.innerHTML = html;
      else el.insertAdjacentHTML('beforeend', html.replace('<div class="list-card list-card-inset" style="margin:0 16px 16px">', '<div>').replace(/^[\s\S]*?<div class="list-item">/, '<div class="list-item">'));
      offset += items.length;
      document.getElementById('load-more-btn').style.display = offset < total ? 'inline-flex' : 'none';
    });
}

document.getElementById('load-more-btn').addEventListener('click', () => loadActivity(false));

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function formatDate(s) { if (!s) return ''; const d = new Date(s); return d.toLocaleDateString() + ' ' + d.toLocaleTimeString(); }

loadActivity(true);
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
