<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

header('Content-Type: application/json');
$user   = apiRequireLogin();
$method = $_SERVER['REQUEST_METHOD'];
$herdId = (int)($_GET['herd_id'] ?? 0);
$id     = (int)($_GET['id']      ?? 0);

switch ($method) {
    case 'GET':
        $where  = ['1=1']; $params = [];
        if ($herdId) { $where[] = 'm.herd_id = ?'; $params[] = $herdId; }
        $rows = DB::rows(
            'SELECT m.*, h.name AS herd_name, fc.name AS from_camp_name, tc.name AS to_camp_name
             FROM herd_movements m
             LEFT JOIN herds h ON h.id = m.herd_id
             LEFT JOIN camps fc ON fc.id = m.from_camp_id
             LEFT JOIN camps tc ON tc.id = m.to_camp_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY m.move_date DESC',
            $params
        );
        jsonSuccess($rows);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        if (empty($b['herd_id']) || empty($b['to_camp_id']) || empty($b['move_date'])) {
            jsonError('herd_id, to_camp_id and move_date are required.');
        }
        $mid = DB::insert(
            'INSERT INTO herd_movements (uuid, herd_id, from_camp_id, to_camp_id, move_date, notes, created_by)
             VALUES (?,?,?,?,?,?,?)',
            [DB::uuid(), $b['herd_id'], $b['from_camp_id'] ?: null,
             $b['to_camp_id'], $b['move_date'], $b['notes'] ?? null, $user['id']]
        );
        // Update herd's current camp
        DB::exec('UPDATE herds SET camp_id = ? WHERE id = ?', [$b['to_camp_id'], $b['herd_id']]);
        logActivity('movement', $mid, 'create', "Herd #{$b['herd_id']} moved to camp #{$b['to_camp_id']}");
        jsonSuccess(['id' => $mid], 'Movement recorded', 201);

    default: jsonError('Method not allowed', 405);
}
