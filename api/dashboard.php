<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

ob_start();
header('Content-Type: application/json');
apiRequireLogin();

$today   = date('Y-m-d');
$in7days = date('Y-m-d', strtotime('+7 days'));
$in30days= date('Y-m-d', strtotime('+30 days'));

$total   = DB::val("SELECT COUNT(*) FROM animals WHERE animal_status = 'active'");
$vDue    = DB::val("SELECT COUNT(*) FROM vaccinations WHERE completed = 0 AND due_date BETWEEN ? AND ?", [$today, $in7days]);
$vOver   = DB::val("SELECT COUNT(*) FROM vaccinations WHERE completed = 0 AND due_date < ?", [$today]);
$forSale = DB::val("SELECT COUNT(*) FROM animals WHERE animal_status = 'active' AND category IN ('weaner','c_grade_cow')");

// Upcoming calvings: all pregnant cows
try {
    $calvings = DB::val(
        "SELECT COUNT(*) FROM animals
         WHERE breeding_status = 'pregnant'
           AND animal_status = 'active'"
    );
    $calvingList = DB::rows(
        "SELECT a.id, a.ear_tag,
                COALESCE(a.breeding_date, h.breeding_start) AS breeding_date,
                DATE_ADD(COALESCE(a.breeding_date, h.breeding_start), INTERVAL 285 DAY) AS expected_calving
         FROM animals a
         LEFT JOIN herds h ON h.id = a.herd_id
         WHERE a.breeding_status = 'pregnant'
           AND a.animal_status = 'active'
         ORDER BY expected_calving ASC"
    );
} catch (Throwable $e) {
    $calvings    = 0;
    $calvingList = [];
}

// Red-flag alert counts
$poorCalvingCount = DB::val(
    "SELECT COUNT(*) FROM animals WHERE animal_status='active' AND category='breeding_cow' AND avg_calf_interval > 420"
);
$badPregnancyCount = DB::val(
    "SELECT COUNT(*) FROM herds WHERE pregnancy_rate IS NOT NULL AND pregnancy_rate <= 74"
);
$weightLossCount = DB::val(
    "SELECT COUNT(*) FROM animals a
     WHERE a.animal_status = 'active'
       AND EXISTS (
           SELECT 1 FROM weights w1
           JOIN weights w2 ON w2.animal_id = a.id
           WHERE w1.animal_id = a.id
             AND w1.id = (SELECT id FROM weights WHERE animal_id = a.id ORDER BY weigh_date DESC LIMIT 1)
             AND w2.id = (SELECT id FROM weights WHERE animal_id = a.id ORDER BY weigh_date DESC LIMIT 1 OFFSET 1)
             AND CAST(w1.weight_kg AS DECIMAL(10,2)) < CAST(w2.weight_kg AS DECIMAL(10,2))
       )"
);

$farmRows = DB::rows(
    "SELECT f.id, f.name, a.category, COUNT(a.id) AS cat_count
     FROM farms f
     LEFT JOIN animals a ON a.farm_id = f.id AND a.animal_status = 'active'
     WHERE f.is_active = 1
     GROUP BY f.id, f.name, a.category
     ORDER BY f.name, a.category"
);
$farmMap = [];
foreach ($farmRows as $row) {
    $fid = $row['id'];
    if (!isset($farmMap[$fid])) {
        $farmMap[$fid] = ['id' => $fid, 'name' => $row['name'], 'animal_count' => 0, 'categories' => []];
    }
    if ($row['category']) {
        $farmMap[$fid]['categories'][] = ['category' => $row['category'], 'cnt' => (int)$row['cat_count']];
        $farmMap[$fid]['animal_count'] += (int)$row['cat_count'];
    }
}
$farmSummary = array_values($farmMap);

jsonSuccess([
    'total_animals'      => (int)$total,
    'vaccines_due'       => (int)$vDue,
    'vaccines_overdue'   => (int)$vOver,
    'upcoming_calvings'  => (int)$calvings,
    'calving_list'       => $calvingList,
    'for_sale'           => (int)$forSale,
    'farm_summary'       => $farmSummary,
    'poor_calving_count' => (int)$poorCalvingCount,
    'bad_pregnancy_count'=> (int)$badPregnancyCount,
    'weight_loss_count'  => (int)$weightLossCount,
]);
