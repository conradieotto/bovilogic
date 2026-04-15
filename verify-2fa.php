<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/totp.php';

// Must be in 2FA-pending state with setup already done
if (empty($_SESSION['2fa_pending_id']) || isLoggedIn()) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// If they haven't set up 2FA yet, send to setup
if (!empty($_SESSION['2fa_needs_setup'])) {
    header('Location: ' . APP_URL . '/setup-2fa.php');
    exit;
}

$pendingId = $_SESSION['2fa_pending_id'];
$lang      = $_COOKIE['bl_lang'] ?? ($_SESSION['2fa_pending_lang'] ?? 'en');
loadLanguage($lang);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
    $user = DB::row('SELECT totp_secret FROM users WHERE id = ? AND is_active = 1', [$pendingId]);

    if ($user && TOTP::verify($user['totp_secret'], $code)) {
        completeLogin();
        header('Location: ' . APP_URL . '/index.php');
        exit;
    } else {
        $error = 'Incorrect code. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#1e2130">
  <title>BoviLogic – Two-Factor Verification</title>
  <link rel="icon" href="/assets/icons/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= APP_VERSION ?>">
</head>
<body class="login-page">

<div class="login-card">
  <div class="login-logo">
    <div style="font-size:2.5rem;color:var(--blue);margin-bottom:8px"><i class="fa-solid fa-lock"></i></div>
    <h2 style="margin-bottom:4px">Two-Factor Verification</h2>
    <p style="color:var(--text-muted);font-size:0.875rem">
      Enter the 6-digit code from your authenticator app
    </p>
  </div>

  <?php if ($error): ?>
  <div class="alert-bar error" style="margin:0 0 16px">
    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="/verify-2fa.php">
    <div class="form-group">
      <label class="form-label">Authenticator Code</label>
      <input type="text" name="code" class="form-control"
             inputmode="numeric" pattern="\d{6}" maxlength="6"
             placeholder="000000" autocomplete="one-time-code"
             style="font-size:1.75rem;letter-spacing:0.4em;text-align:center"
             autofocus required>
    </div>
    <button type="submit" class="btn btn-primary btn-full btn-lg">
      <i class="fa-solid fa-check"></i> Verify
    </button>
  </form>

  <p class="text-xs text-muted" style="margin-top:16px;text-align:center">
    The code changes every 30 seconds.<br>
    Having trouble? Contact your administrator.
  </p>

  <a href="/login.php" class="btn btn-secondary btn-full mt-12">
    <i class="fa-solid fa-arrow-left"></i> Back to Login
  </a>
</div>

<script src="/assets/js/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
