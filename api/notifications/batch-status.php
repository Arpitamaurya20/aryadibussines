<?php

require_once __DIR__ . '/../common_api_header.php';
require_once __DIR__ . '/../../admin/controllers/common_controllers.php';
require_once __DIR__ . '/../../admin/controllers/common_notification_service.php';
require_once __DIR__ . '/inc/notification_config.php';

header('Content-Type: application/json; charset=utf-8');

$secret = isset($_GET['secret']) ? (string) $_GET['secret'] : '';
if (!cns_isValidWorkerSecret($secret)) {
    http_response_code(403);
    echo json_encode(array('error' => true, 'message' => 'Invalid worker secret'));
    exit;
}

$batchId = isset($_GET['batch_id']) ? trim((string) $_GET['batch_id']) : '';
if ($batchId === '') {
    echo json_encode(array('error' => true, 'message' => 'batch_id is required'));
    exit;
}

$conn = _connectodb();
setTimeZone();

$status = cns_getBatchStatus($conn, $batchId);
if ($status === null) {
    echo json_encode(array('error' => true, 'message' => 'Batch not found'));
    exit;
}

echo json_encode(array_merge(array('error' => false), $status));
exit;
