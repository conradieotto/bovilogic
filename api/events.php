<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

header('Content-Type: application/json');
$user     = apiRequireLogin();
$method   = $_SERVER['REQUEST_METHOD'];
$id       = (int)($_GET['id']        ?? 0);
$animalId = (int)($_GET['animal_id'] ?? 0);
$herdId   = (int)($_GET['herd_id']   ?? 0);

switch ($method) {
    case 'GET':
        $where = ['1=1']; $params = [];
        if ($animalId) { $where[] = 'animal_id = ?'; $params[] = $animalId; }
        if ($herdId)   { $where[] = 'herd_id = ?';   $params[] = $herdId; }
        $rows = DB::rows('SELECT * FROM events WHERE ' . implode(' AND ', $where) . ' ORDER BY event_date DESC', $params);
        jsonSuccess($rows);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        if (empty($b['event_type']) || empty($b['event_date'])) jsonError('Event type and date required.');
        $eid = DB::insert(
            'INSERT INTO events (uuid, herd_id, animal_id, event_type, event_date, notes, created_by) VALUES (?,?,?,?,?,?,?)',
            [DB::uuid(), $b['herd_id'] ?: null, $b['animal_id'] ?: null,
             $b['event_type'], $b['event_date'], $b['notes'] ?? null, $user['id']]
        );
        logActivity('event', $eid, 'create', "Event: {$b['event_type']}");
        jsonSuccess(['id' => $eid], 'Saved', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $b = getJsonBody();
        DB::exec('UPDATE events SET event_type=?, event_date=?, notes=? WHERE id=?',
            [$b['event_type'], $b['event_date'], $b['notes'] ?? null, $id]);
        jsonSuccess(['id' => $id]);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('DELETE FROM events WHERE id = ?', [$id]);
        jsonSuccess(null, 'Deleted');

    default: jsonError('Method not allowed', 405);
}
