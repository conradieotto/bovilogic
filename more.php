<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'nav_more';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-ellipsis"></i> <?= t('nav_more') ?></h1>
</div>

<!-- User info -->
<div style="padding:16px 16px 0">
  <div class="card">
    <div class="card-body flex-between">
      <div>
        <div class="font-bold"><?= htmlspecialchars($user['name']) ?></div>
        <div class="text-muted text-sm"><?= htmlspecialchars($user['email']) ?></div>
        <span class="badge <?= $user['role'] === 'super_admin' ? 'badge-green' : 'badge-blue' ?> mt-4">
          <?= $user['role'] === 'super_admin' ? t('role_super_admin') : t('role_view_user') ?>
        </span>
      </div>
      <a href="/logout.php" class="btn btn-secondary btn-sm"><?= t('logout') ?></a>
    </div>
  </div>
</div>

<div class="section-header" style="padding-top:20px"><h2>Management</h2></div>
<div class="list-card">

  <?php if (isSuperAdmin()): ?>
  <a href="/users.php" class="list-item">
    <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg></div>
    <div class="item-body"><div class="item-title"><?= t('users') ?></div><div class="item-sub">Manage user accounts</div></div>
    <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
  </a>
  <?php endif; ?>

  <a href="/settings.php" class="list-item">
    <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94s-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg></div>
    <div class="item-body"><div class="item-title"><?= t('settings') ?></div><div class="item-sub">App preferences</div></div>
    <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
  </a>

  <a href="/language.php" class="list-item">
    <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zm6.93 6h-2.95c-.32-1.25-.78-2.45-1.38-3.56 1.84.63 3.37 1.91 4.33 3.56zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2s.06 1.34.14 2H4.26zm.82 2h2.95c.32 1.25.78 2.45 1.38 3.56-1.84-.63-3.37-1.9-4.33-3.56zm2.95-8H5.08c.96-1.66 2.49-2.93 4.33-3.56C8.81 5.55 8.35 6.75 8.03 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66c-.09-.66-.16-1.32-.16-2s.07-1.35.16-2h4.68c.09.65.16 1.32.16 2s-.07 1.34-.16 2zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95c-.96 1.65-2.49 2.93-4.33 3.56zM16.36 14c.08-.66.14-1.32.14-2s-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2h-3.38z"/></svg></div>
    <div class="item-body"><div class="item-title"><?= t('language') ?></div><div class="item-sub">English / Afrikaans</div></div>
    <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
  </a>
</div>

<div class="section-header" style="padding-top:8px"><h2>Data</h2></div>
<div class="list-card">
  <a href="/activity.php" class="list-item">
    <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M13 2.05V4.05C17.39 4.59 20.5 8.58 19.96 12.97C19.5 16.61 16.64 19.5 13 19.93V21.93C18.5 21.38 22.5 16.5 21.95 11C21.5 6.25 17.73 2.5 13 2.05M11 2.06C9.05 2.25 7.19 3 5.67 4.26L7.1 5.74C8.22 4.84 9.57 4.26 11 4.06V2.06M4.26 5.67C3 7.19 2.25 9.05 2.06 11H4.06C4.26 9.57 4.84 8.22 5.74 7.1L4.26 5.67M2.06 13C2.25 14.95 3 16.81 4.27 18.33L5.74 16.9C4.84 15.78 4.26 14.43 4.06 13H2.06M7.1 18.37L5.67 19.74C7.18 21 9.04 21.79 11 22V20C9.57 19.8 8.22 19.22 7.1 18.37Z"/></svg></div>
    <div class="item-body"><div class="item-title"><?= t('recent_activity') ?></div><div class="item-sub">Full activity history</div></div>
    <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
  </a>

  <a href="/sync.php" class="list-item" id="sync-menu-item">
    <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8zm0 14c-3.31 0-6-2.69-6-6 0-1.01.25-1.97.7-2.8L5.24 7.74C4.46 8.97 4 10.43 4 12c0 4.42 3.58 8 8 8v3l4-4-4-4v3z"/></svg></div>
    <div class="item-body"><div class="item-title"><?= t('sync') ?></div><div class="item-sub" id="sync-sub">Check sync status</div></div>
    <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
  </a>

  <?php if (isSuperAdmin()): ?>
  <a href="/backup.php" class="list-item">
    <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2v9.67z"/></svg></div>
    <div class="item-body"><div class="item-title"><?= t('backup') ?></div><div class="item-sub">Export / Import data</div></div>
    <svg class="chevron" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
  </a>
  <?php endif; ?>
</div>

<script>
// Show pending sync count
if (typeof BL_DB !== 'undefined') {
  BL_DB.getSyncQueueCount().then(n => {
    if (n > 0) {
      document.getElementById('sync-sub').textContent = `${n} pending changes`;
    }
  });
}
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
