<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

requireLogin();
requireRole('super_admin');
$user = currentUser();
loadLanguage($user['language']);

$pageTitle = 'users';
require_once __DIR__ . '/templates/header.php';
?>

<div class="page-wrap">
<div class="page-header">
  <h1><i class="fa-solid fa-users"></i> <?= t('users') ?></h1>
  <button class="btn btn-primary btn-sm" id="btn-add-user"><i class="fa-solid fa-plus"></i> Add User</button>
</div>

<div id="users-list"><div class="page-loader"><div class="spinner"></div></div></div>

<!-- Add / Edit User Modal -->
<div class="modal-overlay" id="user-modal">
  <div class="modal-sheet" style="max-width:500px">
    <div class="modal-handle"></div>
    <div class="modal-title" id="user-modal-title"><?= t('add_user') ?></div>
    <div class="modal-body">
      <input type="hidden" id="u-id" value="">

      <div class="form-group">
        <label class="form-label">Name <span class="required">*</span></label>
        <input type="text" id="u-name" class="form-control" placeholder="Full name">
      </div>
      <div class="form-group">
        <label class="form-label">Email <span class="required">*</span></label>
        <input type="email" id="u-email" class="form-control" placeholder="user@example.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password <span id="pass-hint" class="text-muted text-xs">(required for new user)</span></label>
        <input type="password" id="u-pass" class="form-control" placeholder="Min 8 characters">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role</label>
          <select id="u-role" class="form-control" onchange="togglePermissions()">
            <option value="view_user"><?= t('role_view_user') ?></option>
            <option value="super_admin"><?= t('role_super_admin') ?></option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Language</label>
          <select id="u-lang" class="form-control">
            <option value="en">English</option>
            <option value="af">Afrikaans</option>
          </select>
        </div>
      </div>
      <div class="form-group" id="active-group">
        <label class="form-label">Active</label>
        <select id="u-active" class="form-control">
          <option value="1">Yes</option>
          <option value="0">No</option>
        </select>
      </div>

      <!-- Permissions — only for view_user role -->
      <div id="permissions-section" style="display:none">
        <div style="border-top:1px solid var(--border);margin:16px 0 12px"></div>
        <label class="form-label" style="margin-bottom:10px;display:block">
          <i class="fa-solid fa-key" style="color:var(--blue);margin-right:6px"></i>
          Access Permissions
          <span class="text-xs text-muted" style="font-weight:400;display:block;margin-top:2px">
            View-only users can never add, edit, or delete. Choose which sections they can access:
          </span>
        </label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <label class="perm-check"><input type="checkbox" id="perm-animals" checked> <i class="fa-solid fa-cow"></i> Animals &amp; Herds</label>
          <label class="perm-check"><input type="checkbox" id="perm-health"  checked> <i class="fa-solid fa-syringe"></i> Health Records</label>
          <label class="perm-check"><input type="checkbox" id="perm-weights" checked> <i class="fa-solid fa-weight-scale"></i> Weights</label>
          <label class="perm-check"><input type="checkbox" id="perm-calving" checked> <i class="fa-solid fa-baby"></i> Calving &amp; Breeding</label>
          <label class="perm-check"><input type="checkbox" id="perm-sales"   checked> <i class="fa-solid fa-handshake"></i> Sales &amp; Purchases</label>
          <label class="perm-check"><input type="checkbox" id="perm-reports" checked> <i class="fa-solid fa-chart-bar"></i> Reports</label>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('user-modal')"><?= t('cancel') ?></button>
      <button class="btn btn-primary" id="user-save-btn"><?= t('save') ?></button>
    </div>
  </div>
</div>

<style>
.perm-check {
  display: flex; align-items: center; gap: 8px;
  padding: 8px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-size: 0.875rem;
  transition: background 0.1s, border-color 0.1s;
  user-select: none;
}
.perm-check:hover { background: var(--blue-light); border-color: var(--blue); }
.perm-check input[type=checkbox] { accent-color: var(--blue); width: 16px; height: 16px; cursor: pointer; }
.perm-check i { color: var(--blue); width: 16px; text-align: center; }
</style>

<script>
const SELF_ID = <?= (int)$user['id'] ?>;

function togglePermissions() {
  const role    = document.getElementById('u-role').value;
  const section = document.getElementById('permissions-section');
  section.style.display = role === 'view_user' ? 'block' : 'none';
}

