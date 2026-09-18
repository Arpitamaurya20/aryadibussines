<?php
declare(strict_types=1);

@ini_set('display_errors', '0');
@error_reporting(0);
@set_time_limit(90);

while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

require_once dirname(__DIR__) . '/inc/jll_config.php';
require_once dirname(__DIR__) . '/inc/jll_db.php';
require_once dirname(__DIR__) . '/inc/jll_cache.php';
require_once dirname(__DIR__) . '/inc/jll_queries.php';

date_default_timezone_set(JLL_TIMEZONE);

if (!jll_check_access()) {
    jll_json_exit(['error' => true, 'message' => 'Invalid or missing wallboard key'], 403);
}

$cached = jll_cache_read('live_snapshot', JLL_RESPONSE_CACHE_SECONDS);
if (is_array($cached)) {
    jll_json_exit([
        'error' => false,
        'cached' => true,
        'refresh_seconds' => JLL_REFRESH_SECONDS,
        'brand' => JLL_BRAND_NAME,
        'data' => $cached,
    ]);
}

try {
    $conn = jll_connect();
    if (!$conn) {
        jll_json_exit(['error' => true, 'message' => 'Database unavailable'], 503);
    }
    $data = jll_fetch_live_snapshot($conn);
    $conn->close();

    jll_cache_write('live_snapshot', $data, JLL_RESPONSE_CACHE_SECONDS);

    jll_json_exit([
        'error' => false,
        'cached' => false,
        'refresh_seconds' => JLL_REFRESH_SECONDS,
        'brand' => JLL_BRAND_NAME,
        'data' => $data,
    ]);
} catch (Throwable $e) {
    jll_json_exit(['error' => true, 'message' => 'Failed to load JLL live data'], 500);
}
