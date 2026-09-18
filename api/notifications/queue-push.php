<?php

require_once __DIR__ . '/../common_api_header.php';
require_once __DIR__ . '/../../admin/controllers/common_controllers.php';
require_once __DIR__ . '/../../admin/controllers/common_notification_service.php';
require_once __DIR__ . '/inc/notification_config.php';

header('Content-Type: application/json; charset=utf-8');

$data_raw = file_get_contents('php://input');
$data = json_decode($data_raw, true);
if (!is_array($data)) {
    $data = array();
}

$response = array('error' => true, 'message' => 'Invalid request');

$title = isset($data['Title']) ? trim((string) $data['Title']) : '';
$body = isset($data['Body']) ? trim((string) $data['Body']) : '';
if ($title === '' || $body === '') {
    $response['message'] = 'Title and Body are required';
    echo json_encode($response);
    exit;
}

$target = isset($data['Target']) ? strtolower(trim((string) $data['Target'])) : 'user';
if (!in_array($target, array('user', 'employee', 'all'), true)) {
    $response['message'] = 'Target must be user, employee, or all';
    echo json_encode($response);
    exit;
}

$userId = isset($data['UserID']) ? (int) $data['UserID'] : 0;
$employeeId = isset($data['EmployeeID']) ? (int) $data['EmployeeID'] : 0;
$payload = isset($data['payload']) && is_array($data['payload']) ? $data['payload'] : array();
$dispatchWorker = !isset($data['DispatchWorker']) || $data['DispatchWorker'] !== false;
$savePortal = !isset($data['SavePortal']) || $data['SavePortal'] !== false;

if ($target === 'user' && $userId <= 0) {
    $response['message'] = 'UserID is required when Target is user';
    echo json_encode($response);
    exit;
}
if ($target === 'employee' && $employeeId <= 0 && $userId <= 0) {
    $response['message'] = 'EmployeeID or UserID is required when Target is employee';
    echo json_encode($response);
    exit;
}

$conn = _connectodb();
setTimeZone();

$portalResult = null;
if ($savePortal) {
    require_once __DIR__ . '/../../admin/controllers/portal_notification_controller.php';
    $portalResult = pnc_publishNotification($conn, array(
        'target' => $target,
        'title' => $title,
        'body' => $body,
        'user_id' => $userId,
        'employee_id' => $employeeId,
        'payload' => $payload,
        'send_push' => false,
        'save_portal' => true,
        'created_by' => 'api:queue-push',
    ));
    if (!empty($portalResult['error'])) {
        echo json_encode($portalResult);
        exit;
    }
}

$queue = cns_queueNotification($conn, array(
    'target' => $target,
    'title' => $title,
    'body' => $body,
    'user_id' => $userId,
    'employee_id' => $employeeId,
    'payload' => $payload,
));

if (!empty($queue['error'])) {
    echo json_encode($queue);
    exit;
}

$workerDispatched = false;
if ($dispatchWorker) {
    $workerDispatched = cns_dispatchBackgroundWorker(cns_getDefaultProcessLimit());
}

$response = array_merge($queue, array(
    'worker_dispatched' => $workerDispatched,
    'portal_notification_id' => $portalResult['portal_notification_id'] ?? null,
    'process_url' => cns_getSiteBaseUrl() . '/api/notifications/process-queue.php',
    'status_url' => cns_getSiteBaseUrl() . '/api/notifications/batch-status.php?batch_id=' . urlencode($queue['batch_id']),
));

echo json_encode($response);
exit;
