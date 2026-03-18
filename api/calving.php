<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

header('Content-Type: application/json');
$user  = apiRequireLogin();
$method = $_SERVER['REQUEST_METHOD'];
$damId  = (int)($_GET['dam_id'] ?? 0);

switch ($method) {
    case 'GET':
        if (!$damId) jsonError('dam_id required.');
        $rows = DB::rows(
            'SELECT c.*, a.ear_tag AS calf_tag
             FROM calving c
             LEFT JOIN animals a ON a.id = c.calf_id
             WHERE c.dam_id = ? ORDER BY c.calving_date DESC',
            [$damId]
        );
        jsonSuccess($rows);
    default:
        jsonError('Method not allowed', 405);
}
