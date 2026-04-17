<?php
ob_start();
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

header('Content-Type: application/json');

set_exception_handler(function(Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
});

$user     = apiRequireLogin();
$method   = $_SERVER['REQUEST_METHOD'];
$id       = (int)($_GET['id']        ?? 0);
$animalId = (int)($_GET['animal_id'] ?? 0);
$herdId   = (int)($_GET['herd_id']   ?? 0);
$overdue  = (bool)($_GET['overdue']  ?? false);
$dueSoon  = (bool)($_GET['due_soon'] ?? false);

ob_end_clean(); // discard any warnings captured so far, output will be pure JSON from here

switch ($method) {
    case 'GET':
        $where  = ['1=1']; $params = [];
        if ($animalId) { $where[] = 'animal_id = ?'; $params[] = $animalId; }
        if ($herdId)   { $where[] = 'herd_id = ?';   $params[] = $herdId; }
        if ($overdue)  { $where[] = 'completed = 0 AND due_date < CURDATE()'; }
        if ($dueSoon)  { $where[] = 'completed = 0 AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)'; }
        $rows = DB::rows(
            'SELECT v.*, a.ear_tag AS animal_tag, h.name AS herd_name FROM vaccinations v
             LEFT JOIN animals a ON a.id = v.animal_id
             LEFT JOIN herds h ON h.id = v.herd_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY v.due_date', $params);
        jsonSuccess($rows);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        if (empty($b['product']) || empty($b['due_date'])) jsonError('Product and due date required.');
        $vid = DB::insert(
            'INSERT INTO vaccinations (uuid, herd_id, animal_id, product, dosage, due_date, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?)',
            [DB::uuid(), $b['herd_id'] ?? null, $b['animal_id'] ?? null,
             $b['product'], $b['dosage'] ?? null, $b['due_date'], $b['notes'] ?? null, $user['id']]
        );
        logActivity('vaccination', $vid, 'create', "Vaccination added: {$b['product']}");
        jsonSuccess(['id' => $vid], 'Saved', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $b = getJsonBody();
        $completed        = !empty($b['completed']) ? 1 : 0;
        $completionDate   = $completed ? ($b['completion_date'] ?? date('Y-m-d')) : null;
        DB::exec(
            'UPDATE vaccinations SET product=?, dosage=?, due_date=?, completed=?, completion_date=?, notes=? WHERE id=?',
            [$b['product'] ?? '', $b['dosage'] ?? null, $b['due_date'] ?? date('Y-m-d'),
             $completed, $completionDate, $b['notes'] ?? null, $id]
        );
        logActivity('vaccination', $id, 'update', 'Vaccination updated');
        jsonSuccess(['id' => $id]);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('DELETE FROM vaccinations WHERE id = ?', [$id]);
        jsonSuccess(null, 'Deleted');

    default: jsonError('Method not allowed', 405);
}
