<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

header('Content-Type: application/json');
$user   = apiRequireLogin();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);
$farmId = (int)($_GET['farm_id'] ?? 0);

switch ($method) {
    case 'GET':
        if ($id) {
            $c = DB::row('SELECT c.*, f.name AS farm_name FROM camps c LEFT JOIN farms f ON f.id = c.farm_id WHERE c.id = ?', [$id]);
            $c ? jsonSuccess($c) : jsonNotFound();
        }
        $where = ['c.is_active = 1']; $params = [];
        if ($farmId) { $where[] = 'c.farm_id = ?'; $params[] = $farmId; }
        $camps = DB::rows(
            'SELECT c.*, f.name AS farm_name FROM camps c
             LEFT JOIN farms f ON f.id = c.farm_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY c.name',
            $params
        );
        jsonSuccess($camps);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        $name = trim($b['name'] ?? '');
        if (!$name) jsonError('Camp name is required.');
        if (empty($b['farm_id'])) jsonError('Farm is required.');
        $cid = DB::insert(
            'INSERT INTO camps (uuid, farm_id, name, size_ha, notes, created_by) VALUES (?,?,?,?,?,?)',
            [DB::uuid(), $b['farm_id'], $name, $b['size_ha'] ?: null, $b['notes'] ?? null, $user['id']]
        );
        logActivity('camp', $cid, 'create', "Camp created: $name");
        jsonSuccess(['id' => $cid], 'Camp created', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $b = getJsonBody(); $name = trim($b['name'] ?? '');
        if (!$name) jsonError('Name required.');
        DB::exec('UPDATE camps SET name=?, size_ha=?, notes=? WHERE id=?',
            [$name, $b['size_ha'] ?: null, $b['notes'] ?? null, $id]);
        logActivity('camp', $id, 'update', "Camp updated: $name");
        jsonSuccess(['id' => $id]);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('UPDATE camps SET is_active = 0 WHERE id = ?', [$id]);
        logActivity('camp', $id, 'delete', 'Camp deleted');
        jsonSuccess(null, 'Deleted');

    default: jsonError('Method not allowed', 405);
}
