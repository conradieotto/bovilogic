<?php
/**
 * BoviLogic – Activity Logger
 */

require_once __DIR__ . '/db.php';

function logActivity(string $entityType, ?int $entityId, string $action, string $description = '', ?int $userId = null): void {
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

    DB::exec(
        'INSERT INTO activity_log (user_id, entity_type, entity_id, action, description, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$userId, $entityType, $entityId, $action, $description, $ip]
    );
}
