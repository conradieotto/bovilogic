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
            'SELECT s.*, a.ear_tag FROM sales s LEFT JOIN animals a ON a.id = s.animal_id ORDER BY s.sale_date DESC'
        );
        jsonSuccess($rows);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        if (empty($b['animal_id']) || empty($b['sale_date'])) jsonError('animal_id and sale_date required.');
        $sid = DB::insert(
            'INSERT INTO sales (uuid, animal_id, sale_date, price, buyer, weight_kg, reason, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
            [DB::uuid(), $b['animal_id'], $b['sale_date'], $b['price'] ?? 0, $b['buyer'] ?? null,
             $b['weight_kg'] ?: null, $b['reason'] ?? null, $b['notes'] ?? null, $user['id']]
        );
        // Mark animal as sold
        DB::exec("UPDATE animals SET animal_status = 'sold' WHERE id = ?", [$b['animal_id']]);
        logActivity('sale', $sid, 'create', "Sale recorded for animal #{$b['animal_id']}");
        jsonSuccess(['id' => $sid], 'Sale recorded', 201);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('DELETE FROM sales WHERE id = ?', [$id]);
        jsonSuccess(null, 'Deleted');

    default: jsonError('Method not allowed', 405);
}
