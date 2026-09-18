<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/mis_report_bootstrap.php';

try {
    $session = mis_report_ajax_bootstrap();

    $stateName = trim((string)($_GET['state'] ?? ''));
    $conn = mis_report_db_connect();
    if (!$conn) {
        mis_report_ajax_exit(['error' => true, 'message' => 'Database unavailable'], 503);
    }

    if (!mis_report_validate_state_name($conn, $stateName)) {
        mis_report_ajax_exit(['error' => true, 'message' => 'Select a valid state first.'], 400);
    }

    $options = mis_report_load_filter_options($conn, $stateName);
    $conn->close();

    mis_report_ajax_exit([
        'error' => false,
        'state' => $stateName,
        'companies' => $options['companies'] ?? [],
        'branches' => $options['branches'] ?? [],
    ]);
} catch (Throwable $e) {
    mis_report_ajax_fail($e, 'filter options');
}
