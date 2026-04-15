<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

ob_start();
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
        $rows = DB::rows('SELECT * FROM treatments WHERE ' . implode(' AND ', $where) . ' ORDER BY treat_date DESC', $params);
        jsonSuccess($rows);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        if (empty($b['product']) || empty($b['treat_date'])) jsonError('Product and date required.');
        $tid = DB::insert(
            'INSERT INTO treatments (uuid, herd_id, animal_id, product, dosage, treat_date, notes, created_by) VALUES (?,?,?,?,?,?,?,?)',
            [DB::uuid(), $b['herd_id'] ?: null, $b['animal_id'] ?: null,
             $b['product'], $b['dosage'] ?? null, $b['treat_date'], $b['notes'] ?? null, $user['id']]
        );
        logActivity('treatment', $tid, 'create', "Treatment: {$b['product']}");
        jsonSuccess(['id' => $tid], 'Saved', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $b = getJsonBody();
        DB::exec('UPDATE treatments SET product=?, dosage=?, treat_date=?, notes=? WHERE id=?',
            [$b['product'], $b['dosage'] ?? null, $b['treat_date'], $b['notes'] ?? null, $id]);
        jsonSuccess(['id' => $id]);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('DELETE FROM treatments WHERE id = ?', [$id]);
        jsonSuccess(null, 'Deleted');

    default: jsonError('Method not allowed', 405);
}
