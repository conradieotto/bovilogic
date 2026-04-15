<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$userRole    = $_SESSION['user_role'] ?? 'viewer';
?>
<nav class="sidebar-nav">

  <div class="nav-section-label">Main</div>

  <a href="/index.php" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>">
    <i class="fa-solid fa-gauge-high"></i> <?= t('nav_dashboard') ?>
  </a>
  <a href="/farms.php" class="nav-item <?= $currentPage === 'farms' ? 'active' : '' ?>">
    <i class="fa-solid fa-tractor"></i> <?= t('nav_farms') ?>
  </a>
  <a href="/camps.php" class="nav-item <?= $currentPage === 'camps' ? 'active' : '' ?>">
    <i class="fa-solid fa-map-location-dot"></i> <?= t('nav_camps') ?>
  </a>
  <a href="/herds.php" class="nav-item <?= $currentPage === 'herds' ? 'active' : '' ?>">
    <i class="fa-solid fa-people-group"></i> <?= t('nav_herds') ?>
  </a>
  <a href="/animals.php" class="nav-item <?= $currentPage === 'animals' ? 'active' : '' ?>">
    <i class="fa-solid fa-cow"></i> <?= t('nav_animals') ?>
  </a>
  <a href="/reports.php" class="nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
    <i class="fa-solid fa-chart-bar"></i> <?= t('nav_reports') ?>
  </a>

  <div class="nav-section-label">Manage</div>

  <a href="/summary.php" class="nav-item <?= $currentPage === 'summary' ? 'active' : '' ?>">
    <i class="fa-solid fa-calendar-days"></i> <?= t('nav_summary') ?>
  </a>
  <a href="/alerts.php" class="nav-item <?= $currentPage === 'alerts' ? 'active' : '' ?>">
    <i class="fa-solid fa-bell"></i> <?= t('nav_alerts') ?>
  </a>
  <a href="/quick-actions.php" class="nav-item <?= $currentPage === 'quick-actions' ? 'active' : '' ?>">
    <i class="fa-solid fa-bolt"></i> <?= t('nav_quick_actions') ?>
  </a>

  <?php if (in_array($userRole, ['admin', 'super_admin'])): ?>
  <div class="nav-section-label">Admin</div>

  <a href="/users.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>">
    <i class="fa-solid fa-users"></i> <?= t('nav_users') ?>
  </a>
  <a href="/activity.php" class="nav-item <?= $currentPage === 'activity' ? 'active' : '' ?>">
    <i class="fa-solid fa-clock-rotate-left"></i> <?= t('nav_activity') ?>
  </a>
  <a href="/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
    <i class="fa-solid fa-gear"></i> <?= t('nav_settings') ?>
  </a>
  <?php endif; ?>

</nav>

<div class="sidebar-footer">
  <a href="/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
    <i class="fa-solid fa-circle-user"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? t('nav_profile')) ?>
  </a>
  <a href="/logout.php" class="nav-item">
    <i class="fa-solid fa-right-from-bracket"></i> <?= t('nav_logout') ?>
  </a>
</div>
