<?php
/**
 * BoviLogic – Authentication & Session Management
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Start session once
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Redirect to login if not authenticated */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/** Require a specific role; redirect to dashboard if insufficient */
function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== $role) {
        header('Location: ' . APP_URL . '/index.php?error=unauthorized');
        exit;
    }
}

/** Return currently logged-in user array or null */
function currentUser(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'name'     => $_SESSION['user_name'],
        'email'    => $_SESSION['user_email'],
        'role'     => $_SESSION['user_role'],
        'language' => $_SESSION['user_language'] ?? 'en',
    ];
}

/** Check if logged in */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/** Check if current user is super_admin */
function isSuperAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'super_admin';
}

/** Attempt login, return user array on success or null on failure */
function attemptLogin(string $email, string $password): ?array {
    $user = DB::row('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1', [strtolower(trim($email))]);
    if (!$user || !password_verify($password, $user['password'])) {
        return null;
    }

    // Refresh session
    session_regenerate_id(true);
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['user_language'] = $user['language'];

    DB::exec('UPDATE users SET last_login = NOW() WHERE id = ?', [$user['id']]);

    return $user;
}

/** Destroy session */
function logout(): void {
    session_unset();
    session_destroy();
}
