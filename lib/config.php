<?php
/**
 * BoviLogic – Application Configuration
 * Edit DB_* constants to match your cPanel MySQL credentials.
 */

// ─── Local overrides first (production credentials — never committed to git) ──
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require $localConfig;
}

// ─── Environment ─────────────────────────────────────────────────────────────
if (!defined('BL_ENV'))   define('BL_ENV',   getenv('BL_ENV') ?: 'development');
if (!defined('BL_DEBUG')) define('BL_DEBUG', BL_ENV === 'development');

// ─── Database ─────────────────────────────────────────────────────────────────
if (!defined('DB_HOST'))    define('DB_HOST',    'localhost');
if (!defined('DB_NAME'))    define('DB_NAME',    'bovilogic');
if (!defined('DB_USER'))    define('DB_USER',    'root');
if (!defined('DB_PASS'))    define('DB_PASS',    '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ─── Application ─────────────────────────────────────────────────────────────
if (!defined('APP_NAME'))    define('APP_NAME',    'BoviLogic');
if (!defined('APP_URL'))     define('APP_URL',     getenv('APP_URL') ?: 'http://bovilogic.test');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.4.6');
if (!defined('APP_ROOT'))    define('APP_ROOT',    dirname(__DIR__));

// ─── Session ─────────────────────────────────────────────────────────────────
if (!defined('SESSION_NAME'))     define('SESSION_NAME',     'bl_session_v2');  // bump to invalidate all old sessions
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 0);  // expires when browser/app is closed

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
