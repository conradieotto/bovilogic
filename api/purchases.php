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
    ob_end_clean();
    $rows = DB::rows('SELECT * FROM purchases ORDER BY date_purchased DESC');
    jsonSuccess($rows);
}

if ($method !== 'POST') {
    ob_end_clean();
    jsonError('Method not allowed', 405);
}

if ($user['role'] !== 'super_admin') { ob_end_clean(); jsonForbidden(); }

$b        = getJsonBody();
$date     = $b['date_purchased'] ?? null;
$price    = isset($b['price_zar']) ? (float)$b['price_zar'] : null;
$seller   = trim($b['seller'] ?? '');
$category = $b['category'] ?? null;
$total    = isset($b['total_purchased']) ? (int)$b['total_purchased'] : 0;

if (!$date || !$price || !$seller || !$category || !$total) {
    ob_end_clean();
    jsonError('All required fields must be filled.');
}

$animalIds = json_encode(array_map('intval', $b['animal_ids'] ?? []));

ob_end_clean();

$id = DB::insert(
    'INSERT INTO purchases (date_purchased, price_zar, seller, category, total_purchased, animal_ids, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?)',
    [$date, $price, $seller, $category, $total, $animalIds, $user['id']]
);

logActivity('purchase', $id, 'create', "Purchase: $total x $category from $seller");
jsonSuccess(['id' => $id], 'Purchase saved', 201);
