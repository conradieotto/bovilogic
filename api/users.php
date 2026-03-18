<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

header('Content-Type: application/json');
$user   = apiRequireAdmin();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

switch ($method) {
    case 'GET':
        if ($id) {
            $u = DB::row('SELECT id,uuid,name,email,role,language,is_active,last_login,created_at FROM users WHERE id=?', [$id]);
            $u ? jsonSuccess($u) : jsonNotFound();
        }
        $users = DB::rows('SELECT id,uuid,name,email,role,language,is_active,last_login,created_at FROM users ORDER BY name');
        jsonSuccess($users);

    case 'POST':
        $b    = getJsonBody();
        $name = trim($b['name']  ?? '');
        $email= strtolower(trim($b['email'] ?? ''));
        $pass = $b['password'] ?? '';
        $role = $b['role'] ?? 'view_user';
        if (!$name || !$email || !$pass) jsonError('Name, email and password are required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email.');
        if (strlen($pass) < 8) jsonError('Password must be at least 8 characters.');
        if (!in_array($role, ['super_admin','view_user'])) jsonError('Invalid role.');
        if (DB::val('SELECT id FROM users WHERE email=?',[$email])) jsonError('Email already exists.');

        $uid = DB::insert(
            'INSERT INTO users (uuid,name,email,password,role,language) VALUES (?,?,?,?,?,?)',
            [DB::uuid(), $name, $email, password_hash($pass, PASSWORD_BCRYPT), $role, $b['language'] ?? 'en']
        );
        logActivity('user', $uid, 'create', "User created: $email");
        jsonSuccess(['id' => $uid], 'User created', 201);

    case 'PUT':
        if (!$id) jsonError('Missing ID.');
        $b    = getJsonBody();
        $name = trim($b['name'] ?? '');
        $email= strtolower(trim($b['email'] ?? ''));
        $role = $b['role'] ?? 'view_user';
        if (!$name || !$email) jsonError('Name and email are required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email.');
        if (!in_array($role, ['super_admin','view_user'])) jsonError('Invalid role.');
        if (DB::val('SELECT id FROM users WHERE email=? AND id!=?',[$email,$id])) jsonError('Email already used.');

        $sets = ['name=?','email=?','role=?','language=?','is_active=?'];
        $vals = [$name, $email, $role, $b['language'] ?? 'en', isset($b['is_active']) ? (int)$b['is_active'] : 1];

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
        if ($id === $user['id']) jsonError('Cannot delete yourself.');
        DB::exec('UPDATE users SET is_active = 0 WHERE id = ?', [$id]);
        logActivity('user', $id, 'delete', 'User deactivated');
        jsonSuccess(null, 'User deactivated');

    default: jsonError('Method not allowed', 405);
}
