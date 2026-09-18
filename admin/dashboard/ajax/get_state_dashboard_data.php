<?php
declare(strict_types=1);

@ini_set('display_errors', '0');
@error_reporting(0);
@set_time_limit(120);

while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

session_start();

require_once dirname(__DIR__, 2) . '/includes/autoloader.inc.php';
require_once dirname(__DIR__, 2) . '/controllers/common_controllers.php';
require_once dirname(__DIR__) . '/inc/state_dashboard_cache.php';
require_once dirname(__DIR__) . '/inc/state_dashboard_queries.php';
require_once dirname(__DIR__) . '/inc/state_dashboard_scope.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function state_dashboard_json_exit(array $payload, int $code = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!isset($_SESSION['pb_username'])) {
    state_dashboard_json_exit(['error' => true, 'message' => 'Session expired. Please log in again.'], 401);
}

$scope = null;
$conn = _connectodb();
if ($conn) {
    $scope = manager_dashboard_resolve_scope($conn, $_SESSION);
}

if (!$scope || ($scope['mode'] ?? '') === '') {
    state_dashboard_json_exit(['error' => true, 'message' => 'Access denied. Manager dashboard role required.'], 403);
}

$employeeId = (int)($scope['employee_id'] ?? 0);
if ($employeeId <= 0) {
    state_dashboard_json_exit(['error' => true, 'message' => 'Employee profile not linked to this account.'], 403);
}

try {
    if (!$conn) {
        state_dashboard_json_exit(['error' => true, 'message' => 'Database unavailable'], 503);
    }

    if ($scope['mode'] === 'state' && count($scope['state_names'] ?? []) === 0) {
        state_dashboard_json_exit([
            'error' => false,
            'cached' => false,
            'data' => state_dashboard_empty_snapshot(),
            'message' => 'No states assigned to your profile.',
        ]);
    }

    if ($scope['mode'] === 'branch' && count($scope['branch_ids'] ?? []) === 0) {
        state_dashboard_json_exit([
            'error' => false,
            'cached' => false,
            'data' => state_dashboard_empty_snapshot(),
            'message' => 'No branches assigned to your profile.',
        ]);
    }

    $filters = state_dashboard_normalize_filters($_GET, $scope, $conn);
    $forceRefresh = isset($_GET['refresh']) && (string)$_GET['refresh'] === '1';
    $data = state_dashboard_cached_snapshot($conn, $employeeId, $filters, $forceRefresh);
    $conn->close();

    state_dashboard_json_exit([
        'error' => false,
        'cached' => !$forceRefresh,
        'data' => $data,
    ]);
} catch (Throwable $e) {
    state_dashboard_json_exit(['error' => true, 'message' => 'Failed to load dashboard data'], 500);
}
