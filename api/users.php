<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

header('Content-Type: application/json');
$user   = apiRequireAdmin();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

// ── Special action: reset 2FA ────────────────────────────────────────────────
if ($method === 'POST' && $id && $action === 'reset_2fa') {
    if ($id === (int)$user['id']) jsonError('Cannot reset your own 2FA this way.');
    // Only run if 2FA columns exist
    $cols = DB::rows("SHOW COLUMNS FROM users");
    $colList = array_column($cols, 'Field');
    if (in_array('totp_enabled', $colList)) {
        DB::exec('UPDATE users SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?', [$id]);
    }
    logActivity('user', $id, 'update', '2FA reset by admin');
    jsonSuccess(null, '2FA reset successfully');
}

$validPerms = ['animals', 'health', 'weights', 'calving', 'sales', 'reports'];

// Detect which columns actually exist (graceful pre-migration fallback)
$existingCols = DB::rows("SHOW COLUMNS FROM users");
$colNames     = array_column($existingCols, 'Field');
$has2fa       = in_array('totp_enabled', $colNames);
$hasPerms     = in_array('permissions',  $colNames);

$selectCols = 'id,uuid,name,email,role,language,is_active,last_login,created_at'
    . ($has2fa   ? ',totp_enabled' : '')
    . ($hasPerms ? ',permissions'  : '');

switch ($method) {
    case 'GET':
        if ($id) {
            $u = DB::row("SELECT $selectCols FROM users WHERE id=?", [$id]);
            if (!$has2fa)   $u['totp_enabled'] = 0;
            if (!$hasPerms) $u['permissions']  = null;
            $u ? jsonSuccess($u) : jsonNotFound();
        }
        $users = DB::rows("SELECT $selectCols FROM users ORDER BY name");
        if (!$has2fa || !$hasPerms) {
            $users = array_map(function($u) use ($has2fa, $hasPerms) {
                if (!$has2fa)   $u['totp_enabled'] = 0;
                if (!$hasPerms) $u['permissions']  = null;
                return $u;
            }, $users);
        }
        jsonSuccess($users);

    case 'POST':
        $b     = getJsonBody();
        $name  = trim($b['name']  ?? '');
        $email = strtolower(trim($b['email'] ?? ''));
        $pass  = $b['password'] ?? '';
        $role  = $b['role'] ?? 'view_user';

        if (!$name || !$email || !$pass) jsonError('Name, email and password are required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email.');
        if (strlen($pass) < 8) jsonError('Password must be at least 8 characters.');
        if (!in_array($role, ['super_admin','view_user'])) jsonError('Invalid role.');
        if (DB::val('SELECT id FROM users WHERE email=?', [$email])) jsonError('Email already exists.');

        $permsJson = encodePermissions($b['permissions'] ?? null, $role, $validPerms);

        if ($hasPerms) {
            $uid = DB::insert(
                'INSERT INTO users (uuid,name,email,password,role,language,permissions) VALUES (?,?,?,?,?,?,?)',
                [DB::uuid(), $name, $email, password_hash($pass, PASSWORD_BCRYPT), $role, $b['language'] ?? 'en', $permsJson]
            );
        } else {
            $uid = DB::insert(
                'INSERT INTO users (uuid,name,email,password,role,language) VALUES (?,?,?,?,?,?)',
                [DB::uuid(), $name, $email, password_hash($pass, PASSWORD_BCRYPT), $role, $b['language'] ?? 'en']
            );
        }
        logActivity('user', $uid, 'create', "User created: $email");
        jsonSuccess(['id' => $uid], 'User created', 201);

    case 'PUT':
        if (!$id) jsonError('Missing ID.');
        $b     = getJsonBody();
        $name  = trim($b['name'] ?? '');
        $email = strtolower(trim($b['email'] ?? ''));
        $role  = $b['role'] ?? 'view_user';

        if (!$name || !$email) jsonError('Name and email are required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email.');
        if (!in_array($role, ['super_admin','view_user'])) jsonError('Invalid role.');
        if (DB::val('SELECT id FROM users WHERE email=? AND id!=?', [$email, $id])) jsonError('Email already used.');

        $permsJson = encodePermissions($b['permissions'] ?? null, $role, $validPerms);

        $sets = ['name=?','email=?','role=?','language=?','is_active=?'];
        $vals = [$name, $email, $role, $b['language'] ?? 'en', isset($b['is_active']) ? (int)$b['is_active'] : 1];
        if ($hasPerms) { $sets[] = 'permissions=?'; $vals[] = $permsJson; }

        if (!empty($b['password'])) {
            if (strlen($b['password']) < 8) jsonError('Password must be at least 8 characters.');
            $sets[] = 'password=?';
            $vals[] = password_hash($b['password'], PASSWORD_BCRYPT);
        }
        $vals[] = $id;
        DB::exec('UPDATE users SET ' . implode(',', $sets) . ' WHERE id=?', $vals);
        logActivity('user', $id, 'update', "User updated: $email");
        jsonSuccess(['id' => $id]);

    case 'DELETE':
        if (!$id) jsonError('Missing ID.');
        if ($id === (int)$user['id']) jsonError('Cannot delete yourself.');
        $target = DB::row('SELECT name, email FROM users WHERE id=?', [$id]);
        if (!$target) jsonNotFound();
        DB::exec('DELETE FROM users WHERE id = ?', [$id]);
        logActivity('user', $id, 'delete', 'User deleted: ' . $target['email']);
        jsonSuccess(null, 'User deleted');

    default: jsonError('Method not allowed', 405);
}

/** Encode permissions array to JSON string (null for super_admin or no restrictions) */
function encodePermissions($input, string $role, array $valid): ?string {
    if ($role !== 'view_user' || !is_array($input)) return null;
    $out = [];
    foreach ($valid as $k) {
        $out[$k] = (bool)($input[$k] ?? true);
    }
    // If all true, store null (means full access)
    if (!in_array(false, $out, true)) return null;
    return json_encode($out);
}
