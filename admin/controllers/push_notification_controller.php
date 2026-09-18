<?php

/**
 * Push notifications via push_notifications_log + FCM.
 * App-facing identity is always employees.ID (EmployeeID):
 * - user_devices.user_id
 * - push_notifications_log.user_id
 * - push_notification_queue.user_id
 */

function pnc_resolveEmployeeId($conn, $userId = 0, $employeeId = 0)
{
    $userId = (int) $userId;
    $employeeId = (int) $employeeId;

    if ($employeeId > 0) {
        $employee = _getTableDetails($conn, 'employees', " WHERE ID = $employeeId AND IsActive = 1");
        if (is_array($employee) && !empty($employee['ID'])) {
            return (int) $employee['ID'];
        }
    }

    if ($userId > 0) {
        $employee = _getTableDetails($conn, 'employees', " WHERE ID = $userId AND IsActive = 1");
        if (is_array($employee) && !empty($employee['ID'])) {
            return (int) $employee['ID'];
        }

        $user = _getSQLDetails($conn, "
            SELECT u.EmployeeID
            FROM users u
            INNER JOIN employees e ON e.ID = u.EmployeeID AND e.IsActive = 1
            WHERE u.UserID = $userId
            LIMIT 1
        ");
        if (is_array($user) && !empty($user['EmployeeID']) && (int) $user['EmployeeID'] > 0) {
            return (int) $user['EmployeeID'];
        }
    }

    return 0;
}

/**
 * @deprecated Use pnc_resolveEmployeeId(). Kept for older API callers.
 */
function pnc_resolveCanonicalUserId($conn, $userId, $employeeId = 0)
{
    return pnc_resolveEmployeeId($conn, (int) $userId, (int) $employeeId);
}

function pnc_resolvePortalUserId($conn, $userId = 0, $employeeId = 0)
{
    $userId = (int) $userId;
    $employeeId = (int) $employeeId;

    if ($userId > 0) {
        $user = _getTableDetails($conn, 'users', " WHERE UserID = $userId");
        if (is_array($user) && !empty($user['UserID'])) {
            return (int) $user['UserID'];
        }
    }

    if ($employeeId > 0) {
        $user = _getTableDetails($conn, 'users', " WHERE EmployeeID = $employeeId");
        if (is_array($user) && !empty($user['UserID'])) {
            return (int) $user['UserID'];
        }
    }

    return 0;
}

function pnc_normalizeStoredDeviceOwnerToEmployeeId($conn, $storedId)
{
    $storedId = (int) $storedId;
    if ($storedId <= 0) {
        return 0;
    }

    $employee = _getTableDetails($conn, 'employees', " WHERE ID = $storedId AND IsActive = 1");
    if (is_array($employee) && !empty($employee['ID'])) {
        return (int) $employee['ID'];
    }

    $user = _getTableDetails($conn, 'users', " WHERE UserID = $storedId");
    if (is_array($user) && !empty($user['EmployeeID']) && (int) $user['EmployeeID'] > 0) {
        $linkedEmployeeId = (int) $user['EmployeeID'];
        $employee = _getTableDetails($conn, 'employees', " WHERE ID = $linkedEmployeeId AND IsActive = 1");
        if (is_array($employee) && !empty($employee['ID'])) {
            return $linkedEmployeeId;
        }
    }

    return 0;
}

function pnc_getDeviceTokensForEmployee($conn, $employeeId)
{
    $employeeId = pnc_resolveEmployeeId($conn, 0, $employeeId);
    if ($employeeId <= 0) {
        return array();
    }

    $tokens = array();
    $sql = "SELECT device_token FROM user_devices
            WHERE user_id = $employeeId AND IsActive = 1
            ORDER BY last_active DESC, id DESC
            LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $token = trim((string) ($row['device_token'] ?? ''));
        if ($token !== '') {
            $tokens[] = $token;
            return $tokens;
        }
    }

    $sql = "SELECT ud.device_token
            FROM user_devices ud
            INNER JOIN users u ON u.UserID = ud.user_id AND u.EmployeeID = $employeeId
            WHERE ud.IsActive = 1
            ORDER BY ud.last_active DESC, ud.id DESC
            LIMIT 1";
    $result = mysqli_query($conn, $sql);
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $token = trim((string) ($row['device_token'] ?? ''));
        if ($token !== '') {
            $tokens[] = $token;
        }
    }

    return $tokens;
}

function pnc_getDeviceTokensForUser($conn, $userId)
{
    $employeeId = pnc_resolveEmployeeId($conn, (int) $userId);
    return pnc_getDeviceTokensForEmployee($conn, $employeeId);
}

function pnc_findPushTargetForEmployee($conn, $employee_id)
{
    $employee_id = pnc_resolveEmployeeId($conn, 0, (int) $employee_id);
    if ($employee_id <= 0) {
        return null;
    }

    $tokens = pnc_getDeviceTokensForEmployee($conn, $employee_id);
    if (empty($tokens)) {
        return null;
    }

    return array(
        'employee_id' => $employee_id,
        'device_token' => $tokens[0],
    );
}

