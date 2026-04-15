    </div><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.app-shell -->

<div class="toast-container" id="toastContainer"></div>

<script src="/assets/js/app.js?v=<?= APP_VERSION ?>"></script>
<script>
// ─── Sidebar mobile toggle ────────────────────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}

// ─── Sidebar collapse (desktop) ──────────────────────────────────────────
function toggleCollapse() {
  const shell     = document.querySelector('.app-shell');
  const icon      = document.getElementById('collapseIcon');
  const collapsed = shell.classList.toggle('sidebar-collapsed');
  icon.className  = collapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left';
  localStorage.setItem('bl_sidebar_collapsed', collapsed ? '1' : '0');
}

// Restore collapsed state on load
(function() {
  if (window.innerWidth > 768 && localStorage.getItem('bl_sidebar_collapsed') === '1') {
    document.querySelector('.app-shell').classList.add('sidebar-collapsed');
    const icon = document.getElementById('collapseIcon');
    if (icon) icon.className = 'fa-solid fa-chevron-right';
  }
})();
</script>
</body>
</html>
