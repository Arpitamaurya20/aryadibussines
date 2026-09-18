<?php

/**
 * Common background push notification queue (does not modify attendance/leave flows).
 */

function cns_loadPushHelpers()
{
    if (!function_exists('pnc_resolveEmployeeId')) {
        require_once __DIR__ . '/push_notification_controller.php';
    }
}

function cns_generateBatchId()
{
    try {
        return 'bn_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
    } catch (Exception $e) {
        return 'bn_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
    }
}

function cns_normalizePayload($payload)
{
    if (!is_array($payload)) {
        $payload = array();
    }

    if (!isset($payload['screen'])) {
        $payload['screen'] = 'dashboard';
    }
    if (!isset($payload['type'])) {
        $payload['type'] = 'GENERAL_NOTIFICATION';
    }

    return $payload;
}

function cns_getLatestActiveDeviceTargets($conn)
{
    cns_loadPushHelpers();

    $targets = array();
    $latestByEmployee = array();
    $sql = "SELECT id, user_id, device_token, last_active
            FROM user_devices
            WHERE IsActive = 1 AND device_token IS NOT NULL AND device_token != ''";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return $targets;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $employeeId = pnc_normalizeStoredDeviceOwnerToEmployeeId($conn, (int) ($row['user_id'] ?? 0));
        $token = trim((string) ($row['device_token'] ?? ''));
        if ($employeeId <= 0 || $token === '') {
            continue;
        }

        $rowId = (int) ($row['id'] ?? 0);
        $lastActive = (string) ($row['last_active'] ?? '');
        if (!isset($latestByEmployee[$employeeId])) {
            $latestByEmployee[$employeeId] = array(
                'employee_id' => $employeeId,
                'device_token' => $token,
                'id' => $rowId,
                'last_active' => $lastActive,
            );
            continue;
        }

        $current = $latestByEmployee[$employeeId];
        if ($lastActive > $current['last_active'] || ($lastActive === $current['last_active'] && $rowId > $current['id'])) {
            $latestByEmployee[$employeeId] = array(
                'employee_id' => $employeeId,
                'device_token' => $token,
                'id' => $rowId,
                'last_active' => $lastActive,
            );
        }
    }

    foreach ($latestByEmployee as $target) {
        $targets[] = array(
            'employee_id' => (int) $target['employee_id'],
            'device_token' => $target['device_token'],
        );
    }

    return $targets;
}

function cns_getUserDeviceTarget($conn, $userId, $employeeId = 0)
{
    cns_loadPushHelpers();

    $employeeId = pnc_resolveEmployeeId($conn, (int) $userId, (int) $employeeId);
    if ($employeeId <= 0) {
        return array();
    }

    $target = pnc_findPushTargetForEmployee($conn, $employeeId);
    if (!$target) {
        return array();
    }

    return array($target);
}

function cns_buildNoTargetsMessage($conn, $targetType, $userId = 0, $employeeId = 0)
{
    cns_loadPushHelpers();

    $targetType = strtolower(trim((string) $targetType));
    $userId = (int) $userId;
    $employeeId = (int) $employeeId;

    if ($targetType === 'all') {
        return 'No active mobile devices found. Users must log in to the mobile app so their device token is saved in user_devices.';
    }

    if ($targetType === 'employee' && $employeeId > 0 && function_exists('pnc_buildMissingTokenMessage')) {
        return pnc_buildMissingTokenMessage($conn, $employeeId);
    }

    if ($targetType === 'user' && $userId > 0) {
        if (!function_exists('pnc_resolveEmployeeId')) {
            require_once __DIR__ . '/push_notification_controller.php';
        }
        $linkedEmployeeId = pnc_resolveEmployeeId($conn, $userId, $employeeId);
        if ($linkedEmployeeId > 0 && function_exists('pnc_buildMissingTokenMessage')) {
            return pnc_buildMissingTokenMessage($conn, $linkedEmployeeId);
        }
        return 'No device token in user_devices for this user. The user must open the mobile app and log in so save-device-token.php can register their device with EmployeeID.';
    }

    return 'No active device targets found for this request';
}

function cns_collectTargets($conn, $targetType, $userId = 0, $employeeId = 0)
{
    $targetType = strtolower(trim((string) $targetType));

    if ($targetType === 'all') {
        return cns_getLatestActiveDeviceTargets($conn);
    }

    if ($targetType === 'user' || $targetType === 'employee') {
        return cns_getUserDeviceTarget($conn, $userId, $employeeId);
    }

    return array();
}

function cns_queueTargets($conn, $batchId, $title, $body, $payload, $targets)
{
    cns_loadPushHelpers();

    $queued = 0;
    $title = trim((string) $title);
    $body = trim((string) $body);
    $payloadJson = json_encode(cns_normalizePayload($payload));
    $now = date('Y-m-d H:i:s');

    foreach ($targets as $target) {
        $token = trim((string) ($target['device_token'] ?? ''));
        if ($token === '') {
            continue;
        }

        $employeeIdForQueue = 0;
        if (isset($target['employee_id'])) {
            $employeeIdForQueue = (int) $target['employee_id'];
        } elseif (isset($target['user_id'])) {
            $employeeIdForQueue = pnc_normalizeStoredDeviceOwnerToEmployeeId($conn, (int) $target['user_id']);
        }

        $insert = _InsertTableRecords_prepare($conn, 'push_notification_queue', array(
            'batch_id' => $batchId,
            'user_id' => $employeeIdForQueue > 0 ? $employeeIdForQueue : null,
            'device_token' => $token,
            'title' => $title,
            'body' => $body,
            'payload' => $payloadJson,
            'status' => 'pending',
            'created_at' => $now,
        ));

        if (empty($insert['error'])) {
            $queued++;
        }
    }

    return $queued;
}

