<?php
declare(strict_types=1);

/**
 * Shared AJAX bootstrap: authenticate, then release session lock immediately
 * so long MIS report generation does not block other portal tabs/requests.
 */
function mis_report_ajax_bootstrap(): array
{
    @ini_set('display_errors', '0');
    @error_reporting(0);
    @set_time_limit(180);
    @ini_set('memory_limit', '256M');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    ob_start();

    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    // common_controllers sets $servername etc.; when required inside a function those
    // assignments are local unless we import the globals first (_connectodb reads globals).
    global $servername, $dbusername, $password, $dbname, $_URL;

    require_once dirname(__DIR__, 2) . '/includes/autoloader.inc.php';
    if (!function_exists('_connectodb')) {
        require_once dirname(__DIR__, 2) . '/controllers/common_controllers.php';
    }
    require_once __DIR__ . '/mis_report_scope.php';

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Connection: close');

    if (!isset($_SESSION['pb_username'])) {
        mis_report_ajax_exit(['error' => true, 'message' => 'Session expired. Please log in again.'], 401);
    }

    if (!mis_report_user_has_access($_SESSION)) {
        mis_report_ajax_exit(['error' => true, 'message' => 'Access denied. MIS Report is for admin users only.'], 403);
    }

    $sessionSnapshot = $_SESSION;
    session_write_close();

    return $sessionSnapshot;
}

function mis_report_ajax_exit(array $payload, int $code = 200): void
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        $json = '{"error":true,"message":"JSON encode failed"}';
    }
    echo $json;
    exit;
}

function mis_report_prepare_filters(mysqli $conn, array $session, string $stateName): array
{
    $stateName = trim($stateName);
    if ($stateName === '' || $stateName === 'all') {
        throw new InvalidArgumentException('Please select a state before loading the report.');
    }
    if (!mis_report_validate_state_name($conn, $stateName)) {
        throw new InvalidArgumentException('Invalid state selected.');
    }

    $scope = mis_report_resolve_scope($conn, $session);
    $filters = mis_report_normalize_filters(array_merge($_GET, ['state' => $stateName]), $scope, $conn);

    return [$filters, $stateName, (int)($scope['employee_id'] ?? 0)];
}

function mis_report_db_connect(): ?mysqli
{
    if (!function_exists('_connectodb')) {
        global $servername, $dbusername, $password, $dbname, $_URL;
        require_once dirname(__DIR__, 2) . '/controllers/common_controllers.php';
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = _connectodb();
    return $conn instanceof mysqli ? $conn : null;
}

function mis_report_ajax_fail(Throwable $e, string $context): void
{
    error_log('MIS report ' . $context . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    $message = 'Failed to load ' . $context;
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $message .= ' — ' . $e->getMessage();
    }
    mis_report_ajax_exit(['error' => true, 'message' => $message], 500);
}
