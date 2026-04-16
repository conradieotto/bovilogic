<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['user_language'] ?? 'en') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#1e2130">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="BoviLogic">
  <title><?= t($pageTitle ?? 'app_name') ?> – BoviLogic</title>
  <link rel="manifest" href="/manifest.json">
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/icons/icon-32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/icons/icon-16.png">
  <link rel="icon" href="/assets/icons/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= APP_VERSION ?>">
</head>
<body class="<?= $bodyClass ?? '' ?>">

<div id="offline-banner" class="offline-banner">
  <i class="fa-solid fa-wifi-slash"></i> <?= t('offline_msg') ?>
</div>

<div class="app-shell">

  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon"><i class="fa-solid fa-cow"></i></div>
      <div class="sidebar-logo-text">Bovi<span>Logic</span></div>
    </div>

    <?php require_once __DIR__ . '/nav.php'; ?>
  </aside>

  <!-- Bottom Navigation Bar (mobile only) -->
  <?php
  $currentPage = basename($_SERVER['PHP_SELF'], '.php');
  $userRole    = $_SESSION['user_role'] ?? 'view_user';
  $isAdmin     = $userRole === 'super_admin';
  ?>
  <nav class="bottom-nav">
    <a href="/index.php"   class="bottom-nav-item <?= $currentPage === 'index'   ? 'active' : '' ?>">
      <i class="fa-solid fa-gauge-high"></i>
      <span><?= t('nav_dashboard') ?></span>
    </a>
    <?php if ($isAdmin || hasPermission('animals')): ?>
    <a href="/animals.php" class="bottom-nav-item <?= $currentPage === 'animals' ? 'active' : '' ?>">
      <i class="fa-solid fa-cow"></i>
      <span><?= t('nav_animals') ?></span>
    </a>
    <?php endif; ?>
    <a href="/alerts.php"  class="bottom-nav-item <?= $currentPage === 'alerts'  ? 'active' : '' ?>">
      <i class="fa-solid fa-bell"></i>
      <span><?= t('nav_alerts') ?></span>
    </a>
    <a href="/summary.php" class="bottom-nav-item <?= $currentPage === 'summary' ? 'active' : '' ?>">
      <i class="fa-solid fa-calendar-days"></i>
      <span><?= t('nav_summary') ?></span>
    </a>
    <a href="/more.php"    class="bottom-nav-item <?= $currentPage === 'more'    ? 'active' : '' ?>">
      <i class="fa-solid fa-ellipsis"></i>
      <span><?= t('nav_more') ?></span>
    </a>
  </nav>

  <!-- Collapse toggle — fixed tab on RIGHT edge of sidebar -->
  <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" onclick="toggleCollapse()" title="Toggle sidebar">
    <i class="fa-solid fa-chevron-left" id="collapseIcon"></i>
  </button>

  <!-- Main Area -->
  <div class="main-area">

    <!-- Top Bar -->
    <div class="topbar">
      <button class="topbar-hamburger" onclick="toggleSidebar()" aria-label="Menu">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div class="topbar-title"><?= t($pageTitle ?? 'app_name') ?></div>
      <div class="topbar-actions">
        <?php if (isset($topbarAction)): ?>
          <?= $topbarAction ?>
        <?php endif; ?>
        <div class="topbar-user">
          <div class="topbar-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
          <span class="hidden-mobile"><?= htmlspecialchars($user['name'] ?? '') ?></span>
        </div>
      </div>
    </div>

    <!-- Page Content -->
    <div class="page-content">