function cns_queueNotification($conn, $options)
{
    $targetType = isset($options['target']) ? (string) $options['target'] : 'user';
    $title = isset($options['title']) ? (string) $options['title'] : '';
    $body = isset($options['body']) ? (string) $options['body'] : '';
    $userId = isset($options['user_id']) ? (int) $options['user_id'] : 0;
    $employeeId = isset($options['employee_id']) ? (int) $options['employee_id'] : 0;
    $payload = isset($options['payload']) && is_array($options['payload']) ? $options['payload'] : array();

    if ($title === '' || $body === '') {
        return array('error' => true, 'message' => 'Title and Body are required');
    }

    $targets = cns_collectTargets($conn, $targetType, $userId, $employeeId);
    if (empty($targets)) {
        return array(
            'error' => true,
            'message' => cns_buildNoTargetsMessage($conn, $targetType, $userId, $employeeId),
        );
    }

    $batchId = cns_generateBatchId();
    $queued = cns_queueTargets($conn, $batchId, $title, $body, $payload, $targets);
    if ($queued <= 0) {
        return array('error' => true, 'message' => 'Unable to queue notification rows');
    }

    return array(
        'error' => false,
        'message' => 'Notification queued for background delivery',
        'batch_id' => $batchId,
        'target' => $targetType,
        'queued' => $queued,
    );
}

function cns_getBatchStatus($conn, $batchId)
{
    $batchId = trim((string) $batchId);
    if ($batchId === '') {
        return null;
    }

    $batchEsc = mysqli_real_escape_string($conn, $batchId);
    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                MIN(created_at) AS created_at,
                MAX(processed_at) AS last_processed_at
            FROM push_notification_queue
            WHERE batch_id = '$batchEsc'";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return null;
    }

    $row = mysqli_fetch_assoc($result);
    if (!$row || (int) ($row['total'] ?? 0) <= 0) {
        return null;
    }

    $pending = (int) ($row['pending'] ?? 0);
    $status = 'processing';
    if ($pending <= 0) {
        $status = ((int) ($row['sent'] ?? 0) > 0) ? 'completed' : 'failed';
    }

    return array(
        'batch_id' => $batchId,
        'status' => $status,
        'total' => (int) ($row['total'] ?? 0),
        'pending' => $pending,
        'sent' => (int) ($row['sent'] ?? 0),
        'failed' => (int) ($row['failed'] ?? 0),
        'created_at' => $row['created_at'] ?? null,
        'last_processed_at' => $row['last_processed_at'] ?? null,
    );
}

function cns_processQueue($conn, $limit = 50)
{
    $limit = max(1, min(200, (int) $limit));
    $notificationFile = dirname(__DIR__, 2) . '/core/notification.php';
    if (!file_exists($notificationFile)) {
        return array('error' => true, 'message' => 'Push notification module not found');
    }
    require_once $notificationFile;

    $sql = "SELECT id, user_id, device_token, title, body, payload
            FROM push_notification_queue
            WHERE status = 'pending'
            ORDER BY id ASC
            LIMIT $limit";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return array('error' => true, 'message' => 'Unable to read notification queue');
    }

    $processed = 0;
    $sent = 0;
    $failed = 0;
    $now = date('Y-m-d H:i:s');

    while ($row = mysqli_fetch_assoc($result)) {
        $queueId = (int) ($row['id'] ?? 0);
        if ($queueId <= 0) {
            continue;
        }

        $payload = json_decode((string) ($row['payload'] ?? ''), true);
        if (!is_array($payload)) {
            $payload = array();
        }

        $push = sendPushWithDetails(
            (string) ($row['device_token'] ?? ''),
            (string) ($row['title'] ?? ''),
            (string) ($row['body'] ?? ''),
            $payload
        );

        $processed++;
        if (!empty($push['ok'])) {
            $sent++;
            _UpdateTableRecords_prepare($conn, 'push_notification_queue', array(
                'status' => 'sent',
                'error_message' => null,
                'processed_at' => $now,
            ), array('id' => $queueId));
        } else {
            $failed++;
            _UpdateTableRecords_prepare($conn, 'push_notification_queue', array(
                'status' => 'failed',
                'error_message' => (string) ($push['error'] ?? 'FCM send failed'),
                'processed_at' => $now,
            ), array('id' => $queueId));
        }
    }

    return array(
        'error' => false,
        'message' => 'Queue batch processed',
        'processed' => $processed,
        'sent' => $sent,
        'failed' => $failed,
    );
}

function cns_getSiteBaseUrl()
{
    $env = getenv('APP_BASE_URL');
    if (is_string($env) && trim($env) !== '') {
        return rtrim(trim($env), '/');
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function cns_dispatchBackgroundWorker($limit = null)
{
    if (!function_exists('cns_getWorkerSecret')) {
        require_once dirname(__DIR__, 2) . '/api/notifications/inc/notification_config.php';
    }
    if ($limit === null) {
        $limit = cns_getDefaultProcessLimit();
    }
    $secret = cns_getWorkerSecret();
    $limit = max(1, min(200, (int) $limit));
    $url = cns_getSiteBaseUrl()
        . '/api/notifications/process-queue.php?secret='
        . rawurlencode($secret)
        . '&limit=' . $limit;

    if (!function_exists('curl_init')) {
        return false;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 400,
        CURLOPT_NOSIGNAL => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ));
    curl_exec($ch);
    curl_close($ch);

    return true;
}
