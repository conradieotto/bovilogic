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
            'SELECT p.*, a.ear_tag FROM purchases p LEFT JOIN animals a ON a.id = p.animal_id ORDER BY p.purchase_date DESC'
        );
        jsonSuccess($rows);

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        if (empty($b['animal_id']) || empty($b['purchase_date'])) jsonError('animal_id and purchase_date required.');
        $pid = DB::insert(
            'INSERT INTO purchases (uuid, animal_id, purchase_date, price, seller, notes, created_by) VALUES (?,?,?,?,?,?,?)',
            [DB::uuid(), $b['animal_id'], $b['purchase_date'], $b['price'] ?? 0,
             $b['seller'] ?? null, $b['notes'] ?? null, $user['id']]
        );
        logActivity('purchase', $pid, 'create', "Purchase recorded for animal #{$b['animal_id']}");
        jsonSuccess(['id' => $pid], 'Purchase recorded', 201);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        DB::exec('DELETE FROM purchases WHERE id = ?', [$id]);
        jsonSuccess(null, 'Deleted');

    default: jsonError('Method not allowed', 405);
}
