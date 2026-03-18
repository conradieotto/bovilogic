<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$navItems = [
    ['page' => 'index',   'key' => 'nav_dashboard', 'icon' => 'home'],
    ['page' => 'farms',   'key' => 'nav_farms',     'icon' => 'farm'],
    ['page' => 'herds',   'key' => 'nav_herds',     'icon' => 'herd'],
    ['page' => 'animals', 'key' => 'nav_animals',   'icon' => 'animal'],
    ['page' => 'reports', 'key' => 'nav_reports',   'icon' => 'report'],
    ['page' => 'more',    'key' => 'nav_more',      'icon' => 'more'],
];
$icons = [
    'home'   => '<svg viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
    'farm'   => '<svg viewBox="0 0 24 24"><path d="M19 9.3V4h-3v2.6L12 3 2 12h3v8h5v-5h4v5h5v-8h3l-3-2.7z"/></svg>',
    'herd'   => '<svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="15" cy="8" r="3"/><path d="M1 18v-1c0-2.2 3.6-4 8-4s8 1.8 8 4v1H1zm14.3-4c2.5.4 4.7 1.7 4.7 3v1h-4v-1c0-1.1-.7-2.1-1.8-2.9l1.1-.1z"/></svg>',
    'animal' => '<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>',
    'report' => '<svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>',
    'more'   => '<svg viewBox="0 0 24 24"><path d="M6 10c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm12 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-6 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>',
];
?>
<nav class="bottom-nav" role="navigation" aria-label="Main navigation">
  <?php foreach ($navItems as $item): ?>
  <a href="/<?= $item['page'] === 'index' ? '' : $item['page'] . '.php' ?>"
     class="nav-item <?= ($currentPage === $item['page'] || ($currentPage === '' && $item['page'] === 'index')) ? 'active' : '' ?>"
     aria-label="<?= t($item['key']) ?>">
    <span class="nav-icon"><?= $icons[$item['icon']] ?></span>
    <span class="nav-label"><?= t($item['key']) ?></span>
  </a>
  <?php endforeach; ?>
</nav>
