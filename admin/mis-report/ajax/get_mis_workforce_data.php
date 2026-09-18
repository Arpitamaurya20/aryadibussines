<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/mis_report_bootstrap.php';
require_once dirname(__DIR__) . '/inc/mis_report_cache.php';

$session = mis_report_ajax_bootstrap();

$stateName = trim((string)($_GET['state'] ?? ''));
$conn = mis_report_db_connect();
if (!$conn) {
    mis_report_ajax_exit(['error' => true, 'message' => 'Database unavailable'], 503);
}

try {
    [$filters, $stateName, $employeeId] = mis_report_prepare_filters($conn, $session, $stateName);
    $forceRefresh = isset($_GET['refresh']) && (string)$_GET['refresh'] === '1';
    $data = mis_report_cached_workforce($conn, $employeeId, $filters, $stateName, $forceRefresh);
    $conn->close();

    mis_report_ajax_exit([
        'error' => false,
        'section' => 'workforce',
        'cached' => !$forceRefresh,
        'data' => $data,
    ]);
} catch (InvalidArgumentException $e) {
    mis_report_ajax_exit(['error' => true, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    mis_report_ajax_fail($e, 'workforce data');
}
