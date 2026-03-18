<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['user_language'] ?? 'en') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#1B5E20">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="BoviLogic">
  <title><?= t($pageTitle ?? 'app_name') ?> – BoviLogic</title>
  <link rel="manifest" href="/manifest.json">
  <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
  <link rel="icon" href="/assets/icons/favicon.ico">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= APP_VERSION ?>">
</head>
<body class="<?= $bodyClass ?? '' ?>">

<div id="offline-banner" class="offline-banner" style="display:none;">
  <span>&#9679; Offline – changes will sync when connected</span>
</div>
