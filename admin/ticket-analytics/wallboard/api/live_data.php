<?php
declare(strict_types=1);

@ini_set('display_errors', '0');
@error_reporting(0);
@set_time_limit(120);

while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

require_once dirname(__DIR__) . '/inc/wallboard_config.php';
require_once dirname(__DIR__) . '/inc/wallboard_db.php';
require_once dirname(__DIR__) . '/inc/wallboard_queries.php';

date_default_timezone_set(WALLBOARD_TIMEZONE);

if (!wallboard_check_access()) {
    wallboard_json_exit(['error' => true, 'message' => 'Invalid or missing wallboard key'], 403);
}

try {
    $conn = wallboard_connect();
    if (!$conn) {
        wallboard_json_exit(['error' => true, 'message' => 'Database unavailable'], 503);
    }
    $data = wallboard_fetch_live_snapshot($conn);
    $conn->close();
    wallboard_json_exit([
        'error' => false,
        'refresh_seconds' => WALLBOARD_REFRESH_SECONDS,
        'company' => WALLBOARD_COMPANY_NAME,
        'data' => $data,
    ]);
} catch (Throwable $e) {
    wallboard_json_exit(['error' => true, 'message' => 'Failed to load live data'], 500);
}
