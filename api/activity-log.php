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

$where = ['l.created_at >= DATE_FORMAT(NOW(), \'%Y-%m-01\')']; $params = [];
if ($entity) { $where[] = 'l.entity_type = ?'; $params[] = $entity; }
if ($userId) { $where[] = 'l.user_id = ?';     $params[] = $userId; }

$rows = DB::rows(
    'SELECT l.*, u.name AS user_name
     FROM activity_log l
     LEFT JOIN users u ON u.id = l.user_id
     WHERE ' . implode(' AND ', $where) .
    ' ORDER BY l.created_at DESC LIMIT ' . $limit . ' OFFSET ' . $offset,
    $params
);

$total = DB::val('SELECT COUNT(*) FROM activity_log l WHERE ' . implode(' AND ', $where), $params);

jsonSuccess(['items' => $rows, 'total' => (int)$total]);
