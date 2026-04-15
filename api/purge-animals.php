<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

ob_start();
header('Content-Type: application/json');
$user = apiRequireLogin();
if ($user['role'] !== 'super_admin') jsonForbidden();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$b        = getJsonBody();
$statuses = $b['statuses'] ?? [];
$allowed  = ['sold', 'dead'];
$statuses = array_values(array_intersect($statuses, $allowed));
if (empty($statuses)) jsonError('No valid statuses provided.');

$placeholders = implode(',', array_fill(0, count($statuses), '?'));
$animals = DB::rows(
    "SELECT id, ear_tag FROM animals WHERE animal_status IN ($placeholders)",
    $statuses
);

if (empty($animals)) {
    jsonSuccess(['deleted' => 0], 'No animals to delete.');
}

$deleted = 0;
foreach ($animals as $a) {
    $id  = $a['id'];
    $tag = $a['ear_tag'];
    try {
        DB::exec('DELETE FROM weights      WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM vaccinations WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM treatments   WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM events       WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM sales        WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM purchases    WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM mortality    WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM calving      WHERE dam_id = ?',   [$id]);
        // Preserve ear tag in mother's calving record before unlinking
        try {
            DB::exec('UPDATE calving SET calf_tag = ?, calf_id = NULL WHERE calf_id = ?', [$tag, $id]);
        } catch (Throwable $e) {
            DB::exec('UPDATE calving SET calf_id = NULL WHERE calf_id = ?', [$id]);
        }
        DB::exec('DELETE FROM animals WHERE id = ?', [$id]);
        logActivity('animal', $id, 'delete', "Purged ($tag): {$a['animal_status']}");
        $deleted++;
    } catch (Throwable $e) {
        // Skip animals that fail and continue
    }
}

jsonSuccess(['deleted' => $deleted], "Deleted $deleted animal(s).");
