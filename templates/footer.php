    </div><!-- /.page-content -->
  </div><!-- /.main-area -->
</div><!-- /.app-shell -->

<div class="toast-container" id="toastContainer"></div>

<script src="/assets/js/app.js?v=<?= APP_VERSION ?>"></script>
<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}
</script>
</body>
</html>
