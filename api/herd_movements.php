<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

ob_start();
header('Content-Type: application/json');
apiRequireLogin();
try {

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

// Add computed fields
$today = date('Y-m-d');
foreach ($rows as &$row) {
    $endDate         = $row['date_out'] ?? $today;
    $days            = max(0, (int)round((strtotime($endDate) - strtotime($row['date_in'])) / 86400));
    $row['days']     = $days;
    $row['animal_days'] = ($row['animal_count'] ?? 0) * $days;
    $row['is_open']  = $row['date_out'] === null;
}
unset($row);

jsonSuccess($rows);
} catch (Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    jsonError('Server error: ' . $e->getMessage());
}
