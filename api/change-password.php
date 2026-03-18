<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

header('Content-Type: application/json');
$user = apiRequireLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('POST required', 405);

$b       = getJsonBody();
$current = $b['current_password'] ?? '';
$new     = $b['new_password']     ?? '';

if (!$current || !$new) jsonError('Both fields required.');
if (strlen($new) < 8)   jsonError('New password must be at least 8 characters.');

$dbUser = DB::row('SELECT password FROM users WHERE id = ?', [$user['id']]);
if (!password_verify($current, $dbUser['password'])) {
    jsonError('Current password is incorrect.');
}

DB::exec('UPDATE users SET password = ? WHERE id = ?', [password_hash($new, PASSWORD_BCRYPT), $user['id']]);
jsonSuccess(null, 'Password changed successfully.');
