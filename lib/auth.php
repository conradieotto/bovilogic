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

/** Redirect to login if not fully authenticated (2FA must also be complete) */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        // If 2FA is pending, send to the right step
        if (!empty($_SESSION['2fa_pending_id'])) {
            $needsSetup = !empty($_SESSION['2fa_needs_setup']);
            header('Location: ' . APP_URL . ($needsSetup ? '/setup-2fa.php' : '/verify-2fa.php'));
        } else {
            header('Location: ' . APP_URL . '/login.php');
        }
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
        'id'          => $_SESSION['user_id'],
        'name'        => $_SESSION['user_name'],
        'email'       => $_SESSION['user_email'],
        'role'        => $_SESSION['user_role'],
        'language'    => $_SESSION['user_language'] ?? 'en',
        'permissions' => $_SESSION['user_permissions'] ?? null,
    ];
}

/** Check if fully logged in (2FA complete) */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/** Check if current user is super_admin */
function isSuperAdmin(): bool {
    return ($_SESSION['user_role'] ?? '') === 'super_admin';
}

/**
 * Check if the current user has a given permission section.
 * Super admins always pass. view_user defaults to all allowed unless restricted.
 * Permission keys: animals, health, weights, calving, sales, reports
 */
function hasPermission(string $key): bool {
    if (!isLoggedIn()) return false;
    if (isSuperAdmin()) return true;
    $perms = $_SESSION['user_permissions'] ?? null;
    if ($perms === null) return true; // no restrictions set → allow all
    return (bool)($perms[$key] ?? true);
}

/**
 * Attempt password login.
 * Returns the user row on success, null on failure.
 * Does NOT complete the session — caller must redirect to 2FA flow.
 */
function attemptLogin(string $email, string $password): ?array {
    $user = DB::row(
        'SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1',
        [strtolower(trim($email))]
    );
    if (!$user || !password_verify($password, $user['password'])) {
        return null;
    }
    return $user;
}

/**
 * Store a "2FA pending" state in session after password is verified.
 * The user is NOT yet logged in.
 */
function setPendingUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['2fa_pending_id']     = $user['id'];
    $_SESSION['2fa_pending_name']   = $user['name'];
    $_SESSION['2fa_pending_email']  = $user['email'];
    $_SESSION['2fa_pending_role']   = $user['role'];
    $_SESSION['2fa_pending_lang']   = $user['language'];
    $_SESSION['2fa_pending_perms']  = $user['permissions'] ?? null;
    // Flag whether they need to set up 2FA or just verify it
    $_SESSION['2fa_needs_setup']    = !$user['totp_enabled'];
}

/**
 * Complete login after 2FA is verified.
 * Moves pending state to full session.
 */
function completeLogin(): void {
    $id = $_SESSION['2fa_pending_id'] ?? null;
    if (!$id) return;

    session_regenerate_id(true);
    $_SESSION['user_id']          = $_SESSION['2fa_pending_id'];
    $_SESSION['user_name']        = $_SESSION['2fa_pending_name'];
    $_SESSION['user_email']       = $_SESSION['2fa_pending_email'];
    $_SESSION['user_role']        = $_SESSION['2fa_pending_role'];
    $_SESSION['user_language']    = $_SESSION['2fa_pending_lang'];

    $rawPerms = $_SESSION['2fa_pending_perms'] ?? null;
    $_SESSION['user_permissions'] = $rawPerms ? json_decode($rawPerms, true) : null;

    // Clear pending keys
    unset(
        $_SESSION['2fa_pending_id'],
        $_SESSION['2fa_pending_name'],
        $_SESSION['2fa_pending_email'],
        $_SESSION['2fa_pending_role'],
        $_SESSION['2fa_pending_lang'],
        $_SESSION['2fa_pending_perms'],
        $_SESSION['2fa_needs_setup']
    );

    DB::exec('UPDATE users SET last_login = NOW() WHERE id = ?', [$_SESSION['user_id']]);
}

/** Destroy session */
function logout(): void {
    session_unset();
    session_destroy();
}
