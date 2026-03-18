<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

header('Content-Type: application/json');
apiRequireLogin();

$type   = trim($_GET['type']    ?? 'monthly');
$from   = trim($_GET['from']    ?? date('Y-m-01'));
$to     = trim($_GET['to']      ?? date('Y-m-t'));
$herdId = (int)($_GET['herd_id'] ?? 0);
$farmId = (int)($_GET['farm_id'] ?? 0);

$data = [];

switch ($type) {
    case 'monthly':
        // Animal counts by category
        $categories = DB::rows(
            "SELECT category, COUNT(*) AS cnt
             FROM animals WHERE animal_status = 'active'
             GROUP BY category ORDER BY category"
        );

        // Total active
        $total = DB::val("SELECT COUNT(*) FROM animals WHERE animal_status = 'active'");

        // Sales in period
        $sales = DB::rows(
            "SELECT s.*, a.ear_tag, a.category, a.breed
             FROM sales s LEFT JOIN animals a ON a.id = s.animal_id
             WHERE s.sale_date BETWEEN ? AND ? ORDER BY s.sale_date",
            [$from, $to]
        );
        $salesTotal = array_sum(array_column($sales, 'price'));

        // Mortality in period
        $mortality = DB::rows(
            "SELECT m.*, a.ear_tag, a.category
             FROM mortality m LEFT JOIN animals a ON a.id = m.animal_id
             WHERE m.death_date BETWEEN ? AND ?",
            [$from, $to]
        );

        // Newborns (calves born in period)
        $newborns = DB::rows(
            "SELECT a.ear_tag, a.dob, a.breed, a.sex,
                    m.ear_tag AS dam_tag
             FROM animals a
             LEFT JOIN animals m ON m.id = a.mother_id
             WHERE a.category = 'calf' AND a.dob BETWEEN ? AND ?",
            [$from, $to]
        );

        // Farm summary
        $farmSummary = DB::rows(
            "SELECT f.name, COUNT(a.id) AS animal_count
             FROM farms f
             LEFT JOIN animals a ON a.farm_id = f.id AND a.animal_status = 'active'
             WHERE f.is_active = 1 GROUP BY f.id ORDER BY f.name"
        );

        $data = [
            'period'       => ['from' => $from, 'to' => $to],
            'total_active' => (int)$total,
            'by_category'  => $categories,
            'farm_summary' => $farmSummary,
            'sales'        => $sales,
            'sales_total'  => (float)$salesTotal,
            'mortality'    => $mortality,
            'newborns'     => $newborns,
        ];
        break;

    case 'herd':
        if (!$herdId) jsonError('herd_id required for herd report.');
        $herd = DB::row('SELECT * FROM herds WHERE id = ?', [$herdId]);
        if (!$herd) jsonNotFound('Herd not found.');

        // Animals by category
        $categories = DB::rows(
            "SELECT category, COUNT(*) AS cnt FROM animals WHERE herd_id = ? AND animal_status = 'active' GROUP BY category",
            [$herdId]
        );
        $total = DB::val("SELECT COUNT(*) FROM animals WHERE herd_id = ? AND animal_status = 'active'", [$herdId]);

        // Average weight per category
        $avgWeights = DB::rows(
            "SELECT a.category, ROUND(AVG(last_w.weight_kg), 1) AS avg_weight
             FROM animals a
             INNER JOIN (
               SELECT w.animal_id, w.weight_kg
               FROM weights w
               WHERE w.id = (SELECT MAX(id) FROM weights WHERE animal_id = w.animal_id)
             ) last_w ON last_w.animal_id = a.id
             WHERE a.herd_id = ? AND a.animal_status = 'active'
             GROUP BY a.category",
            [$herdId]
        );

        // Vaccinations due/overdue
        $vaccDue    = (int)DB::val("SELECT COUNT(*) FROM vaccinations WHERE herd_id=? AND completed=0 AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)", [$herdId]);
        $vaccOverdue= (int)DB::val("SELECT COUNT(*) FROM vaccinations WHERE herd_id=? AND completed=0 AND due_date < CURDATE()", [$herdId]);

        // Breeding performance
        $pregnant = (int)DB::val("SELECT COUNT(*) FROM animals WHERE herd_id=? AND breeding_status='pregnant' AND animal_status='active'", [$herdId]);
        $calved   = (int)DB::val("SELECT COUNT(*) FROM animals WHERE herd_id=? AND breeding_status='calved' AND animal_status='active'", [$herdId]);
        $cows     = (int)DB::val("SELECT COUNT(*) FROM animals WHERE herd_id=? AND category='cow' AND animal_status='active'", [$herdId]);

        $data = [
            'herd'               => $herd,
            'total_active'       => (int)$total,
            'by_category'        => $categories,
            'avg_weight_by_cat'  => $avgWeights,
            'vaccinations_due'   => $vaccDue,
            'vaccinations_overdue'=> $vaccOverdue,
            'pregnant_cows'      => $pregnant,
            'calved_cows'        => $calved,
            'total_cows'         => $cows,
        ];
        break;

    default:
        jsonError('Unknown report type.');
}

jsonSuccess($data);
