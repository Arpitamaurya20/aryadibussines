<?php
declare(strict_types=1);

/**
 * JLL Live Wallboard — isolated module (corporate HQ ID = 13).
 * Open: live-screen.php?key=YOUR_SECRET
 * No admin session required — does not load common_controllers or portal assets.
 */
const JLL_CORPORATE_HQ_ID = 13;
const JLL_WALLBOARD_SECRET = '';
const JLL_REFRESH_SECONDS = 120;
const JLL_RESPONSE_CACHE_SECONDS = 90;
const JLL_TIMEZONE = 'Asia/Kolkata';
const JLL_BRAND_NAME = 'JLL';
const JLL_BRAND_SUBTITLE = 'Operations Command Center';
const JLL_TECHXPERT_LOGO = '../../img/logo-flat.svg';

function jll_check_access(): bool
{
    if (JLL_WALLBOARD_SECRET === '') {
        return true;
    }
    $key = isset($_GET['key']) ? (string)$_GET['key'] : '';
    if ($key === '' && isset($_POST['key'])) {
        $key = (string)$_POST['key'];
    }
    return hash_equals(JLL_WALLBOARD_SECRET, $key);
}

function jll_json_exit(array $payload, int $code = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        $json = '{"error":true,"message":"JSON encode failed"}';
    }
    echo $json;
    exit;
}
