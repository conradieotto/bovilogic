<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/db.php';

$allowedIps = ['127.0.0.1', '::1'];
$validToken = isset($_GET['token']) && $_GET['token'] === 'bl-migrate-2025';
if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIps) && !$validToken) {
    http_response_code(403); die('Forbidden');
}

$migrations = [
    'farms.size_ha'          => "ALTER TABLE farms ADD COLUMN size_ha DECIMAL(10,2) DEFAULT NULL AFTER location",
    'animals.breeding_date'  => "ALTER TABLE animals ADD COLUMN breeding_date DATE DEFAULT NULL AFTER breeding_status",
    'animals.category_type'  => "ALTER TABLE animals MODIFY COLUMN category VARCHAR(50) NOT NULL DEFAULT 'breeding_cow'",
    'herds.bull_ids'            => "ALTER TABLE herds ADD COLUMN bull_ids JSON DEFAULT NULL",
    'herds.pregnancy_rate'      => "ALTER TABLE herds ADD COLUMN pregnancy_rate DECIMAL(5,2) DEFAULT NULL",
    'herds.last_pregnancy_test' => "ALTER TABLE herds ADD COLUMN last_pregnancy_test DATE DEFAULT NULL",
    'create.pregnancy_tests'    => "CREATE TABLE IF NOT EXISTS pregnancy_tests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        herd_id INT NOT NULL,
        test_date DATE NOT NULL,
        total_tested INT NOT NULL,
        total_pregnant INT NOT NULL,
        pregnancy_rate DECIMAL(5,2) NOT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'animals.last_calving_date' => "ALTER TABLE animals ADD COLUMN last_calving_date DATE DEFAULT NULL",
    'animals.avg_calf_interval' => "ALTER TABLE animals ADD COLUMN avg_calf_interval DECIMAL(6,2) DEFAULT NULL",
    'create.herd_movements'  => "CREATE TABLE IF NOT EXISTS herd_movements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        herd_id INT NOT NULL,
        farm_id INT NOT NULL,
        camp_id INT NOT NULL,
        date_in DATE NOT NULL,
        date_out DATE DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'create.sales'           => "CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date_sold DATE NOT NULL,
        price_zar DECIMAL(12,2) NOT NULL,
        buyer VARCHAR(255) NOT NULL,
        category VARCHAR(50) NOT NULL,
        total_sold INT NOT NULL DEFAULT 1,
        animal_ids JSON DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    'calving.calf_tag'       => "ALTER TABLE calving ADD COLUMN calf_tag VARCHAR(50) DEFAULT NULL AFTER calf_id",
    'animals.status_date'    => "ALTER TABLE animals ADD COLUMN status_date DATE DEFAULT NULL AFTER animal_status",
    'animals.status_notes'   => "ALTER TABLE animals ADD COLUMN status_notes TEXT DEFAULT NULL AFTER status_date",
    'create.purchases'       => "CREATE TABLE IF NOT EXISTS purchases (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date_purchased DATE NOT NULL,
        price_zar DECIMAL(12,2) NOT NULL,
        seller VARCHAR(255) NOT NULL,
        category VARCHAR(50) NOT NULL,
        total_purchased INT NOT NULL DEFAULT 1,
        animal_ids JSON DEFAULT NULL,
        created_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
];

$results = [];
foreach ($migrations as $label => $sql) {
    try {
        DB::exec($sql);
        $results[] = ['label' => $label, 'status' => 'ok', 'msg' => 'Applied'];
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        // "Duplicate column" means it already exists — that's fine
        if (str_contains($msg, 'Duplicate column') || str_contains($msg, 'already exists')) {
            $results[] = ['label' => $label, 'status' => 'skip', 'msg' => 'Already exists'];
        } else {
            $results[] = ['label' => $label, 'status' => 'error', 'msg' => $msg];
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>DB Migration</title>
<style>
body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:16px}
table{width:100%;border-collapse:collapse;margin-bottom:24px}
td,th{padding:10px;border:1px solid #ddd;font-size:14px}th{background:#f5f5f5}
.ok{color:green}.skip{color:#888}.error{color:red}
</style>
</head>
<body>
<h2>Database Migration</h2>
<table>
  <tr><th>Column</th><th>Status</th><th>Message</th></tr>
  <?php foreach ($results as $r): ?>
  <tr>
    <td><?= htmlspecialchars($r['label']) ?></td>
    <td class="<?= $r['status'] ?>"><strong><?= strtoupper($r['status']) ?></strong></td>
    <td><?= htmlspecialchars($r['msg']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<p>Done. You can now <a href="/index.php">go back to the app</a>.</p>
</body>
</html>
