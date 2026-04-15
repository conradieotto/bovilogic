<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

header('Content-Type: application/json');
apiRequireLogin();

$campId = (int)($_GET['camp_id'] ?? 0);
if (!$campId) jsonError('camp_id required.');

$rows = DB::rows(
    'SELECT m.*, h.name AS herd_name, f.name AS farm_name
     FROM herd_movements m
     LEFT JOIN herds h ON h.id = m.herd_id
     LEFT JOIN farms f ON f.id = m.farm_id
     WHERE m.camp_id = ?
     ORDER BY m.date_in DESC',
    [$campId]
);

jsonSuccess($rows);