function pnc_buildMissingTokenMessage($conn, $employee_id, $resolvedUserId = 0)
{
    $employee_id = (int) $employee_id;

    $message = 'No device token in user_devices for employee #' . $employee_id;
    $message .= '. App must call save-device-token.php with EmployeeID (employees.ID).';

    return $message;
}

function pnc_countDeviceTokensForUser($conn, $userId)
{
    return count(pnc_getDeviceTokensForUser($conn, $userId));
}

function pnc_resolveUserIdFromEmployeeId($conn, $employee_id)
{
    return pnc_resolveEmployeeId($conn, 0, (int) $employee_id);
}

function pnc_sendPushDirect($conn, $employeeId, $title, $body, $data = array(), $deviceToken = '')
{
    $employeeId = pnc_resolveEmployeeId($conn, (int) $employeeId);
    $tokens = array();
    $deviceToken = trim((string) $deviceToken);
    if ($deviceToken !== '') {
        $tokens[] = $deviceToken;
    } else {
        $tokens = pnc_getDeviceTokensForEmployee($conn, $employeeId);
    }

    if ($employeeId <= 0 || empty($tokens)) {
        return array(
            'error' => true,
            'message' => 'No device tokens found for employee #' . (int) $employeeId . ' in user_devices',
        );
    }

    $notificationFile = dirname(__DIR__, 2) . '/core/notification.php';
    if (!file_exists($notificationFile)) {
        return array('error' => true, 'message' => 'Push notification module not found');
    }
    require_once $notificationFile;

    $lastError = '';
    $sent = false;
    foreach ($tokens as $token) {
        $result = function_exists('sendPushWithDetails')
            ? sendPushWithDetails($token, $title, $body, $data)
            : array('ok' => sendPush($token, $title, $body, $data), 'error' => '');
        if (!empty($result['ok'])) {
            $sent = true;
            break;
        }
        $lastError = trim((string) ($result['error'] ?? ''));
    }

    if (!$sent) {
        $message = 'Failed to send push for employee #' . (int) $employeeId;
        if ($lastError !== '') {
            $message .= ': ' . $lastError;
        }
        return array('error' => true, 'message' => $message);
    }

    return array(
        'error' => false,
        'message' => 'Push sent to employee #' . (int) $employeeId,
        'employee_id' => $employeeId,
    );
}

function pnc_formatDisplayDate($date)
{
    $date = trim((string) $date);
    if ($date === '' || $date === '0000-00-00') {
        return date('d-m-Y');
    }
    $ts = strtotime($date);
    return $ts ? date('d-m-Y', $ts) : $date;
}

function pnc_insertPushNotificationsLogRecord($conn, $data)
{
    try {
        return _InsertTableRecords_prepare($conn, 'push_notifications_log', $data);
    } catch (mysqli_sql_exception $e) {
        return array(
            'error' => true,
            'message' => $e->getMessage(),
            'last_insert_id' => 0,
        );
    }
}

function pnc_sendPushToEmployee($conn, $employee_id, $title, $body, $payload = array())
{
    $employee_id = pnc_resolveEmployeeId($conn, 0, (int) $employee_id);
    $target = pnc_findPushTargetForEmployee($conn, $employee_id);

    if ($employee_id <= 0) {
        return array('error' => true, 'message' => 'Invalid employee id');
    }

    if (!$target) {
        return array(
            'error' => true,
            'message' => pnc_buildMissingTokenMessage($conn, $employee_id),
            'employee_id' => $employee_id,
        );
    }

    $payloadData = array_merge(array(
        'type' => 'APPROVAL_NOTIFICATION',
        'employee_id' => $employee_id,
        'screen' => 'dashboard',
    ), $payload);

    $insert = pnc_insertPushNotificationsLogRecord($conn, array(
        'user_id' => $employee_id,
        'title' => $title,
        'body' => $body,
        'payload' => json_encode($payloadData),
        'status' => 'PENDING',
        'userstatus' => 'NotSeen',
        'created_at' => date('Y-m-d H:i:s'),
    ));

    $logId = (!empty($insert['error']) ? 0 : (isset($insert['last_insert_id']) ? (int) $insert['last_insert_id'] : 0));

    $pushResult = pnc_sendPushDirect($conn, $employee_id, $title, $body, $payloadData, $target['device_token']);

    if ($logId > 0) {
        if (!empty($pushResult['error'])) {
            _UpdateTableRecords_prepare($conn, 'push_notifications_log', array(
                'status' => 'failed',
                'error_message' => $pushResult['message'],
            ), array('id' => $logId));
        } else {
            _UpdateTableRecords_prepare($conn, 'push_notifications_log', array(
                'status' => 'sent',
            ), array('id' => $logId));
        }
    }

    return array_merge($pushResult, array('employee_id' => $employee_id));
}

