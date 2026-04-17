<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$userRole    = $_SESSION['user_role'] ?? 'view_user';
$isAdmin     = $userRole === 'super_admin';
?>
<nav class="sidebar-nav">

  <div class="nav-section-label">Main</div>

  <a href="/index.php" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>" data-label="<?= t('nav_dashboard') ?>">
    <i class="fa-solid fa-gauge-high"></i> <span><?= t('nav_dashboard') ?></span>
  </a>

  <?php if ($isAdmin || hasPermission('animals')): ?>
  <a href="/farms.php" class="nav-item <?= $currentPage === 'farms' ? 'active' : '' ?>" data-label="<?= t('nav_farms') ?>">
    <i class="fa-solid fa-tractor"></i> <span><?= t('nav_farms') ?></span>
  </a>
  <a href="/camps.php" class="nav-item <?= $currentPage === 'camps' ? 'active' : '' ?>" data-label="<?= t('nav_camps') ?>">
    <i class="fa-solid fa-map-location-dot"></i> <span><?= t('nav_camps') ?></span>
  </a>
  <a href="/herds.php" class="nav-item <?= $currentPage === 'herds' ? 'active' : '' ?>" data-label="<?= t('nav_herds') ?>">
    <i class="fa-solid fa-people-group"></i> <span><?= t('nav_herds') ?></span>
  </a>
  <a href="/animals.php" class="nav-item <?= $currentPage === 'animals' ? 'active' : '' ?>" data-label="<?= t('nav_animals') ?>">
    <?= beef_cow_icon() ?> <span><?= t('nav_animals') ?></span>
  </a>
  <?php endif; ?>

  <?php if ($isAdmin || hasPermission('reports')): ?>
  <a href="/reports.php" class="nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>" data-label="<?= t('nav_reports') ?>">
    <i class="fa-solid fa-chart-bar"></i> <span><?= t('nav_reports') ?></span>
  </a>
  <?php endif; ?>

  <div class="nav-section-label">Manage</div>

  <a href="/summary.php" class="nav-item <?= $currentPage === 'summary' ? 'active' : '' ?>" data-label="<?= t('nav_summary') ?>">
    <i class="fa-solid fa-calendar-days"></i> <span><?= t('nav_summary') ?></span>
  </a>
  <a href="/alerts.php" class="nav-item <?= $currentPage === 'alerts' ? 'active' : '' ?>" data-label="<?= t('nav_alerts') ?>">
    <i class="fa-solid fa-bell"></i> <span><?= t('nav_alerts') ?></span>
  </a>

  <?php if ($isAdmin): ?>
  <a href="/quick-actions.php" class="nav-item <?= $currentPage === 'quick-actions' ? 'active' : '' ?>" data-label="<?= t('nav_quick_actions') ?>">
    <i class="fa-solid fa-bolt"></i> <span><?= t('nav_quick_actions') ?></span>
  </a>
  <?php endif; ?>

  <?php if ($isAdmin): ?>
  <div class="nav-section-label">Admin</div>
  <a href="/users.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>" data-label="<?= t('nav_users') ?>">
    <i class="fa-solid fa-users"></i> <span><?= t('nav_users') ?></span>
  </a>
  <a href="/activity.php" class="nav-item <?= $currentPage === 'activity' ? 'active' : '' ?>" data-label="<?= t('nav_activity') ?>">
    <i class="fa-solid fa-clock-rotate-left"></i> <span><?= t('nav_activity') ?></span>
  </a>
  <a href="/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>" data-label="<?= t('nav_settings') ?>">
    <i class="fa-solid fa-gear"></i> <span><?= t('nav_settings') ?></span>
  </a>
  <?php endif; ?>

</nav>

<div class="sidebar-footer">
  <a href="/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>" data-label="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
    <i class="fa-solid fa-circle-user"></i> <span><?= htmlspecialchars($_SESSION['user_name'] ?? t('nav_profile')) ?></span>
  </a>
  <a href="/logout.php" class="nav-item" data-label="<?= t('nav_logout') ?>">
    <i class="fa-solid fa-right-from-bracket"></i> <span><?= t('nav_logout') ?></span>
  </a>
</div>
