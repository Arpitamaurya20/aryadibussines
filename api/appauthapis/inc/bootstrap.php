<?php
/**
 * Shared bootstrap for appauthapis (does not load legacy auth controllers).
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../common_api_header.php';
require_once __DIR__ . '/../../../admin/controllers/common_controllers.php';
require_once __DIR__ . '/../../../admin/includes/autoloader.inc.php';
require_once __DIR__ . '/../../../admin/vendor/autoload.php';

$app_auth_config = require __DIR__ . '/config.php';
require_once __DIR__ . '/AppAuthService.php';

setTimeZone();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
$app_auth_conn = _connectodb();

if (!$app_auth_conn || $app_auth_conn->connect_error) {
    http_response_code(503);
    echo json_encode(['error' => true, 'message' => 'Database connection failed.']);
    exit;
}

$app_auth_service = new AppAuthService($app_auth_conn, $app_auth_config);

function app_auth_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function app_auth_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        app_auth_json_response(['error' => true, 'message' => 'Method Not Allowed'], 405);
    }
}

function app_auth_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function app_auth_normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) > 10) {
        $digits = substr($digits, -10);
    }
    return $digits;
}

function app_auth_client_ip(): string
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
}
