<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../controllers/common_controllers.php';
require_once __DIR__ . '/../../includes/autoloader.inc.php';

$ta_user_type = SessionCheck();
if (!$ta_user_type || $ta_user_type === 'Error') {
    header('Location: ../authentication/login.php');
    exit;
}

setTimeZone();
$ta_conn = _connectodb();

if (!$ta_conn || $ta_conn->connect_error) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => true, 'message' => 'Database connection failed.']);
    exit;
}

function ta_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function ta_require_ajax(): void
{
    $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    if (!$isAjax && php_sapi_name() !== 'cli') {
        ta_json_response(['error' => true, 'message' => 'Invalid request'], 400);
    }
}
?>
