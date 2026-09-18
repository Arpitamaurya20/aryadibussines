<?php
declare(strict_types=1);

/**
 * Live wallboard — no login (office TV / live screen).
 * Set WALLBOARD_SECRET to a long random string and open:
 *   live-screen.php?key=YOUR_SECRET
 * Leave empty to allow access without key (internal network only).
 */
const WALLBOARD_SECRET = '';
const WALLBOARD_REFRESH_SECONDS = 300;
const WALLBOARD_COMPANY_NAME = 'Aryadibussiness';
const WALLBOARD_TIMEZONE = 'Asia/Kolkata';

function wallboard_check_access(): bool
{
    if (WALLBOARD_SECRET === '') {
        return true;
    }
    $key = isset($_GET['key']) ? (string)$_GET['key'] : '';
  if ($key === '' && isset($_POST['key'])) {
        $key = (string)$_POST['key'];
    }
    return hash_equals(WALLBOARD_SECRET, $key);
}

function wallboard_json_exit(array $payload, int $code = 200): void
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
