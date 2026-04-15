<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

ob_start();
header('Content-Type: application/json');
$user     = apiRequireLogin();
$method   = $_SERVER['REQUEST_METHOD'];
$id       = (int)($_GET['id'] ?? 0);
$animalId = (int)($_GET['animal_id'] ?? 0);

switch ($method) {
    case 'GET':
        if ($id) {
            $w = DB::row('SELECT * FROM weights WHERE id = ?', [$id]);
            $w ? jsonSuccess($w) : jsonNotFound();
        }
        if (!$animalId) jsonError('animal_id required');
        $weights = DB::rows(
            'SELECT * FROM weights WHERE animal_id = ? ORDER BY weigh_date ASC',
            [$animalId]
        );
        // Calculate ADG: assign gain to the newer entry (i+1)
        for ($i = 0; $i < count($weights) - 1; $i++) {
            $d1   = new DateTime($weights[$i]['weigh_date']);
            $d2   = new DateTime($weights[$i+1]['weigh_date']);
            $days = max(1, $d1->diff($d2)->days);
            $diff = floatval($weights[$i+1]['weight_kg']) - floatval($weights[$i]['weight_kg']);
            $weights[$i+1]['adg']       = round($diff / $days, 3);
            $weights[$i+1]['kg_gained'] = round($diff, 1);
        }
        jsonSuccess($weights);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        if (empty($b['animal_id']) || empty($b['weight_kg']) || empty($b['weigh_date'])) {
            jsonError('animal_id, weight_kg and weigh_date are required.');
        }
        $wid = DB::insert(
            'INSERT INTO weights (uuid, animal_id, weight_kg, weigh_date, notes, created_by) VALUES (?,?,?,?,?,?)',
            [DB::uuid(), $b['animal_id'], $b['weight_kg'], $b['weigh_date'], $b['notes'] ?? null, $user['id']]
        );
        logActivity('weight', $wid, 'create', "Weight added: {$b['weight_kg']}kg for animal #{$b['animal_id']}");
        jsonSuccess(['id' => $wid], 'Weight saved', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $b = getJsonBody();
        DB::exec('UPDATE weights SET weight_kg=?, weigh_date=?, notes=? WHERE id=?',
            [$b['weight_kg'], $b['weigh_date'], $b['notes'] ?? null, $id]);
        logActivity('weight', $id, 'update', 'Weight updated');
        jsonSuccess(['id' => $id]);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('DELETE FROM weights WHERE id = ?', [$id]);
        logActivity('weight', $id, 'delete', 'Weight deleted');
        jsonSuccess(null, 'Deleted');

    default: jsonError('Method not allowed', 405);
}
