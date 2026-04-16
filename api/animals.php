<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/activity_logger.php';

ob_start();
header('Content-Type: application/json');
$user   = apiRequireLogin();
$method = $_SERVER['REQUEST_METHOD'];
$id     = (int)($_GET['id'] ?? 0);

switch ($method) {
    case 'GET':
        if ($id) {
            $a = DB::row(
                'SELECT a.*, f.name AS farm_name, h.name AS herd_name,
                        m.ear_tag AS mother_tag, fa.ear_tag AS father_tag
                 FROM animals a
                 LEFT JOIN farms f ON f.id = a.farm_id
                 LEFT JOIN herds h ON h.id = a.herd_id
                 LEFT JOIN animals m ON m.id = a.mother_id
                 LEFT JOIN animals fa ON fa.id = a.father_id
                 WHERE a.id = ?',
                [$id]
            );
            // Append last weight
            if ($a) {
                $lw = DB::row('SELECT weight_kg, weigh_date FROM weights WHERE animal_id = ? ORDER BY weigh_date DESC LIMIT 1', [$id]);
                $a['last_weight_kg']   = $lw['weight_kg'] ?? null;
                $a['last_weight_date'] = $lw['weigh_date'] ?? null;
            }
            $a ? jsonSuccess($a) : jsonNotFound();
        }
        // List with filters
        $q       = trim($_GET['q']      ?? '');
        $status  = trim($_GET['status'] ?? '');
        $cat     = trim($_GET['cat']    ?? $_GET['category'] ?? '');
        $herd    = (int)($_GET['herd_id']  ?? 0);
        $farm    = (int)($_GET['farm_id']  ?? 0);
        $forSale = !empty($_GET['for_sale']);

        $where = ['1=1'];
        $params = [];
        if ($q) {
            $where[] = '(a.ear_tag LIKE ? OR a.rfid LIKE ?)';
            $params[] = "%$q%"; $params[] = "%$q%";
        }
        if ($forSale) {
            $where[] = "a.animal_status = 'active'";
            $where[] = "a.category IN ('weaner','c_grade_cow')";
        } else {
            if ($status) { $where[] = 'a.animal_status = ?'; $params[] = $status; }
            if ($cat)    { $where[] = 'a.category = ?';      $params[] = $cat; }
        }
        if ($herd)   { $where[] = 'a.herd_id = ?';       $params[] = $herd; }
        if ($farm)   { $where[] = 'a.farm_id = ?';       $params[] = $farm; }

        $sql = 'SELECT a.*, f.name AS farm_name, h.name AS herd_name,
                       w.weight_kg AS last_weight_kg
                FROM animals a
                LEFT JOIN farms f ON f.id = a.farm_id
                LEFT JOIN herds h ON h.id = a.herd_id
                LEFT JOIN (
                    SELECT animal_id, weight_kg FROM weights w2
                    WHERE w2.id = (SELECT MAX(id) FROM weights WHERE animal_id = w2.animal_id)
                ) w ON w.animal_id = a.id
                WHERE ' . implode(' AND ', $where) .
               ' ORDER BY a.ear_tag LIMIT 200';

        jsonSuccess(DB::rows($sql, $params));

    case 'POST':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        $b = getJsonBody();
        $tag = trim($b['ear_tag'] ?? '');
        if (!$tag) jsonError('Ear tag is required.');
        if (!in_array($b['sex'] ?? '', ['male','female'])) jsonError('Invalid sex.');

        // Check unique ear tag
        $exists = DB::val('SELECT id FROM animals WHERE ear_tag = ?', [$tag]);
        if ($exists) jsonError('Ear tag already exists.');

        $animalId = DB::insert(
            'INSERT INTO animals (uuid, ear_tag, rfid, breed, sex, dob, farm_id, herd_id,
              mother_id, father_id, category, breeding_status, breeding_date, animal_status, comments, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [
                DB::uuid(), $tag,
                $b['rfid']    ?? null,
                $b['breed']   ?? null,
                $b['sex'],
                $b['dob']     ?: null,
                $b['farm_id'] ?: null,
                $b['herd_id'] ?: null,
                $b['mother_id'] ?: null,
                $b['father_id'] ?: null,
                $b['category'] ?? 'breeding_cow',
                $b['breeding_status'] ?? 'open',
                $b['breeding_date']   ?: null,
                $b['animal_status']   ?? 'active',
                $b['comments'] ?? null,
                $user['id'],
            ]
        );

        // If calf – create calving record and update mother
        if (!empty($b['mother_id']) && in_array($b['category'] ?? '', ['bull_calf','heifer_calf','calf']) && !empty($b['dob'])) {
            DB::insert(
                'INSERT INTO calving (uuid, dam_id, calf_id, calving_date, created_by) VALUES (?,?,?,?,?)',
                [DB::uuid(), $b['mother_id'], $animalId, $b['dob'], $user['id']]
            );
            // Recalculate average calf interval
            $calvings = DB::rows(
                'SELECT calving_date FROM calving WHERE dam_id = ? ORDER BY calving_date',
                [$b['mother_id']]
            );
            if (count($calvings) > 1) {
                $intervals = [];
                for ($i = 1; $i < count($calvings); $i++) {
                    $d1 = new DateTime($calvings[$i-1]['calving_date']);
                    $d2 = new DateTime($calvings[$i]['calving_date']);
                    $intervals[] = $d1->diff($d2)->days;
                }
                $avg = array_sum($intervals) / count($intervals);
                DB::exec('UPDATE animals SET last_calving_date=?, avg_calf_interval=?, breeding_status=\'calved\' WHERE id=?',
                    [$b['dob'], round($avg, 1), $b['mother_id']]);
            } else {
                DB::exec('UPDATE animals SET last_calving_date=?, breeding_status=\'calved\' WHERE id=?',
                    [$b['dob'], $b['mother_id']]);
            }
        }

        logActivity('animal', $animalId, 'create', "Animal created: $tag");
        jsonSuccess(['id' => $animalId], 'Animal created', 201);

    case 'PUT':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        $b   = getJsonBody();
        $tag = trim($b['ear_tag'] ?? '');
        if (!$tag) jsonError('Ear tag is required.');

        // Check unique (exclude self)
        $exists = DB::val('SELECT id FROM animals WHERE ear_tag = ? AND id != ?', [$tag, $id]);
        if ($exists) jsonError('Ear tag already used by another animal.');

        $newStatus  = $b['animal_status'] ?? 'active';
        $statusDate = in_array($newStatus, ['dead', 'sold']) ? ($b['status_date'] ?: date('Y-m-d')) : null;
        $statusNotes = in_array($newStatus, ['dead', 'sold']) ? ($b['status_notes'] ?? null) : null;

        try {
            DB::exec(
                'UPDATE animals SET ear_tag=?, rfid=?, breed=?, sex=?, dob=?, farm_id=?, herd_id=?,
                  mother_id=?, father_id=?, category=?, breeding_status=?, breeding_date=?,
                  animal_status=?, status_date=?, status_notes=?, comments=?
                 WHERE id=?',
                [
                    $tag, $b['rfid'] ?? null, $b['breed'] ?? null,
                    $b['sex'] ?? 'female', $b['dob'] ?: null,
                    $b['farm_id'] ?: null, $b['herd_id'] ?: null,
                    $b['mother_id'] ?: null, $b['father_id'] ?: null,
                    $b['category'] ?? 'breeding_cow',
                    $b['breeding_status'] ?? 'open',
                    $b['breeding_date']   ?: null,
                    $newStatus, $statusDate, $statusNotes,
                    $b['comments'] ?? null, $id,
                ]
            );
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'status_date') || str_contains($e->getMessage(), 'status_notes') || str_contains($e->getMessage(), 'Unknown column')) {
                DB::exec(
                    'UPDATE animals SET ear_tag=?, rfid=?, breed=?, sex=?, dob=?, farm_id=?, herd_id=?,
                      mother_id=?, father_id=?, category=?, breeding_status=?, breeding_date=?, animal_status=?, comments=?
                     WHERE id=?',
                    [
                        $tag, $b['rfid'] ?? null, $b['breed'] ?? null,
                        $b['sex'] ?? 'female', $b['dob'] ?: null,
                        $b['farm_id'] ?: null, $b['herd_id'] ?: null,
                        $b['mother_id'] ?: null, $b['father_id'] ?: null,
                        $b['category'] ?? 'breeding_cow',
                        $b['breeding_status'] ?? 'open',
                        $b['breeding_date']   ?: null,
                        $newStatus,
                        $b['comments'] ?? null, $id,
                    ]
                );
            } else {
                throw $e;
            }
        }
        logActivity('animal', $id, 'update', "Animal updated: $tag");
        jsonSuccess(['id' => $id]);

    case 'DELETE':
        if ($user['role'] !== 'super_admin') jsonForbidden();
        if (!$id) jsonError('Missing ID.');
        // Remove linked records first to avoid FK constraint errors
        DB::exec('DELETE FROM weights       WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM vaccinations  WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM treatments    WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM events        WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM sales         WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM purchases     WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM mortality     WHERE animal_id = ?', [$id]);
        DB::exec('DELETE FROM calving WHERE dam_id = ?', [$id]); // remove cow's own calving records if cow is deleted
        // Preserve ear tag in calving record before unlinking, so mother's history stays readable
        $tag = DB::val('SELECT ear_tag FROM animals WHERE id = ?', [$id]);
        try {
            DB::exec('UPDATE calving SET calf_tag = ?, calf_id = NULL WHERE calf_id = ?', [$tag, $id]);
        } catch (Throwable $e) {
            DB::exec('UPDATE calving SET calf_id = NULL WHERE calf_id = ?', [$id]);
        }
        DB::exec('DELETE FROM animals WHERE id = ?', [$id]);
        logActivity('animal', $id, 'delete', 'Animal deleted');
        jsonSuccess(null, 'Deleted');

    default:
        jsonError('Method not allowed', 405);
}
