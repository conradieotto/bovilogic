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

<!-- Modal -->
<div class="modal-overlay" id="user-modal">
  <div class="modal-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title" id="user-modal-title"><?= t('add_user') ?></div>
    <div class="modal-body">
      <input type="hidden" id="u-id" value="">
      <div class="form-group"><label class="form-label">Name <span class="required">*</span></label><input type="text" id="u-name" class="form-control"></div>
      <div class="form-group"><label class="form-label">Email <span class="required">*</span></label><input type="email" id="u-email" class="form-control"></div>
      <div class="form-group"><label class="form-label">Password <span id="pass-hint">(required)</span></label><input type="password" id="u-pass" class="form-control" placeholder="Min 8 characters"></div>
      <div class="form-group">
        <label class="form-label">Role</label>
        <select id="u-role" class="form-control">
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
      <div class="form-group" id="active-group">
        <label class="form-label">Active</label>
        <select id="u-active" class="form-control">
          <option value="1">Yes</option>
          <option value="0">No</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('user-modal')"><?= t('cancel') ?></button>
      <button class="btn btn-primary" id="user-save-btn"><?= t('save') ?></button>
    </div>
  </div>
</div>

<script>
function loadUsers() {
  fetch('/api/users.php')
    .then(r=>r.json())
    .then(res => {
      const el = document.getElementById('users-list');
      if (!res.data?.length) { el.innerHTML='<div class="empty-state"><p>No users.</p></div>'; return; }
      el.innerHTML = '<div class="list-card">' + res.data.map(u=>`
        <div class="list-item">
          <div class="item-icon"><svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg></div>
          <div class="item-body">
            <div class="item-title">${escHtml(u.name)} ${u.id == <?= $user['id'] ?> ? '<span class="badge badge-blue">You</span>' : ''}</div>
            <div class="item-sub">${escHtml(u.email)}</div>
          </div>
          <div class="item-end">
            <span class="badge ${u.role==='super_admin'?'badge-green':'badge-grey'}">${u.role==='super_admin'?'Admin':'View'}</span>
            ${!u.is_active ? '<span class="badge badge-red">Inactive</span>' : ''}
          </div>
          <button class="btn btn-sm btn-secondary" style="margin-left:8px" onclick="editUser(event,${JSON.stringify(u).replace(/"/g,'&quot;')})">Edit</button>
        </div>
      `).join('') + '</div>';
    });
}

document.getElementById('btn-add-user').addEventListener('click', () => {
  document.getElementById('u-id').value = '';
  document.getElementById('u-name').value = '';
  document.getElementById('u-email').value = '';
  document.getElementById('u-pass').value = '';
  document.getElementById('u-role').value = 'view_user';
  document.getElementById('u-lang').value = 'en';
  document.getElementById('u-active').value = '1';
  document.getElementById('pass-hint').textContent = '(required)';
  document.getElementById('user-modal-title').textContent = '<?= t('add_user') ?>';
  openModal('user-modal');
});

function editUser(e, u) {
  e.stopPropagation();
  document.getElementById('u-id').value    = u.id;
  document.getElementById('u-name').value  = u.name;
  document.getElementById('u-email').value = u.email;
  document.getElementById('u-pass').value  = '';
  document.getElementById('u-role').value  = u.role;
  document.getElementById('u-lang').value  = u.language || 'en';
  document.getElementById('u-active').value= u.is_active ? '1' : '0';
  document.getElementById('pass-hint').textContent = '(leave blank to keep)';
  document.getElementById('user-modal-title').textContent = '<?= t('edit_user') ?>';
  openModal('user-modal');
}

document.getElementById('user-save-btn').addEventListener('click', () => {
  const id  = document.getElementById('u-id').value;
  const body = {
    name:      document.getElementById('u-name').value.trim(),
    email:     document.getElementById('u-email').value.trim(),
    role:      document.getElementById('u-role').value,
    language:  document.getElementById('u-lang').value,
    is_active: document.getElementById('u-active').value,
  };
  const pass = document.getElementById('u-pass').value;
  if (pass) body.password = pass;
  if (!id && !pass) { alert('Password required for new user.'); return; }

  const method = id ? 'PUT' : 'POST';
  const url    = id ? `/api/users.php?id=${id}` : '/api/users.php';
  fetch(url, { method, headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) })
    .then(r=>r.json())
    .then(res => {
      if (res.success) { closeModal('user-modal'); loadUsers(); }
      else alert(res.message || 'Error.');
    });
});

function escHtml(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
loadUsers();
</script>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
