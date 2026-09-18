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

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : cns_getDefaultProcessLimit();

$conn = _connectodb();
setTimeZone();

$result = cns_processQueue($conn, $limit);
echo json_encode($result);
exit;
