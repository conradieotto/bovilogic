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
  <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
  <link rel="icon" href="/assets/icons/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= APP_VERSION ?>">
</head>
<body class="<?= $bodyClass ?? '' ?>">

<div id="offline-banner" class="offline-banner">
  <i class="fa-solid fa-wifi-slash"></i> Offline – changes will sync when connected
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
