<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

header('Content-Type: application/json');
apiRequireLogin();

$today   = date('Y-m-d');
$in7days = date('Y-m-d', strtotime('+7 days'));
$in30days= date('Y-m-d', strtotime('+30 days'));

$total   = DB::val("SELECT COUNT(*) FROM animals WHERE animal_status = 'active'");
$vDue    = DB::val("SELECT COUNT(*) FROM vaccinations WHERE completed = 0 AND due_date BETWEEN ? AND ?", [$today, $in7days]);
$vOver   = DB::val("SELECT COUNT(*) FROM vaccinations WHERE completed = 0 AND due_date < ?", [$today]);
$forSale = DB::val("SELECT COUNT(*) FROM animals WHERE animal_status = 'active' AND breeding_status = 'open' AND sex = 'male'"); // rough proxy; can be refined

// Upcoming calvings: pregnant cows, breeding_end + ~285 days
$calvings = DB::val(
    "SELECT COUNT(*) FROM animals a
     JOIN herds h ON h.id = a.herd_id
     WHERE a.breeding_status = 'pregnant'
       AND a.animal_status = 'active'
       AND DATE_ADD(COALESCE(h.breeding_end, h.breeding_start, CURDATE()), INTERVAL 285 DAY) BETWEEN ? AND ?",
    [$today, $in30days]
);

$farmSummary = DB::rows(
    "SELECT f.name, COUNT(a.id) AS animal_count
     FROM farms f
     LEFT JOIN animals a ON a.farm_id = f.id AND a.animal_status = 'active'
     WHERE f.is_active = 1
     GROUP BY f.id ORDER BY f.name"
);

jsonSuccess([
    'total_animals'    => (int)$total,
    'vaccines_due'     => (int)$vDue,
    'vaccines_overdue' => (int)$vOver,
    'upcoming_calvings'=> (int)$calvings,
    'for_sale'         => (int)$forSale,
    'farm_summary'     => $farmSummary,
]);
