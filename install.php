<?php
/**
 * BoviLogic – Database Installer
 * Run once at: http://localhost/bovilogic/install.php
 * DELETE or rename this file after setup is complete.
 */

// Only allow from localhost
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    die('Access denied.');
}

require_once __DIR__ . '/lib/config.php';

$step    = $_POST['step'] ?? 'check';
$errors  = [];
$success = [];

// ─── Test DB connection ───────────────────────────────────────────────────────
function testConnection(string $host, string $user, string $pass): ?PDO {
    try {
        return new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (PDOException $e) {
        return null;
    }
}

$dbOk = false;
if ($step === 'install') {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $name = trim($_POST['db_name'] ?? 'bovilogic');
    $user = trim($_POST['db_user'] ?? 'root');
    $pass = $_POST['db_pass'] ?? '';
    $admin_email = trim($_POST['admin_email'] ?? 'admin@bovilogic.co.za');
    $admin_pass  = $_POST['admin_pass'] ?? 'Admin@1234';
    $admin_name  = trim($_POST['admin_name'] ?? 'Super Admin');

    $pdo = testConnection($host, $user, $pass);
    if (!$pdo) {
        $errors[] = "Cannot connect to MySQL with provided credentials.";
    } else {
        try {
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$name`");

            // Run schema
            $schema = file_get_contents(__DIR__ . '/db/schema.sql');
            // Strip -- comment lines and split on semicolons
            $lines = explode("\n", $schema);
            $cleaned = [];
            foreach ($lines as $line) {
                $trimmed = ltrim($line);
                if (substr($trimmed, 0, 2) === '--') continue;
                $cleaned[] = $line;
            }
            $schema = implode("\n", $cleaned);
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            $schemaOk = true;
            foreach ($statements as $sql) {
                if (!$sql) continue;
                try {
                    $pdo->exec($sql);
                } catch (PDOException $e) {
                    $code = $e->getCode();
                    // Suppress expected errors: duplicate key, FK already exists
                    if (!in_array($code, ['42S21', '23000', 'HY000'])) {
                        $errors[] = 'Schema error: ' . $e->getMessage();
                        $schemaOk = false;
                    }
                }
            }
            if (!$schemaOk) throw new Exception('Schema failed — see errors above.');
            $success[] = "Database tables created.";

            // Create admin user
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
            $hash = password_hash($admin_pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (uuid, name, email, password, role) VALUES (?,?,?,?,'super_admin')");
            $stmt->execute([$uuid, $admin_name, strtolower($admin_email), $hash]);
            $success[] = "Admin user created: $admin_email";

            // Default settings
            $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_val) VALUES
                ('app_name','BoviLogic'),('default_language','en'),
                ('weight_unit','kg'),('date_format','Y-m-d'),
                ('timezone','Africa/Johannesburg')");
            $success[] = "Default settings inserted.";

            // Update config.php
            $configPath = __DIR__ . '/lib/config.php';
            $config = file_get_contents($configPath);
            $config = preg_replace("/define\('DB_HOST',.*?\);/", "define('DB_HOST',    '$host');", $config);
            $config = preg_replace("/define\('DB_NAME',.*?\);/", "define('DB_NAME',    '$name');", $config);
            $config = preg_replace("/define\('DB_USER',.*?\);/", "define('DB_USER',    '$user');", $config);
            $config = preg_replace("/define\('DB_PASS',.*?\);/", "define('DB_PASS',    '$pass');", $config);
            file_put_contents($configPath, $config);
            $success[] = "lib/config.php updated.";

            $dbOk = true;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BoviLogic – Install</title>
  <link rel="stylesheet" href="/bovilogic/assets/css/app.css">
  <style>
    body { background: linear-gradient(160deg, #1B5E20, #388E3C); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; padding-bottom:0; }
    .install-card { background:#fff; border-radius:20px; padding:32px 28px; width:100%; max-width:480px; box-shadow:0 8px 40px rgba(0,0,0,0.2); }
    h1 { color:#1B5E20; font-size:1.75rem; margin-bottom:4px; }
    .step { color:#6B7280; font-size:0.875rem; margin-bottom:24px; }
    .success-list li { color:#2E7D32; font-size:0.875rem; padding:4px 0; }
    .error-list li   { color:#C62828; font-size:0.875rem; padding:4px 0; }
  </style>
</head>
<body>
<div class="install-card">
  <h1>BoviLogic</h1>
  <p class="step">Database Setup</p>

  <?php if ($errors): ?>
  <ul class="error-list" style="margin-bottom:16px;padding-left:20px">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if ($success): ?>
  <ul class="success-list" style="margin-bottom:16px;padding-left:20px">
    <?php foreach ($success as $s): ?><li>✓ <?= htmlspecialchars($s) ?></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if ($dbOk): ?>
    <div class="alert-bar success mb-16">Installation complete!</div>
    <p class="text-muted text-sm mb-16">
      <strong>Important:</strong> Delete or rename <code>install.php</code> before deploying to production.
    </p>
    <a href="/bovilogic/" class="btn btn-primary btn-full btn-lg">Open BoviLogic →</a>
  <?php else: ?>
  <form method="POST">
    <input type="hidden" name="step" value="install">

    <div class="form-group">
      <label class="form-label">MySQL Host</label>
      <input type="text" name="db_host" class="form-control" value="localhost">
    </div>
    <div class="form-group">
      <label class="form-label">Database Name</label>
      <input type="text" name="db_name" class="form-control" value="bovilogic">
    </div>
    <div class="form-group">
      <label class="form-label">MySQL Username</label>
      <input type="text" name="db_user" class="form-control" value="root">
    </div>
    <div class="form-group">
      <label class="form-label">MySQL Password <span class="text-muted">(blank for Laragon)</span></label>
      <input type="password" name="db_pass" class="form-control" placeholder="Leave blank for Laragon default">
    </div>

    <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0">
    <p class="text-muted text-sm mb-12">Admin account to create:</p>

    <div class="form-group">
      <label class="form-label">Admin Name</label>
      <input type="text" name="admin_name" class="form-control" value="Super Admin">
    </div>
    <div class="form-group">
      <label class="form-label">Admin Email</label>
      <input type="email" name="admin_email" class="form-control" value="admin@bovilogic.co.za">
    </div>
    <div class="form-group">
      <label class="form-label">Admin Password</label>
      <input type="password" name="admin_pass" class="form-control" value="Admin@1234">
    </div>

    <button type="submit" class="btn btn-primary btn-full btn-lg">Install Database</button>
  </form>
  <?php endif; ?>
</div>
</body>
</html>
