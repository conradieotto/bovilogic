<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

ob_start();
header('Content-Type: application/json');
$user  = apiRequireLogin();
$method = $_SERVER['REQUEST_METHOD'];
$damId  = (int)($_GET['dam_id'] ?? 0);
$id     = (int)($_GET['id']     ?? 0);

switch ($method) {
    case 'GET':
        if (!$damId) jsonError('dam_id required.');
        $rows = DB::rows(
            'SELECT c.*,
                    COALESCE(a.ear_tag, c.calf_tag) AS calf_tag,
                    a.sex AS calf_sex
             FROM calving c
             LEFT JOIN animals a ON a.id = c.calf_id
             WHERE c.dam_id = ? ORDER BY c.calving_date ASC',
            [$damId]
        );
        jsonSuccess($rows);

    case 'PUT':
        if (!$id) jsonError('Missing calving ID.');
        $b    = getJsonBody();
        $date = trim($b['calving_date'] ?? '');
        if (!$date) jsonError('Calving date is required.');
        $damId = DB::val('SELECT dam_id FROM calving WHERE id = ?', [$id]);
        DB::exec('UPDATE calving SET calving_date = ? WHERE id = ?', [$date, $id]);

        if ($damId) {
            // Recalculate last_calving_date and avg_calf_interval
            $dates = DB::rows(
                'SELECT calving_date FROM calving WHERE dam_id = ? ORDER BY calving_date ASC',
                [$damId]
            );
            $latest = end($dates)['calving_date'];
            $avg    = null;
            if (count($dates) >= 2) {
                $intervals = [];
                for ($i = 1; $i < count($dates); $i++) {
                    $d1 = new DateTime($dates[$i-1]['calving_date']);
                    $d2 = new DateTime($dates[$i]['calving_date']);
                    $intervals[] = $d1->diff($d2)->days;
                }
                $avg = round(array_sum($intervals) / count($intervals), 1);
            }
            DB::exec(
                'UPDATE animals SET last_calving_date = ?, avg_calf_interval = ? WHERE id = ?',
                [$latest, $avg, $damId]
            );
        }
        jsonSuccess(['id' => $id]);

    default:
        jsonError('Method not allowed', 405);
}
