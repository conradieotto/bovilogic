<?php
/**
 * BoviLogic – Language / Translation Helper
 */

$GLOBALS['_bl_lang'] = [];

function loadLanguage(string $lang = 'en'): void {
    $allowed = ['en', 'af'];
    $lang = in_array($lang, $allowed) ? $lang : 'en';
    $file = __DIR__ . '/../lang/' . $lang . '.php';
    if (file_exists($file)) {
        $strings = require $file;
        $GLOBALS['_bl_lang'] = $strings;
    }
}

/** Translate a key, with optional placeholders */
function t(string $key, array $replace = []): string {
    $str = $GLOBALS['_bl_lang'][$key] ?? $key;
    foreach ($replace as $k => $v) {
        $str = str_replace(':' . $k, (string)$v, $str);
    }
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/** Raw translate (no escaping – use only for trusted content) */
function tr(string $key, array $replace = []): string {
    $str = $GLOBALS['_bl_lang'][$key] ?? $key;
    foreach ($replace as $k => $v) {
        $str = str_replace(':' . $k, (string)$v, $str);
    }
    return $str;
}
