<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

// Already fully logged in → go home
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/index.php');
    exit;
}

$lang = $_COOKIE['bl_lang'] ?? 'en';
loadLanguage($lang);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = t('required_field');
    } else {
        $user = attemptLogin($email, $password);
        if ($user) {
            setcookie('bl_lang', $user['language'], time() + 60*60*24*365, '/');
            // Check if 2FA columns exist before entering 2FA flow
            $has2faCol = false;
            try {
                $cols = \DB::rows("SHOW COLUMNS FROM users LIKE 'totp_enabled'");
                $has2faCol = !empty($cols);
            } catch (Throwable $e) {}

            if ($has2faCol) {
                setPendingUser($user);
                $dest = !empty($user['totp_enabled']) ? '/verify-2fa.php' : '/setup-2fa.php';
                header('Location: ' . APP_URL . $dest);
            } else {
                // Migration not yet run — log in directly without 2FA
                session_regenerate_id(true);
                $_SESSION['user_id']          = $user['id'];
                $_SESSION['user_name']        = $user['name'];
                $_SESSION['user_email']       = $user['email'];
                $_SESSION['user_role']        = $user['role'];
                $_SESSION['user_language']    = $user['language'];
                $_SESSION['user_permissions'] = null;
                \DB::exec('UPDATE users SET last_login = NOW() WHERE id = ?', [$user['id']]);
                header('Location: ' . APP_URL . '/index.php');
            }
            exit;
        } else {
            $error = t('invalid_login');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#1e2130">
  <title>BoviLogic – Login</title>
  <link rel="manifest" href="/manifest.json">
  <link rel="icon" href="/assets/icons/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= APP_VERSION ?>">
</head>
<body class="login-page">

<div class="login-card">
  <div class="login-logo">
    <h1>Bovi<span style="color:var(--blue)">Logic</span></h1>
    <p>Cattle Management</p>
  </div>

  <?php if ($error): ?>
  <div class="alert-bar error" style="margin:0 0 16px">
    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <?php if (isset($_GET['logout'])): ?>
  <div class="alert-bar success" style="margin:0 0 16px">
    <i class="fa-solid fa-check-circle"></i> <?= t('logged_out') ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="/login.php" novalidate>
    <div class="form-group">
      <label class="form-label" for="email"><?= t('email') ?></label>
      <input type="email" id="email" name="email" class="form-control"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             autocomplete="email" required autofocus placeholder="you@example.com">
    </div>
    <div class="form-group">
      <label class="form-label" for="password"><?= t('password') ?></label>
      <input type="password" id="password" name="password" class="form-control"
             autocomplete="current-password" required placeholder="••••••••">
    </div>
    <button type="submit" class="btn btn-primary btn-full btn-lg"><?= t('login') ?></button>
  </form>
</div>

<script src="/assets/js/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
