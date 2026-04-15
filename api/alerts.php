<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

ob_start();
header('Content-Type: application/json');
apiRequireLogin();

// Poor calving interval (>420 days)
$poorCalving = DB::rows(
    "SELECT a.id, a.ear_tag, a.avg_calf_interval,
            h.name AS herd_name
     FROM animals a
     LEFT JOIN herds h ON h.id = a.herd_id
     WHERE a.animal_status = 'active'
       AND a.category = 'breeding_cow'
       AND a.avg_calf_interval > 420
     ORDER BY a.avg_calf_interval DESC"
);

// Bad pregnancy rate herds (<=74%)
$badPregnancy = DB::rows(
    "SELECT h.id, h.name, h.pregnancy_rate, h.last_pregnancy_test,
            f.name AS farm_name
     FROM herds h
     LEFT JOIN farms f ON f.id = h.farm_id
     WHERE h.pregnancy_rate IS NOT NULL
       AND h.pregnancy_rate <= 74
     ORDER BY h.pregnancy_rate ASC"
);

// Weight loss: animals where latest weight < previous weight
$weightLoss = DB::rows(
    "SELECT a.id, a.ear_tag,
            w1.weight_kg AS latest_kg, w1.weigh_date AS latest_date,
            w2.weight_kg AS prev_kg
     FROM animals a
     JOIN weights w1 ON w1.animal_id = a.id
     JOIN weights w2 ON w2.animal_id = a.id
     WHERE a.animal_status = 'active'
       AND w1.id = (
           SELECT id FROM weights WHERE animal_id = a.id ORDER BY weigh_date DESC LIMIT 1
       )
       AND w2.id = (
           SELECT id FROM weights WHERE animal_id = a.id ORDER BY weigh_date DESC LIMIT 1 OFFSET 1
       )
       AND CAST(w1.weight_kg AS DECIMAL(10,2)) < CAST(w2.weight_kg AS DECIMAL(10,2))
     ORDER BY (CAST(w2.weight_kg AS DECIMAL(10,2)) - CAST(w1.weight_kg AS DECIMAL(10,2))) DESC"
);

jsonSuccess([
    'poor_calving'  => $poorCalving,
    'bad_pregnancy' => $badPregnancy,
    'weight_loss'   => $weightLoss,
]);
