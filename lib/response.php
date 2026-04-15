<?php
/**
 * BoviLogic – JSON Response Helpers
 */

function jsonSuccess(mixed $data = null, string $message = 'OK', int $code = 200): never {
    if (ob_get_level()) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message = 'Error', int $code = 400, mixed $errors = null): never {
    if (ob_get_level()) ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $message, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonUnauthorized(string $message = 'Unauthorized'): never {
    jsonError($message, 401);
}

function jsonForbidden(string $message = 'Forbidden'): never {
    jsonError($message, 403);
}

function jsonNotFound(string $message = 'Not found'): never {
    jsonError($message, 404);
}

/** Get and decode JSON request body */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/** Require API request is authenticated */
function apiRequireLogin(): array {
    require_once __DIR__ . '/auth.php';
    if (!isLoggedIn()) jsonUnauthorized();
    return currentUser();
}

/** Require API request is super_admin */
function apiRequireAdmin(): array {
    $user = apiRequireLogin();
    if ($user['role'] !== 'super_admin') jsonForbidden('Admin access required');
    return $user;
}
