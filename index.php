<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'nav_dashboard';
require_once __DIR__ . '/templates/header.php';
?>

<?php /* Splash screen — always shown on dashboard load */ ?>
<!-- ─── Splash Screen ─────────────────────────────────────────────────── -->
<div id="splash-screen" onclick="dismissSplash()">

  <!-- OPTION 1: Photo background — drop your image at assets/images/splash.jpg -->
  <img id="splash-bg-img"
       src="/assets/images/splash.jpg.jpeg"
       onerror="this.style.display='none'"
       alt="">

  <!-- OPTION 2: Video background — uncomment and drop file at assets/videos/splash.mp4
  <video id="splash-video" autoplay muted playsinline
         onended="dismissSplash()"
         style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
    <source src="/assets/videos/splash.mp4" type="video/mp4">
  </video>
  -->

  <div id="splash-content">
    <div id="splash-logo">Bovi<span>Logic</span></div>
    <div id="splash-tagline">Farm Management</div>
    <div id="splash-hint">Tap to continue</div>
  </div>

  <div id="splash-bar"><div id="splash-bar-fill"></div></div>
</div>

<style>
#splash-screen {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #0d1117;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: opacity 0.7s ease;
}
#splash-bg-img {
  position: absolute;
  inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  opacity: 0;
  transition: opacity 1s ease;
}
#splash-bg-img.loaded { opacity: 0.55; }
#splash-content {
  position: relative;
  text-align: center;
  color: #fff;
  padding: 24px;
  animation: splashFadeUp 0.8s ease forwards;
}
@keyframes splashFadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}
#splash-logo {
  font-size: 5.5rem;
  font-weight: 900;
  letter-spacing: -0.04em;
  text-shadow: 0 4px 32px rgba(0,0,0,0.7);
}
#splash-logo span { color: #FFDE00; }
#splash-tagline {
  font-size: 1rem;
  font-weight: 500;
  color: rgba(255,255,255,0.65);
  margin-top: 6px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}
#splash-hint {
  font-size: 0.75rem;
  color: rgba(255,255,255,0.35);
  margin-top: 32px;
  letter-spacing: 0.06em;
  animation: pulse 2s ease infinite;
}
@keyframes pulse { 0%,100%{opacity:0.35} 50%{opacity:0.7} }
#splash-bar {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 3px;
  background: rgba(255,255,255,0.1);
}
#splash-bar-fill {
  height: 100%;
  width: 0%;
  background: #FFDE00;
  transition: width 3s linear;
}
</style>

<script>
  // Only show once per browser session
  var splashTimer;
  function dismissSplash() {
    clearTimeout(splashTimer);
    var s = document.getElementById('splash-screen');
    if (!s) return;
    sessionStorage.setItem('splash_shown', '1');
    s.style.opacity = '0';
    setTimeout(function() { if (s.parentNode) s.parentNode.removeChild(s); }, 700);
  }

  // If already shown this session, remove immediately without animation
  if (sessionStorage.getItem('splash_shown')) {
    var s = document.getElementById('splash-screen');
    if (s) s.parentNode.removeChild(s);
    splashTimer = null;
  } else {

  // Fade in photo
  var splashImg = document.getElementById('splash-bg-img');
  if (splashImg) {
    if (splashImg.complete && splashImg.naturalWidth > 0) {
      splashImg.classList.add('loaded');
    } else {
      splashImg.onload = function() { splashImg.classList.add('loaded'); };
    }
  }

  // Start progress bar
  setTimeout(function() {
    var bar = document.getElementById('splash-bar-fill');
    if (bar) bar.style.width = '100%';
  }, 50);

  // Auto-dismiss after 3.5 seconds
  splashTimer = setTimeout(dismissSplash, 3500);
  }
</script>
<!-- Page Header -->
<header class="page-header">
  <h1>BoviLogic</h1>
  <span id="header-date" style="font-size:0.75rem;font-weight:600;color:rgba(255,255,255,0.6);letter-spacing:0.03em;margin-right:auto;padding-left:12px"></span>
  <button class="btn-icon" onclick="window.location='/more.php'" aria-label="More">
    <svg viewBox="0 0 24 24"><path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
  </button>
</header>

<!-- Hero strip -->
<div class="dash-hero">
  <div class="dash-hero-label">Welcome back</div>
  <div class="dash-hero-title" id="hero-total">– Animals</div>
  <div class="dash-hero-sub" id="hero-sub">Loading farm data…</div>
</div>

<!-- Search -->
<div class="search-bar">
  <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
  <input type="search" id="global-search" placeholder="<?= t('search') ?> ear tag / RFID..." autocomplete="off">
