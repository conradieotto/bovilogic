<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

ob_start();
header('Content-Type: application/json');
$user   = apiRequireLogin();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $herdId = (int)($_GET['herd_id'] ?? 0);
    if (!$herdId) jsonError('herd_id required.');
    $rows = DB::rows(
        'SELECT * FROM pregnancy_tests WHERE herd_id = ? ORDER BY test_date DESC',
        [$herdId]
    );
    jsonSuccess($rows);
}

if ($method !== 'POST') { jsonError('Method not allowed', 405); }
if ($user['role'] !== 'super_admin') { jsonForbidden(); }

$b       = getJsonBody();
$herdId  = (int)($b['herd_id'] ?? 0);
$date    = $b['test_date'] ?? date('Y-m-d');
$results = $b['results'] ?? [];   // {animal_id: 'pregnant'|'open'}
$months  = $b['months']  ?? [];   // {animal_id: months_pregnant}
$total   = (int)($b['total_tested'] ?? 0);
$pCount  = (int)($b['total_pregnant'] ?? 0);
$rate    = (float)($b['pregnancy_rate'] ?? 0);

if (!$herdId || !$total) { jsonError('Missing required fields.'); }

try {
    // Save test record
    $testId = DB::insert(
        'INSERT INTO pregnancy_tests (herd_id, test_date, total_tested, total_pregnant, pregnancy_rate, created_by)
         VALUES (?, ?, ?, ?, ?, ?)',
        [$herdId, $date, $total, $pCount, $rate, $user['id']]
    );

    // Update each animal's breeding_status and breeding_date
    foreach ($results as $animalId => $status) {
        $animalId = (int)$animalId;
        if (!$animalId) continue;
        $breedingStatus = $status === 'pregnant' ? 'pregnant' : 'open';
        if ($status === 'pregnant' && isset($months[$animalId]) && (int)$months[$animalId] >= 1) {
            $daysBack = (int)$months[$animalId] * 30;
            $breedingDate = date('Y-m-d', strtotime($date . " - {$daysBack} days"));
            DB::exec(
                "UPDATE animals SET breeding_status = ?, breeding_date = ? WHERE id = ?",
                [$breedingStatus, $breedingDate, $animalId]
            );
        } else {
            DB::exec(
                "UPDATE animals SET breeding_status = ? WHERE id = ?",
                [$breedingStatus, $animalId]
            );
        }
    }

    // Update herd with latest pregnancy rate
    DB::exec(
        'UPDATE herds SET pregnancy_rate = ?, last_pregnancy_test = ? WHERE id = ?',
        [$rate, $date, $herdId]
    );
} catch (Throwable $e) {
    jsonError('Database error: ' . $e->getMessage());
}

logActivity('herd', $herdId, 'pregnancy_test', "Pregnancy test: {$rate}% ({$pCount}/{$total})");
jsonSuccess(['id' => $testId], 'Test saved', 201);
