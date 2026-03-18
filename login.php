<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';

// Already logged in → go home
if (isLoggedIn()) {
    header('Location: /index.php');
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
            header('Location: /index.php');
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
  <meta name="theme-color" content="#1B5E20">
  <title>BoviLogic – Login</title>
  <link rel="manifest" href="/manifest.json">
  <link rel="icon" href="/assets/icons/favicon.ico">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= APP_VERSION ?>">
</head>
<body class="login-page">

<div class="login-card">
  <div class="login-logo">
    <h1>BoviLogic</h1>
    <p>Cattle Management</p>
  </div>

  <?php if ($error): ?>
  <div class="alert-bar error" style="margin: 0 0 16px;">
    <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <?php if (isset($_GET['logout'])): ?>
  <div class="alert-bar success" style="margin: 0 0 16px;">
    <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
    <?= t('logged_out') ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="/login.php" novalidate>
    <div class="form-group">
      <label class="form-label" for="email"><?= t('email') ?></label>
      <input
        type="email" id="email" name="email"
        class="form-control"
        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
        autocomplete="email" required autofocus
        placeholder="you@example.com">
    </div>
    <div class="form-group">
      <label class="form-label" for="password"><?= t('password') ?></label>
      <input
        type="password" id="password" name="password"
        class="form-control"
        autocomplete="current-password" required
        placeholder="••••••••">
    </div>
    <button type="submit" class="btn btn-primary btn-full btn-lg">
      <?= t('login') ?>
    </button>
  </form>
</div>

<script src="/assets/js/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