</div>

<!-- Dashboard Buttons -->
<div class="dash-grid">

  <button class="dash-btn" onclick="window.location='/summary.php'">
    <svg viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    <?= t('summary') ?>
  </button>

  <button class="dash-btn dash-red" onclick="window.location='/alerts.php'">
    <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
    <?= t('alerts') ?>
    <span id="alert-count" class="badge badge-red" style="display:none">0</span>
  </button>

  <button class="dash-btn dash-cyan" onclick="window.location='/quick-actions.php'">
    <svg viewBox="0 0 24 24"><path d="M12 7L7 12H10V16H14V12H17L12 7zm1-4.95v2c4.39.54 7.5 4.53 6.96 8.92C19.5 16.61 16.64 19.5 13 19.93v2c5.5-.55 9.5-5.43 8.95-10.93C21.5 6.25 17.73 2.5 13 2.05M11 2.06C9.05 2.25 7.19 3 5.67 4.26L7.1 5.74C8.22 4.84 9.57 4.26 11 4.06V2.06M4.26 5.67C3 7.19 2.25 9.05 2.06 11H4.06C4.26 9.57 4.84 8.22 5.74 7.1L4.26 5.67M2.06 13C2.25 14.95 3 16.81 4.27 18.33L5.74 16.9C4.84 15.78 4.26 14.43 4.06 13H2.06M7.1 18.37L5.67 19.74C7.18 21 9.04 21.79 11 22V20C9.57 19.8 8.22 19.22 7.1 18.37"/></svg>
    <?= t('quick_actions') ?>
  </button>

  <button class="dash-btn dash-purple" onclick="window.location='/activity.php'">
    <svg viewBox="0 0 24 24"><path d="M3 18h12v-2H3v2zm0-5h12v-2H3v2zm0-7v2h12V6H3zm14 9.43V7l-5 5.22 5 5.21z"/></svg>
    <?= t('recent_activity') ?>
  </button>

</div>

<!-- Quick Stat Strip (loaded via JS) -->
<div class="section-header"><h2><?= t('summary') ?></h2></div>
<div class="stat-grid" id="dash-stats">
  <div class="stat-card"><span class="stat-val" id="stat-total">–</span><span class="stat-label"><?= t('total_animals') ?></span></div>
  <div class="stat-card alert"><span class="stat-val" id="stat-vaccines">–</span><span class="stat-label"><?= t('vaccines_due') ?></span></div>
  <div class="stat-card"><span class="stat-val" id="stat-calvings">–</span><span class="stat-label"><?= t('upcoming_calvings') ?></span></div>
  <div class="stat-card"><span class="stat-val" id="stat-sale">–</span><span class="stat-label"><?= t('animals_for_sale') ?></span></div>
</div>


<script>
document.getElementById('global-search').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && this.value.trim()) {
    window.location = '/animals.php?q=' + encodeURIComponent(this.value.trim());
  }
});

// Load dashboard stats
fetch('/api/dashboard.php')
  .then(r => r.json())
  .then(res => {
    if (!res.success) return;
    const d = res.data;
    document.getElementById('stat-total').textContent    = d.total_animals ?? 0;
    document.getElementById('stat-vaccines').textContent = d.vaccines_due ?? 0;
    document.getElementById('stat-calvings').textContent = d.upcoming_calvings ?? 0;
    document.getElementById('stat-sale').textContent     = d.for_sale ?? 0;

    // Hero strip
    document.getElementById('hero-total').textContent = (d.total_animals ?? 0) + ' Animals';
    const parts = [];
    if (d.upcoming_calvings) parts.push(d.upcoming_calvings + ' calving soon');
    if (d.for_sale)          parts.push(d.for_sale + ' for sale');
    if (d.vaccines_overdue)  parts.push(d.vaccines_overdue + ' vaccines overdue');
    document.getElementById('hero-sub').textContent = parts.length ? parts.join(' · ') : 'All good — no alerts';

    const alertCount = (d.vaccines_overdue ?? 0) + (d.vaccines_due ?? 0)
                     + (d.poor_calving_count ?? 0) + (d.bad_pregnancy_count ?? 0) + (d.weight_loss_count ?? 0);
    const badge = document.getElementById('alert-count');
    if (alertCount > 0) {
      badge.textContent = alertCount;
      badge.style.display = '';
    }
  })
  .catch(() => {});


function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = s || '';
  return d.innerHTML;
}
function formatDate(s) {
  if (!s) return '';
  return new Date(s).toLocaleDateString();
}

document.getElementById('header-date').textContent = new Date().toLocaleDateString(undefined, {
  weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'
});
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
