<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

header('Content-Type: application/json');
$user   = apiRequireLogin();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

switch ($method) {
    case 'GET':
        $rows = DB::rows(
            'SELECT m.*, a.ear_tag FROM mortality m LEFT JOIN animals a ON a.id = m.animal_id ORDER BY m.death_date DESC'
        );
        jsonSuccess($rows);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        if (empty($b['animal_id']) || empty($b['death_date'])) jsonError('animal_id and death_date required.');
        $mid = DB::insert(
            'INSERT INTO mortality (uuid, animal_id, death_date, cause, notes, created_by) VALUES (?,?,?,?,?,?)',
            [DB::uuid(), $b['animal_id'], $b['death_date'], $b['cause'] ?? null, $b['notes'] ?? null, $user['id']]
        );
        // Mark animal as dead
        DB::exec("UPDATE animals SET animal_status = 'dead' WHERE id = ?", [$b['animal_id']]);
        logActivity('mortality', $mid, 'create', "Mortality recorded for animal #{$b['animal_id']}");
        jsonSuccess(['id' => $mid], 'Mortality recorded', 201);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('DELETE FROM mortality WHERE id = ?', [$id]);
        jsonSuccess(null, 'Deleted');

    default: jsonError('Method not allowed', 405);
}
