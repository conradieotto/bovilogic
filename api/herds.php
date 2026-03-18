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
            $herd = DB::row(
                'SELECT h.*, f.name AS farm_name, c.name AS camp_name,
                        b.ear_tag AS bull_tag
                 FROM herds h
                 LEFT JOIN farms f ON f.id = h.farm_id
                 LEFT JOIN camps c ON c.id = h.camp_id
                 LEFT JOIN animals b ON b.id = h.breeding_bull_id
                 WHERE h.id = ?',
                [$id]
            );
            if ($herd) {
                $herd['animal_count'] = DB::val("SELECT COUNT(*) FROM animals WHERE herd_id = ? AND animal_status = 'active'", [$id]);
            }
            $herd ? jsonSuccess($herd) : jsonNotFound();
        }
        $where  = ['h.is_active = 1'];
        $params = [];
        if ($farmId) { $where[] = 'h.farm_id = ?'; $params[] = $farmId; }

        $herds = DB::rows(
            'SELECT h.*, f.name AS farm_name, c.name AS camp_name,
                    b.ear_tag AS bull_tag,
                    COUNT(a.id) AS animal_count
             FROM herds h
             LEFT JOIN farms f ON f.id = h.farm_id
             LEFT JOIN camps c ON c.id = h.camp_id
             LEFT JOIN animals b ON b.id = h.breeding_bull_id
             LEFT JOIN animals a ON a.herd_id = h.id AND a.animal_status = \'active\'
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY h.id ORDER BY h.name',
            $params
        );
        jsonSuccess($herds);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        $name = trim($b['name'] ?? '');
        if (!$name) jsonError('Herd name is required.');
        if (empty($b['farm_id'])) jsonError('Farm is required.');
        $hid = DB::insert(
            'INSERT INTO herds (uuid, farm_id, camp_id, name, color, breeding_bull_id, breeding_start, breeding_end, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [
                DB::uuid(), $b['farm_id'], $b['camp_id'] ?: null,
                $name, $b['color'] ?? '#4CAF50',
                $b['breeding_bull_id'] ?: null,
                $b['breeding_start'] ?: null, $b['breeding_end'] ?: null,
                $b['notes'] ?? null, $user['id'],
            ]
        );
        logActivity('herd', $hid, 'create', "Herd created: $name");
        jsonSuccess(['id' => $hid], 'Herd created', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $b    = getJsonBody();
        $name = trim($b['name'] ?? '');
        if (!$name) jsonError('Herd name is required.');
        DB::exec(
            'UPDATE herds SET name=?, color=?, farm_id=?, camp_id=?, breeding_bull_id=?,
              breeding_start=?, breeding_end=?, notes=? WHERE id=?',
            [
                $name, $b['color'] ?? '#4CAF50',
                $b['farm_id'] ?? null, $b['camp_id'] ?: null,
                $b['breeding_bull_id'] ?: null,
                $b['breeding_start'] ?: null, $b['breeding_end'] ?: null,
                $b['notes'] ?? null, $id,
            ]
        );
        logActivity('herd', $id, 'update', "Herd updated: $name");
        jsonSuccess(['id' => $id]);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('UPDATE herds SET is_active = 0 WHERE id = ?', [$id]);
        logActivity('herd', $id, 'delete', 'Herd deleted');
        jsonSuccess(null, 'Deleted');

    default:
        jsonError('Method not allowed', 405);
}
