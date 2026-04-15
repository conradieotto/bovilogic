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
$farmId = (int)($_GET['farm_id'] ?? 0);

try {

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
                $ids = json_decode($herd['bull_ids'] ?? 'null', true);
                if (empty($ids) && !empty($herd['breeding_bull_id'])) {
                    $ids = [(int)$herd['breeding_bull_id']];
                }
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $herd['bulls'] = DB::rows("SELECT id, ear_tag FROM animals WHERE id IN ($placeholders)", $ids);
                } else {
                    $herd['bulls'] = [];
                }
            }
            $herd ? jsonSuccess($herd) : jsonNotFound();
        }
        $where  = ['h.is_active = 1'];
        $params = [];
        if ($farmId) { $where[] = 'h.farm_id = ?'; $params[] = $farmId; }

        $herds = DB::rows(
            'SELECT h.*, f.name AS farm_name, c.name AS camp_name,
                    COUNT(a.id) AS animal_count
             FROM herds h
             LEFT JOIN farms f ON f.id = h.farm_id
             LEFT JOIN camps c ON c.id = h.camp_id
             LEFT JOIN animals a ON a.herd_id = h.id AND a.animal_status = \'active\'
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY h.id ORDER BY h.name',
            $params
        );
        foreach ($herds as &$herd) {
            $ids = json_decode($herd['bull_ids'] ?? 'null', true);
            if (empty($ids) && !empty($herd['breeding_bull_id'])) {
                $ids = [(int)$herd['breeding_bull_id']];
            }
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $herd['bulls'] = DB::rows("SELECT id, ear_tag FROM animals WHERE id IN ($placeholders)", $ids);
            } else {
                $herd['bulls'] = [];
            }
        }
        unset($herd);
        jsonSuccess($herds);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        $name = trim($b['name'] ?? '');
        if (!$name) jsonError('Herd name is required.');
        if (empty($b['farm_id'])) jsonError('Farm is required.');
        $bullIds     = array_filter(array_map('intval', $b['bull_ids'] ?? []));
        $bullIdsJson = !empty($bullIds) ? json_encode(array_values($bullIds)) : null;
        $firstBullId = $bullIds[0] ?? null;
        try {
            $hid = DB::insert(
                'INSERT INTO herds (uuid, farm_id, camp_id, name, color, breeding_bull_id, bull_ids, breeding_start, breeding_end, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                [
                    DB::uuid(), $b['farm_id'], $b['camp_id'] ?: null,
                    $name, $b['color'] ?? '#4CAF50',
                    $firstBullId, $bullIdsJson,
                    $b['breeding_start'] ?: null, $b['breeding_end'] ?: null,
                    $b['notes'] ?? null, $user['id'],
                ]
            );
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'bull_ids') || str_contains($e->getMessage(), 'Unknown column')) {
                $hid = DB::insert(
                    'INSERT INTO herds (uuid, farm_id, camp_id, name, color, breeding_bull_id, breeding_start, breeding_end, notes, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?)',
                    [
                        DB::uuid(), $b['farm_id'], $b['camp_id'] ?: null,
                        $name, $b['color'] ?? '#4CAF50',
                        $firstBullId,
                        $b['breeding_start'] ?: null, $b['breeding_end'] ?: null,
                        $b['notes'] ?? null, $user['id'],
                    ]
                );
            } else {
                throw $e;
            }
        }
        logActivity('herd', $hid, 'create', "Herd created: $name");
        jsonSuccess(['id' => $hid], 'Herd created', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $b    = getJsonBody();
        $name = trim($b['name'] ?? '');
        if (!$name) jsonError('Herd name is required.');

        $oldHerd   = DB::row('SELECT farm_id, camp_id FROM herds WHERE id = ?', [$id]);
        $newCampId = $b['camp_id'] ?: null;
        $newFarmId = $b['farm_id'] ?? null;
        $moveDate  = $b['move_date'] ?? date('Y-m-d');

        $bullIds     = array_filter(array_map('intval', $b['bull_ids'] ?? []));
        $bullIdsJson = !empty($bullIds) ? json_encode(array_values($bullIds)) : null;
        $firstBullId = $bullIds[0] ?? null;
        try {
            DB::exec(
                'UPDATE herds SET name=?, color=?, farm_id=?, camp_id=?, breeding_bull_id=?, bull_ids=?,
                  breeding_start=?, breeding_end=?, notes=? WHERE id=?',
                [
                    $name, $b['color'] ?? '#4CAF50',
                    $newFarmId, $newCampId,
                    $firstBullId, $bullIdsJson,
                    $b['breeding_start'] ?: null, $b['breeding_end'] ?: null,
                    $b['notes'] ?? null, $id,
                ]
            );
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'bull_ids') || str_contains($e->getMessage(), 'Unknown column')) {
                DB::exec(
                    'UPDATE herds SET name=?, color=?, farm_id=?, camp_id=?, breeding_bull_id=?,
                      breeding_start=?, breeding_end=?, notes=? WHERE id=?',
                    [
                        $name, $b['color'] ?? '#4CAF50',
                        $newFarmId, $newCampId,
                        $firstBullId,
                        $b['breeding_start'] ?: null, $b['breeding_end'] ?: null,
                        $b['notes'] ?? null, $id,
                    ]
                );
            } else {
                throw $e;
            }
        }

        if ($newCampId && $newCampId != ($oldHerd['camp_id'] ?? null)) {
            try {
                DB::exec(
                    'UPDATE herd_movements SET date_out = ? WHERE herd_id = ? AND date_out IS NULL',
                    [$moveDate, $id]
                );
                DB::exec(
                    'INSERT INTO herd_movements (herd_id, farm_id, camp_id, date_in, created_by) VALUES (?,?,?,?,?)',
                    [$id, $newFarmId, $newCampId, $moveDate, $user['id']]
                );
            } catch (Throwable $e) {
                // herd_movements table not yet created — run migrate.php
            }
        }

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

} catch (Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    jsonError('Server error: ' . $e->getMessage());
}