function loadUsers() {
  fetch('/api/users.php')
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(res => {
      const el = document.getElementById('users-list');
      if (!res.success) { el.innerHTML = `<div class="empty-state"><p>Error: ${escHtml(res.message||'Could not load users.')}</p></div>`; return; }
      if (!res.data?.length) { el.innerHTML = '<div class="empty-state"><p>No users.</p></div>'; return; }
      el.innerHTML = '<div class="list-card">' + res.data.map(u => {
        const isSelf  = u.id == SELF_ID;
        const roleTag = u.role === 'super_admin'
          ? '<span class="badge badge-green">Admin</span>'
          : '<span class="badge badge-grey">View</span>';
        const twoFaTag = u.totp_enabled
          ? '<span class="badge" style="background:#e8f5e9;color:#2e7d32"><i class="fa-solid fa-shield-halved"></i> 2FA On</span>'
          : '<span class="badge" style="background:#fff3e0;color:#e65100"><i class="fa-solid fa-shield-slash"></i> 2FA Off</span>';
        const inactiveTag = !u.is_active ? '<span class="badge badge-red">Inactive</span>' : '';
        return `
        <div class="list-item">
          <div class="item-icon" style="background:${u.role==='super_admin'?'var(--blue-light)':'var(--border)'}">
            <i class="fa-solid fa-${u.role==='super_admin'?'user-shield':'user'}" style="color:${u.role==='super_admin'?'var(--blue)':'var(--text-muted)'}"></i>
          </div>
          <div class="item-body">
            <div class="item-title">${escHtml(u.name)} ${isSelf ? '<span class="badge badge-blue">You</span>' : ''}</div>
            <div class="item-sub">${escHtml(u.email)}</div>
          </div>
          <div class="item-end" style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
            <div style="display:flex;gap:4px;flex-wrap:wrap;justify-content:flex-end">
              ${roleTag} ${twoFaTag} ${inactiveTag}
            </div>
          </div>
          <div style="display:flex;gap:6px;margin-left:8px;flex-shrink:0">
            <button class="btn btn-sm btn-secondary" onclick="editUser(event,${JSON.stringify(u).replace(/"/g,'&quot;')})">
              <i class="fa-solid fa-pen"></i>
            </button>
            ${!isSelf ? `<button class="btn btn-sm btn-danger" onclick="resetTwoFa(event,${u.id},'${escHtml(u.name).replace(/'/g,"\\'")}')">
              <i class="fa-solid fa-rotate-left"></i> 2FA
            </button>` : ''}
          </div>
        </div>`;
      }).join('') + '</div>';
    })
    .catch(err => {
      document.getElementById('users-list').innerHTML =
        `<div class="empty-state"><i class="fa-solid fa-triangle-exclamation" style="font-size:2rem;color:var(--orange);margin-bottom:12px"></i><p>Could not load users.</p><p class="text-xs text-muted">${escHtml(err.message)}</p></div>`;
    });
}

document.getElementById('btn-add-user').addEventListener('click', () => {
  document.getElementById('u-id').value   = '';
  document.getElementById('u-name').value = '';
  document.getElementById('u-email').value= '';
  document.getElementById('u-pass').value = '';
  document.getElementById('u-role').value = 'view_user';
  document.getElementById('u-lang').value = 'en';
  document.getElementById('u-active').value = '1';
  document.getElementById('pass-hint').textContent = '(required for new user)';
  document.getElementById('user-modal-title').textContent = '<?= t('add_user') ?>';
  // Reset permissions to all checked
  ['animals','health','weights','calving','sales','reports'].forEach(k => {
    document.getElementById('perm-'+k).checked = true;
  });
  togglePermissions();
  openModal('user-modal');
});

function editUser(e, u) {
  e.stopPropagation();
  document.getElementById('u-id').value     = u.id;
  document.getElementById('u-name').value   = u.name;
  document.getElementById('u-email').value  = u.email;
  document.getElementById('u-pass').value   = '';
  document.getElementById('u-role').value   = u.role;
  document.getElementById('u-lang').value   = u.language || 'en';
  document.getElementById('u-active').value = u.is_active ? '1' : '0';
  document.getElementById('pass-hint').textContent = '(leave blank to keep)';
  document.getElementById('user-modal-title').textContent = '<?= t('edit_user') ?>';

  // Load permissions
  const perms = u.permissions ? JSON.parse(u.permissions) : null;
  ['animals','health','weights','calving','sales','reports'].forEach(k => {
    document.getElementById('perm-'+k).checked = perms ? (perms[k] !== false) : true;
  });
  togglePermissions();
  openModal('user-modal');
}

document.getElementById('user-save-btn').addEventListener('click', () => {
  const id   = document.getElementById('u-id').value;
  const role = document.getElementById('u-role').value;
  const body = {
    name:      document.getElementById('u-name').value.trim(),
    email:     document.getElementById('u-email').value.trim(),
    role,
    language:  document.getElementById('u-lang').value,
    is_active: document.getElementById('u-active').value,
  };
  const pass = document.getElementById('u-pass').value;
  if (pass) body.password = pass;
  if (!id && !pass) { alert('Password required for new user.'); return; }
  if (!body.name || !body.email) { alert('Name and email are required.'); return; }

  // Include permissions for view_user
  if (role === 'view_user') {
    body.permissions = {
      animals: document.getElementById('perm-animals').checked,
      health:  document.getElementById('perm-health').checked,
      weights: document.getElementById('perm-weights').checked,
      calving: document.getElementById('perm-calving').checked,
      sales:   document.getElementById('perm-sales').checked,
      reports: document.getElementById('perm-reports').checked,
    };
  } else {
    body.permissions = null;
  }

  const method = id ? 'PUT' : 'POST';
  const url    = id ? `/api/users.php?id=${id}` : '/api/users.php';
  fetch(url, { method, headers: {'Content-Type':'application/json'}, body: JSON.stringify(body) })
    .then(r => r.json())
    .then(res => {
      if (res.success) { closeModal('user-modal'); loadUsers(); showToast(id ? 'User updated' : 'User created'); }
      else alert(res.message || 'Error.');
    });
});

function resetTwoFa(e, id, name) {
  e.stopPropagation();
  if (!confirm(`Reset 2FA for "${name}"?\n\nThey will be required to set it up again on next login.`)) return;
  fetch(`/api/users.php?id=${id}&action=reset_2fa`, { method: 'POST' })
    .then(r => r.json())
    .then(res => {
      if (res.success) { loadUsers(); showToast('2FA reset – user must set up again on next login'); }
      else alert(res.message || 'Error resetting 2FA.');
    });
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
loadUsers();
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