function pnc_notifyAttendanceDecision($conn, $attendance_id, $decision, $layer)
{
    if (!function_exists('getAttendanceRecordById')) {
        return;
    }
    $attendance = getAttendanceRecordById($conn, (int) $attendance_id);
    if (!$attendance || empty($attendance['EmployeeID'])) {
        return;
    }

    $dateLabel = pnc_formatDisplayDate($attendance['RecordDate'] ?? '');
    $title = 'Attendance Notification';

    if ($layer === 'supervisor' && $decision === 'approved') {
        $body = "Your Attendance of $dateLabel is Approved By Supervisor and sent to HR.";
    } elseif ($layer === 'supervisor' && $decision === 'rejected') {
        $body = "Your Attendance of $dateLabel is Rejected By Supervisor.";
    } elseif ($layer === 'hr' && $decision === 'approved') {
        $body = "Your Attendance of $dateLabel is Approved By HR.";
    } elseif ($layer === 'hr' && $decision === 'rejected') {
        $body = "Your Attendance of $dateLabel is Rejected By HR.";
    } else {
        return;
    }

    pnc_sendPushToEmployee($conn, (int) $attendance['EmployeeID'], $title, $body, array(
        'module' => 'attendance',
        'attendance_id' => (int) $attendance_id,
        'decision' => $decision,
        'layer' => $layer,
        'record_date' => $attendance['RecordDate'] ?? '',
    ));
}

function pnc_notifyLeaveDecision($conn, $leave_id, $decision, $layer)
{
    if (!function_exists('getEmployeeLeaveDataByID')) {
        return;
    }
    $leave = getEmployeeLeaveDataByID($conn, array('ID' => (int) $leave_id));
    if (!$leave || empty($leave['EmployeeID'])) {
        return;
    }

    $fromDate = pnc_formatDisplayDate($leave['FromDate'] ?? '');
    $toDate = pnc_formatDisplayDate($leave['ToDate'] ?? '');
    $dateLabel = ($fromDate === $toDate) ? $fromDate : ($fromDate . ' to ' . $toDate);
    $title = 'Leave Notification';

    if ($layer === 'supervisor' && $decision === 'approved') {
        $body = "Your Leave of $dateLabel is Approved By Supervisor and sent to HR.";
    } elseif ($layer === 'supervisor' && $decision === 'rejected') {
        $body = "Your Leave of $dateLabel is Rejected By Supervisor.";
    } elseif ($layer === 'hr' && $decision === 'approved') {
        $body = "Your Leave of $dateLabel is Approved By HR.";
    } elseif ($layer === 'hr' && $decision === 'rejected') {
        $body = "Your Leave of $dateLabel is Rejected By HR.";
    } else {
        return;
    }

    pnc_sendPushToEmployee($conn, (int) $leave['EmployeeID'], $title, $body, array(
        'module' => 'leave',
        'leave_id' => (int) $leave_id,
        'decision' => $decision,
        'layer' => $layer,
        'from_date' => $leave['FromDate'] ?? '',
        'to_date' => $leave['ToDate'] ?? '',
    ));
}

function pnc_notifyConvenienceDecision($conn, $convenience_id, $decision, $layer, $approved_amount = '')
{
    $record = _getTableDetails($conn, 'employee_convenience', ' WHERE ID = ' . (int) $convenience_id);
    if (!$record || empty($record['EmployeeID'])) {
        return;
    }
    $date_label = pnc_formatDisplayDate($record['ConvenienceDate'] ?? '');
    $amount_label = trim((string) $approved_amount);
    if ($amount_label === '') {
        $amount_label = trim((string) ($record['ConvenienceAmount'] ?? ''));
    }
    $title = 'Convenience Notification';

    if ($layer === 'supervisor' && $decision === 'approved') {
        $body = "Your convenience claim for $date_label is approved by supervisor and sent to HR.";
    } elseif ($layer === 'supervisor' && $decision === 'rejected') {
        $body = "Your convenience claim for $date_label is rejected by supervisor.";
    } elseif ($layer === 'hr' && $decision === 'approved') {
        $body = "Your convenience reimbursement for $date_label is approved by HR. Approved amount: Rs. $amount_label.";
    } elseif ($layer === 'hr' && $decision === 'rejected') {
        $body = "Your convenience claim for $date_label is rejected by HR.";
    } else {
        return;
    }

    $payload = array(
        'module' => 'convenience',
        'convenience_id' => (int) $convenience_id,
        'decision' => $decision,
        'layer' => $layer,
        'approved_amount' => $amount_label,
        'convenience_date' => $record['ConvenienceDate'] ?? '',
        'screen' => 'dashboard',
    );

    $employee_id = (int) $record['EmployeeID'];
    $pushSent = false;

    if (!function_exists('pnc_publishNotification')) {
        $portalFile = __DIR__ . '/portal_notification_controller.php';
        if (is_readable($portalFile)) {
            require_once $portalFile;
        }
    }
    if (function_exists('pnc_publishNotification')) {
        $publish = pnc_publishNotification($conn, array(
            'target' => 'employee',
            'employee_id' => $employee_id,
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
            'send_push' => true,
            'save_portal' => true,
        ));
        $pushSent = is_array($publish) && empty($publish['error']) && empty($publish['push_failed']);
    }

    if (!$pushSent) {
        pnc_sendPushToEmployee($conn, $employee_id, $title, $body, $payload);
    }
}
