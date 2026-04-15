<?php
require_once __DIR__ . '/lib/config.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/language.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/totp.php';

// Allow access for:
// (a) 2FA-pending users who haven't set up yet
// (b) Already logged-in users who want to reset/re-setup 2FA
$pendingId = $_SESSION['2fa_pending_id'] ?? null;
$loggedIn  = isLoggedIn();

if (!$pendingId && !$loggedIn) {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

$userId    = $loggedIn ? ($_SESSION['user_id']) : $pendingId;
$userEmail = $loggedIn ? ($_SESSION['user_email']) : ($_SESSION['2fa_pending_email'] ?? '');
$lang      = $_COOKIE['bl_lang'] ?? ($loggedIn ? ($_SESSION['user_language'] ?? 'en') : ($_SESSION['2fa_pending_lang'] ?? 'en'));
loadLanguage($lang);

$error   = '';
$success = '';

// Generate a new temp secret (stored in session until confirmed)
if (empty($_SESSION['2fa_setup_secret'])) {
    $_SESSION['2fa_setup_secret'] = TOTP::generateSecret();
}
$secret = $_SESSION['2fa_setup_secret'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
    if (TOTP::verify($secret, $code)) {
        // Save secret and enable 2FA
        DB::exec(
            'UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?',
            [$secret, $userId]
        );
        unset($_SESSION['2fa_setup_secret']);

        if ($pendingId && !$loggedIn) {
            // Complete login for new 2FA setup flow
            // Refresh pending perms from DB
            $freshUser = DB::row('SELECT * FROM users WHERE id = ?', [$userId]);
            if ($freshUser) {
                $_SESSION['2fa_pending_perms'] = $freshUser['permissions'] ?? null;
            }
            $_SESSION['2fa_needs_setup'] = false;
            completeLogin();
        }
        header('Location: ' . APP_URL . '/index.php?2fa=setup_ok');
        exit;
    } else {
        $error = 'Incorrect code. Try again.';
    }
}

$otpauthUri = TOTP::getUri($secret, $userEmail);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#1e2130">
  <title>BoviLogic – Setup 2FA</title>
  <link rel="icon" href="/assets/icons/favicon.ico">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= APP_VERSION ?>">
</head>
<body class="login-page">

<div class="login-card" style="max-width:420px">
  <div class="login-logo">
    <div style="font-size:2.5rem;color:var(--blue);margin-bottom:8px"><i class="fa-solid fa-shield-halved"></i></div>
    <h2 style="margin-bottom:4px">Set Up Two-Factor Auth</h2>
    <p style="color:var(--text-muted);font-size:0.875rem">Scan the QR code with your authenticator app</p>
  </div>

  <?php if ($error): ?>
  <div class="alert-bar error" style="margin:0 0 16px">
    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <!-- Step 1: QR Code -->
  <div style="background:#f8fafc;border:1px solid var(--border);border-radius:var(--radius);padding:20px;margin-bottom:20px;text-align:center">
    <p class="text-sm text-muted" style="margin-bottom:12px">
      <strong>Step 1:</strong> Open Google Authenticator, Authy, or Microsoft Authenticator and scan this code:
    </p>
    <div id="qr-code" style="display:inline-block;padding:8px;background:#fff;border-radius:8px;margin-bottom:12px"></div>
    <p class="text-xs text-muted" style="margin-bottom:6px">Can't scan? Enter this key manually:</p>
    <code style="display:block;word-break:break-all;font-size:0.85rem;background:#e2e8f0;padding:8px 12px;border-radius:6px;letter-spacing:0.1em">
      <?= chunk_split($secret, 4, ' ') ?>
    </code>
  </div>

  <!-- Step 2: Verify -->
  <p class="text-sm" style="margin-bottom:12px"><strong>Step 2:</strong> Enter the 6-digit code from your app:</p>
  <form method="POST" action="/setup-2fa.php">
    <div class="form-group">
      <input type="text" name="code" class="form-control" inputmode="numeric" pattern="\d{6}"
             maxlength="6" placeholder="000000" autocomplete="one-time-code"
             style="font-size:1.5rem;letter-spacing:0.3em;text-align:center" autofocus required>
    </div>
    <button type="submit" class="btn btn-primary btn-full btn-lg">
      <i class="fa-solid fa-check"></i> Verify &amp; Activate
    </button>
  </form>

  <?php if ($loggedIn): ?>
  <a href="/settings.php" class="btn btn-secondary btn-full mt-12">Cancel</a>
  <?php endif; ?>
</div>

<!-- QR Code generator (client-side, no external data sent) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" crossorigin="anonymous"></script>
<script>
new QRCode(document.getElementById('qr-code'), {
  text: <?= json_encode($otpauthUri) ?>,
  width: 200, height: 200,
  colorDark: '#1e2130', colorLight: '#ffffff',
  correctLevel: QRCode.CorrectLevel.M
});
</script>
<script src="/assets/js/app.js?v=<?= APP_VERSION ?>"></script>
</body>
</html>
