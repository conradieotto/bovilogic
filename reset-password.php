<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';

// Only accessible from localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    http_response_code(403); die('Forbidden');
}

$done = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $rows = DB::exec('UPDATE users SET password = ? WHERE email = ?', [$hash, $email]);
        if ($rows === 0) {
            $error = 'No user found with that email.';
        } else {
            $done = true;
        }
    }
}

$users = DB::rows('SELECT email, name, role FROM users WHERE is_active = 1 ORDER BY email');
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Reset Password</title>
<style>body{font-family:sans-serif;max-width:400px;margin:60px auto;padding:16px}
input{width:100%;padding:10px;margin:8px 0 16px;box-sizing:border-box;border:1px solid #ccc;border-radius:6px}
button{background:#1B5E20;color:#fff;border:none;padding:12px 24px;border-radius:6px;cursor:pointer;width:100%}
.error{color:red;margin-bottom:12px}.success{color:green;margin-bottom:12px}
table{width:100%;border-collapse:collapse;margin-bottom:24px}
td,th{padding:8px;border:1px solid #ddd;font-size:13px}th{background:#f5f5f5}</style>
</head>
<body>
<h2>Reset Password</h2>

<h4>Existing Users</h4>
<table>
  <tr><th>Email</th><th>Name</th><th>Role</th></tr>
  <?php foreach ($users as $u): ?>
  <tr><td><?= htmlspecialchars($u['email']) ?></td><td><?= htmlspecialchars($u['name']) ?></td><td><?= $u['role'] ?></td></tr>
  <?php endforeach; ?>
</table>

<?php if ($done): ?>
  <p class="success">✓ Password updated successfully. <a href="/login.php">Go to login</a></p>
<?php else: ?>
  <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form method="POST">
    <label>Email</label>
    <input type="email" name="email" value="admin@bovilogic.com">
    <label>New Password</label>
    <input type="password" name="password" placeholder="Min 6 characters">
    <button type="submit">Reset Password</button>
  </form>
<?php endif; ?>
</body>
</html>
