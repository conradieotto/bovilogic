<?php
/**
 * BoviLogic – Application Configuration
 * Edit DB_* constants to match your cPanel MySQL credentials.
 */

// ─── Environment ─────────────────────────────────────────────────────────────
// Change to 'production' before deploying to cPanel
define('BL_ENV',   getenv('BL_ENV') ?: 'development');
define('BL_DEBUG', BL_ENV === 'development');

// ─── Database ─────────────────────────────────────────────────────────────────
// LOCAL (Laragon defaults) — change these for cPanel
define('DB_HOST',    'localhost');
define('DB_NAME',    'bovilogic');
define('DB_USER',    'root');
define('DB_PASS',    '');           // Laragon MySQL has no password by default
define('DB_CHARSET', 'utf8mb4');

// ─── Application ─────────────────────────────────────────────────────────────
define('APP_NAME',    'BoviLogic');
define('APP_URL',     getenv('APP_URL') ?: 'http://bovilogic.test');
define('APP_VERSION', '1.2.1');
define('APP_ROOT',    dirname(__DIR__));

// ─── Session ─────────────────────────────────────────────────────────────────
define('SESSION_NAME',     'bl_session');
define('SESSION_LIFETIME', 60 * 60 * 8);  // 8 hours

// ─── Timezone ────────────────────────────────────────────────────────────────
date_default_timezone_set('Africa/Johannesburg');

// ─── Error display ───────────────────────────────────────────────────────────
if (BL_DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ─── Local overrides (production credentials — never committed to git) ────────
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require $localConfig;
}
