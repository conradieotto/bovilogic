<?php
/**
 * BoviLogic – Application Configuration
 * Edit DB_* constants to match your cPanel MySQL credentials.
 */

// ─── Environment ─────────────────────────────────────────────────────────────
define('BL_ENV',      getenv('BL_ENV') ?: 'production');  // 'development' | 'production'
define('BL_DEBUG',    BL_ENV === 'development');

// ─── Database ─────────────────────────────────────────────────────────────────
define('DB_HOST',     getenv('DB_HOST') ?: 'localhost');
define('DB_NAME',     getenv('DB_NAME') ?: 'your_db_name');   // <-- set in cPanel
define('DB_USER',     getenv('DB_USER') ?: 'your_db_user');   // <-- set in cPanel
define('DB_PASS',     getenv('DB_PASS') ?: 'your_db_pass');   // <-- set in cPanel
define('DB_CHARSET',  'utf8mb4');

// ─── Application ─────────────────────────────────────────────────────────────
define('APP_NAME',    'BoviLogic');
define('APP_URL',     getenv('APP_URL') ?: 'https://bovilogic.co.za');
define('APP_VERSION', '1.0.0');
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
