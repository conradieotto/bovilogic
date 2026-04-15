<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

ob_start();
header('Content-Type: application/json');
$user   = apiRequireLogin();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

switch ($method) {
    case 'GET':
        if ($id) {
            $farm = DB::row('SELECT * FROM farms WHERE id = ? AND is_active = 1', [$id]);
            $farm ? jsonSuccess($farm) : jsonNotFound();
        }
        $farms = DB::rows(
            'SELECT f.*, COUNT(a.id) AS animal_count
             FROM farms f
             LEFT JOIN animals a ON a.farm_id = f.id AND a.animal_status = \'active\'
             WHERE f.is_active = 1
             GROUP BY f.id ORDER BY f.name'
        );
        jsonSuccess($farms);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $body = getJsonBody();
        $name = trim($body['name'] ?? '');
        if (!$name) jsonError('Farm name is required.');
        $fid = DB::insert(
            'INSERT INTO farms (uuid, name, location, size_ha, notes, created_by) VALUES (?,?,?,?,?,?)',
            [DB::uuid(), $name, $body['location'] ?? null, $body['size_ha'] ?? null, $body['notes'] ?? null, $user['id']]
        );
        logActivity('farm', $fid, 'create', "Farm created: $name");
        jsonSuccess(['id' => $fid], 'Farm created', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $body = getJsonBody();
        $name = trim($body['name'] ?? '');
        if (!$name) jsonError('Farm name is required.');
        DB::exec('UPDATE farms SET name=?, location=?, size_ha=?, notes=? WHERE id=?',
            [$name, $body['location'] ?? null, $body['size_ha'] ?? null, $body['notes'] ?? null, $id]);
        logActivity('farm', $id, 'update', "Farm updated: $name");
        jsonSuccess(['id' => $id]);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('UPDATE farms SET is_active = 0 WHERE id = ?', [$id]);
        logActivity('farm', $id, 'delete', 'Farm deleted');
        jsonSuccess(null, 'Deleted');

    default:
        jsonError('Method not allowed', 405);
}
