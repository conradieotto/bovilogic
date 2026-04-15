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
    $rows = DB::rows('SELECT * FROM sales ORDER BY date_sold DESC');
    jsonSuccess($rows);
}

if ($method !== 'POST') {
    ob_end_clean();
    jsonError('Method not allowed', 405);
}

if ($user['role'] !== 'super_admin') { ob_end_clean(); jsonForbidden(); }

$b        = getJsonBody();
$date     = $b['date_sold'] ?? null;
$price    = isset($b['price_zar']) ? (float)$b['price_zar'] : null;
$buyer    = trim($b['buyer'] ?? '');
$category = $b['category'] ?? null;
$total    = isset($b['total_sold']) ? (int)$b['total_sold'] : 0;

if (!$date || !$price || !$buyer || !$category || !$total) {
    ob_end_clean();
    jsonError('All required fields must be filled.');
}

$animalIds = json_encode(array_map('intval', $b['animal_ids'] ?? []));

ob_end_clean();

$id = DB::insert(
    'INSERT INTO sales (date_sold, price_zar, buyer, category, total_sold, animal_ids, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?)',
    [$date, $price, $buyer, $category, $total, $animalIds, $user['id']]
);

// Mark tagged animals as sold
foreach (array_map('intval', $b['animal_ids'] ?? []) as $aid) {
    DB::exec("UPDATE animals SET animal_status = 'sold' WHERE id = ?", [$aid]);
}

logActivity('sale', $id, 'create', "Sale: $total x $category to $buyer");
jsonSuccess(['id' => $id], 'Sale saved', 201);
