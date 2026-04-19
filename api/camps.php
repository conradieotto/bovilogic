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

// Compute grazing budget/used/remaining for a camp
function campGrazingInfo($campId, $sizeHa, $stockingRatio) {
    if (!$stockingRatio || !$sizeHa) return null;
    $budget = ($sizeHa / $stockingRatio) * 365;

    // Animal-days used within rolling 12-month window
    // Try with animal_count column; if column missing fall back to day-count only
    try {
        $used = (float)DB::val(
            'SELECT COALESCE(SUM(
                COALESCE(animal_count, 0) *
                GREATEST(0, DATEDIFF(
                    COALESCE(date_out, CURDATE()),
                    GREATEST(date_in, DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
                ))
             ), 0)
             FROM herd_movements
             WHERE camp_id = ?
               AND COALESCE(date_out, CURDATE()) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)',
            [$campId]
        );
    } catch (Throwable $e) {
        // animal_count column not yet present — count plain days instead
        $used = (float)DB::val(
            'SELECT COALESCE(SUM(
                GREATEST(0, DATEDIFF(
                    COALESCE(date_out, CURDATE()),
                    GREATEST(date_in, DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
                ))
             ), 0)
             FROM herd_movements
             WHERE camp_id = ?
               AND COALESCE(date_out, CURDATE()) >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)',
            [$campId]
        );
    }

    $remaining = max(0.0, $budget - $used);
    $pctUsed   = $budget > 0 ? min(100, round($used / $budget * 100)) : 0;

    // Current active animal count in this camp
    $currentAnimals = (int)DB::val(
        'SELECT COUNT(a.id) FROM herds h
         LEFT JOIN animals a ON a.herd_id = h.id AND a.animal_status = \'active\'
         WHERE h.camp_id = ? AND h.is_active = 1',
        [$campId]
    );

    $daysLeft  = null;
    $moveOutBy = null;
    if ($currentAnimals > 0) {
        $daysLeft  = (int)round($remaining / $currentAnimals);
        $moveOutBy = date('Y-m-d', strtotime("+{$daysLeft} days"));
    }

    return [
        'budget'          => (int)round($budget),
        'used'            => (int)round($used),
        'remaining'       => (int)round($remaining),
        'pct_used'        => $pctUsed,
        'days_left'       => $daysLeft,
        'move_out_by'     => $moveOutBy,
        'current_animals' => $currentAnimals,
    ];
}

try {
switch ($method) {
    case 'GET':
        if ($id) {
            $c = DB::row(
                'SELECT c.*, f.name AS farm_name FROM camps c
                 LEFT JOIN farms f ON f.id = c.farm_id WHERE c.id = ?',
                [$id]
            );
            if ($c) {
            try { $c['grazing'] = campGrazingInfo($c['id'], $c['size_ha'], $c['stocking_ratio']); }
            catch (Throwable $e) { $c['grazing'] = null; }
        }
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
        foreach ($camps as &$c) {
            try {
                $c['grazing'] = campGrazingInfo($c['id'], $c['size_ha'], $c['stocking_ratio']);
            } catch (Throwable $e) {
                $c['grazing'] = null;
            }
        }
        unset($c);
        jsonSuccess($camps);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        $name = trim($b['name'] ?? '');
        if (!$name) jsonError('Camp name is required.');
        if (empty($b['farm_id'])) jsonError('Farm is required.');
        $cid = DB::insert(
            'INSERT INTO camps (uuid, farm_id, name, size_ha, stocking_ratio, notes, created_by) VALUES (?,?,?,?,?,?,?)',
            [DB::uuid(), $b['farm_id'], $name, $b['size_ha'] ?: null,
             $b['stocking_ratio'] ?: null, $b['notes'] ?? null, $user['id']]
        );
        logActivity('camp', $cid, 'create', "Camp created: $name");
        jsonSuccess(['id' => $cid], 'Camp created', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $b = getJsonBody(); $name = trim($b['name'] ?? '');
        if (!$name) jsonError('Name required.');
        DB::exec('UPDATE camps SET name=?, size_ha=?, stocking_ratio=?, notes=? WHERE id=?',
            [$name, $b['size_ha'] ?: null, $b['stocking_ratio'] ?: null, $b['notes'] ?? null, $id]);
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
} catch (Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    // If column missing, give a helpful hint
    if (str_contains($e->getMessage(), 'stocking_ratio') || str_contains($e->getMessage(), 'Unknown column')) {
        jsonError('Database needs updating — please run migrate.php first.');
    }
    jsonError('Server error: ' . $e->getMessage());
}
