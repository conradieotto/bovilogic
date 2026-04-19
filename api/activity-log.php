<?php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';

header('Content-Type: application/json');
apiRequireLogin();

$limit  = min((int)($_GET['limit']  ?? 20), 100);
$offset = (int)($_GET['offset'] ?? 0);
$entity = trim($_GET['entity_type'] ?? '');
$userId = (int)($_GET['user_id'] ?? 0);

$where = ["il.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"]; $params = [];
if ($entity) { $where[] = 'il.entity_type = ?'; $params[] = $entity; }
if ($userId) { $where[] = 'il.user_id = ?';     $params[] = $userId; }

$whereStr = implode(' AND ', $where);

// Group consecutive vaccination-create entries (same user + product + 5-min window)
// into a single row with a count, so herd batch vaccinations appear as one line.
$groupKey = "IF(il.entity_type = 'vaccination' AND il.action = 'create',
               CONCAT(IFNULL(il.user_id,''), '|', il.description, '|',
                      FLOOR(UNIX_TIMESTAMP(il.created_at) / 300)),
               il.id)";

$rows = DB::rows(
    "SELECT * FROM (
        SELECT MIN(il.id) AS id, il.entity_type, il.action, il.user_id,
               MAX(u.name) AS user_name, il.description,
               COUNT(*) AS cnt, MIN(il.created_at) AS created_at
        FROM activity_log il
        LEFT JOIN users u ON u.id = il.user_id
        WHERE {$whereStr}
        GROUP BY {$groupKey}
     ) AS g
     ORDER BY g.created_at DESC LIMIT {$limit} OFFSET {$offset}",
    $params
);

$total = (int)DB::val(
    "SELECT COUNT(*) FROM (
        SELECT 1 FROM activity_log il
        WHERE {$whereStr}
        GROUP BY {$groupKey}
     ) AS g",
    $params
);

jsonSuccess(['items' => $rows, 'total' => $total]);
