<?php

/**
 * Portal notifications: in-app inbox + optional mobile push via common_notification_service.
 */

function pnc_portalTableExists($conn, $table_name)
{
    $table_name = mysqli_real_escape_string($conn, $table_name);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
    return ($result && mysqli_num_rows($result) > 0);
}

function hasPortalNotificationAdminAccess($roles)
{
    if (isset($_SESSION['UserType'])) {
        $userType = (string) $_SESSION['UserType'];
        if (in_array($userType, array('Admin', 'Super Admin'), true)) {
            return true;
        }
    }
    if (!isset($roles['EmployeeRoles']) || !is_array($roles['EmployeeRoles'])) {
        return false;
    }
    $allowed = array('HR', 'Super Admin', 'Admin');
    foreach ($roles['EmployeeRoles'] as $role) {
        if (in_array($role, $allowed, true)) {
            return true;
        }
    }
    return false;
}

function pnc_getUserIdByUsername($conn, $username)
{
    $username = mysqli_real_escape_string($conn, trim((string) $username));
    if ($username === '') {
        return 0;
    }

    $user = _getTableDetails($conn, 'users', " WHERE UserName = '$username'");
    if (!is_array($user) || empty($user['UserID'])) {
        return 0;
    }

    $employeeId = (int) ($user['EmployeeID'] ?? 0);
    if ($employeeId > 0) {
        $employee = _getTableDetails($conn, 'employees', " WHERE ID = $employeeId AND IsActive = 1");
        if (!is_array($employee) || empty($employee['ID'])) {
            return 0;
        }
    }

    return (int) $user['UserID'];
}

function pnc_getPortalUserContextFromSession($conn, $session)
{
    $userId = 0;
    $employeeId = 0;

    if (isset($session['pb_username'])) {
        $userId = pnc_getUserIdByUsername($conn, $session['pb_username']);
    }
    if ($userId > 0) {
        $user = _getTableDetails($conn, 'users', " WHERE UserID = $userId");
        if (is_array($user) && !empty($user['EmployeeID']) && (int) $user['EmployeeID'] > 0) {
            $employeeId = (int) $user['EmployeeID'];
        }
    }
    if ($employeeId <= 0 && isset($session['Roles']['EmployeeID']) && (int) $session['Roles']['EmployeeID'] > 0) {
        $employeeId = (int) $session['Roles']['EmployeeID'];
    }

    return array(
        'user_id' => $userId,
        'employee_id' => $employeeId,
    );
}

function pnc_resolveTargetUserId($conn, $targetType, $userId = 0, $employeeId = 0)
{
    if (!function_exists('pnc_resolvePortalUserId')) {
        require_once __DIR__ . '/push_notification_controller.php';
    }
    $targetType = strtolower(trim((string) $targetType));
    if ($targetType === 'user') {
        return pnc_resolvePortalUserId($conn, (int) $userId, 0);
    }
    if ($targetType === 'employee') {
        return pnc_resolvePortalUserId($conn, 0, (int) $employeeId);
    }
    return 0;
}

/**
 * push_notifications_log.user_id and push_notification_queue.user_id = employees.ID.
 */
function pnc_resolveAppLogUserId($conn, $targetType, $userId = 0, $employeeId = 0)
{
    if (!function_exists('pnc_resolveEmployeeId')) {
        require_once __DIR__ . '/push_notification_controller.php';
    }
    return pnc_resolveEmployeeId($conn, (int) $userId, (int) $employeeId);
}

function pnc_insertPushNotificationsLog($conn, $appUserId, $title, $body, $payload = array())
{
    if (!function_exists('pnc_insertPushNotificationsLogRecord')) {
        require_once __DIR__ . '/push_notification_controller.php';
    }
    $appUserId = (int) $appUserId;
    if ($appUserId <= 0 || !pnc_portalTableExists($conn, 'push_notifications_log')) {
        return 0;
    }
    $insert = pnc_insertPushNotificationsLogRecord($conn, array(
        'user_id' => $appUserId,
        'title' => (string) $title,
        'body' => (string) $body,
        'payload' => json_encode(is_array($payload) ? $payload : array()),
        'status' => 'sent',
        'userstatus' => 'NotSeen',
        'created_at' => date('Y-m-d H:i:s'),
    ));
    return (!empty($insert['error']) ? 0 : (isset($insert['last_insert_id']) ? (int) $insert['last_insert_id'] : 0));
}

function pnc_publishNotification($conn, $options)
{
    if (!pnc_portalTableExists($conn, 'portal_notifications')) {
        return array('error' => true, 'message' => 'Run admin/sql/create_portal_notifications.sql first');
    }

    $targetType = isset($options['target']) ? strtolower(trim((string) $options['target'])) : 'user';
    $title = isset($options['title']) ? trim((string) $options['title']) : '';
    $body = isset($options['body']) ? trim((string) $options['body']) : '';
    $userId = isset($options['user_id']) ? (int) $options['user_id'] : 0;
    $employeeId = isset($options['employee_id']) ? (int) $options['employee_id'] : 0;
    $payload = isset($options['payload']) && is_array($options['payload']) ? $options['payload'] : array();
    $sendPush = !isset($options['send_push']) || $options['send_push'] !== false;
    $savePortal = !isset($options['save_portal']) || $options['save_portal'] !== false;
    $createdBy = isset($options['created_by']) ? (string) $options['created_by'] : '';

    if ($title === '' || $body === '') {
        return array('error' => true, 'message' => 'Title and Body are required');
    }
    if (!in_array($targetType, array('all', 'user', 'employee'), true)) {
        return array('error' => true, 'message' => 'Target must be all, user, or employee');
    }
    if ($targetType === 'user' && $userId <= 0) {
        return array('error' => true, 'message' => 'UserID is required when Target is user');
    }
    if ($targetType === 'employee' && $employeeId <= 0 && $userId <= 0) {
        return array('error' => true, 'message' => 'EmployeeID or UserID is required when Target is employee');
    }

    $resolvedUserId = pnc_resolveTargetUserId($conn, $targetType, $userId, $employeeId);
    if ($targetType === 'user') {
        $userId = $resolvedUserId;
    }
    if ($targetType === 'employee' && $employeeId <= 0 && $resolvedUserId > 0) {
        $user = _getTableDetails($conn, 'users', " WHERE UserID = $resolvedUserId AND IsActive = 1");
        if (is_array($user) && !empty($user['EmployeeID'])) {
            $employeeId = (int) $user['EmployeeID'];
        }
    }

    $portalId = 0;
    $pushBatchId = null;
    $pushResult = null;
    $appLogId = 0;

    if ($savePortal) {
        $insert = _InsertTableRecords_prepare($conn, 'portal_notifications', array(
            'target_type' => $targetType,
            'user_id' => $targetType === 'user' ? ($userId > 0 ? $userId : null) : null,
            'employee_id' => $targetType === 'employee' ? ($employeeId > 0 ? $employeeId : null) : null,
            'title' => $title,
            'body' => $body,
            'payload' => json_encode($payload),
            'send_push' => $sendPush ? 1 : 0,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        if (!empty($insert['error'])) {
            return array('error' => true, 'message' => 'Unable to save portal notification');
        }
        $portalId = isset($insert['last_insert_id']) ? (int) $insert['last_insert_id'] : 0;
    }

    if ($targetType !== 'all') {
        $appLogUserId = pnc_resolveAppLogUserId($conn, $targetType, $userId, $employeeId);
        if ($appLogUserId > 0) {
            $appLogId = pnc_insertPushNotificationsLog($conn, $appLogUserId, $title, $body, $payload);
        }
    }

    if ($sendPush) {
        if (!function_exists('cns_queueNotification')) {
            require_once __DIR__ . '/common_notification_service.php';
        }
        $pushResult = cns_queueNotification($conn, array(
            'target' => $targetType,
            'title' => $title,
            'body' => $body,
            'user_id' => $userId,
            'employee_id' => $employeeId,
            'payload' => $payload,
        ));
        if (!empty($pushResult['error'])) {
            if ($portalId > 0) {
                return array(
                    'error' => false,
                    'message' => 'Portal notification saved. Mobile push was not sent: ' . ($pushResult['message'] ?? 'no device target found'),
                    'portal_notification_id' => $portalId,
                    'app_log_id' => $appLogId,
                    'push_failed' => true,
                    'push' => $pushResult,
                );
            }
            return $pushResult;
        }
        $pushBatchId = $pushResult['batch_id'] ?? null;
        if ($portalId > 0 && $pushBatchId) {
            _UpdateTableRecords_prepare($conn, 'portal_notifications', array(
                'push_batch_id' => $pushBatchId,
            ), array('id' => $portalId));
        }
        if (function_exists('cns_dispatchBackgroundWorker')) {
            cns_dispatchBackgroundWorker();
        }
    }

    return array(
        'error' => false,
        'message' => 'Notification published successfully',
        'portal_notification_id' => $portalId,
        'app_log_id' => $appLogId,
        'push_batch_id' => $pushBatchId,
        'target' => $targetType,
        'push' => $pushResult,
    );
}

function pnc_buildNotificationVisibilitySql($conn, $userId, $employeeId)
{
    $userId = (int) $userId;
    $employeeId = (int) $employeeId;
    $parts = array("n.target_type = 'all'");
    if ($userId > 0) {
        $parts[] = "(n.target_type = 'user' AND n.user_id = $userId)";
    }
    if ($employeeId > 0) {
        $parts[] = "(n.target_type = 'employee' AND n.employee_id = $employeeId)";
    }
    return '(' . implode(' OR ', $parts) . ')';
}

function pnc_getNotificationsForUser($conn, $userId, $employeeId = 0, $limit = 30)
{
    if (!pnc_portalTableExists($conn, 'portal_notifications') || (int) $userId <= 0) {
        return array();
    }

    $userId = (int) $userId;
    $employeeId = (int) $employeeId;
    $limit = max(1, min(100, (int) $limit));
    $visibility = pnc_buildNotificationVisibilitySql($conn, $userId, $employeeId);

    $sql = "
        SELECT
            n.id,
            n.target_type,
            n.title,
            n.body,
            n.payload,
            n.created_by,
            n.created_at,
            COALESCE(r.userstatus, 'NotSeen') AS userstatus
        FROM portal_notifications n
        LEFT JOIN portal_notification_reads r
            ON r.notification_id = n.id AND r.user_id = $userId
        WHERE $visibility
        ORDER BY n.id DESC
        LIMIT $limit
    ";

    $rows = _getSQLRecords($conn, $sql);
    if (!is_array($rows)) {
        return array();
    }

    foreach ($rows as &$row) {
        $row['payload'] = !empty($row['payload']) ? json_decode($row['payload'], true) : array();
        if (!is_array($row['payload'])) {
            $row['payload'] = array();
        }
    }
    unset($row);

    return $rows;
}

function pnc_getUnreadCountForUser($conn, $userId, $employeeId = 0)
{
    if (!pnc_portalTableExists($conn, 'portal_notifications') || (int) $userId <= 0) {
        return 0;
    }

    $userId = (int) $userId;
    $employeeId = (int) $employeeId;
    $visibility = pnc_buildNotificationVisibilitySql($conn, $userId, $employeeId);

    $sql = "
        SELECT COUNT(*) AS total
        FROM portal_notifications n
        LEFT JOIN portal_notification_reads r
            ON r.notification_id = n.id AND r.user_id = $userId
        WHERE $visibility
          AND COALESCE(r.userstatus, 'NotSeen') = 'NotSeen'
    ";
    $row = _getSQLDetails($conn, $sql);
    return is_array($row) ? (int) ($row['total'] ?? 0) : 0;
}

function pnc_userCanViewNotification($conn, $notificationId, $userId, $employeeId = 0)
{
    $notificationId = (int) $notificationId;
    $userId = (int) $userId;
    $employeeId = (int) $employeeId;
    if ($notificationId <= 0 || $userId <= 0) {
        return false;
    }

    $notification = _getTableDetails($conn, 'portal_notifications', " WHERE id = $notificationId");
    if (!$notification) {
        return false;
    }

    $targetType = strtolower((string) ($notification['target_type'] ?? ''));
    if ($targetType === 'all') {
        return true;
    }
    if ($targetType === 'user' && (int) ($notification['user_id'] ?? 0) === $userId) {
        return true;
    }
    if ($targetType === 'employee' && $employeeId > 0 && (int) ($notification['employee_id'] ?? 0) === $employeeId) {
        return true;
    }
    return false;
}

function pnc_resolveMobileNotificationUserContext($conn, $userId)
{
    $userIdInt = (int) $userId;
    $employeeId = 0;
    $portalUserId = 0;

    if ($userIdInt <= 0) {
        return array(
            'request_user_id' => 0,
            'employee_id' => 0,
            'portal_user_id' => 0,
            'app_log_user_id' => 0,
        );
    }

    $employee = _getTableDetails($conn, 'employees', " WHERE ID = $userIdInt AND IsActive = 1");
    if (is_array($employee) && !empty($employee['ID'])) {
        $employeeId = $userIdInt;
        $userRow = _getTableDetails($conn, 'users', " WHERE EmployeeID = $employeeId");
        if (is_array($userRow) && !empty($userRow['UserID'])) {
            $portalUserId = (int) $userRow['UserID'];
        }
    } else {
        $portalUserId = $userIdInt;
        $userRow = _getTableDetails($conn, 'users', " WHERE UserID = $userIdInt");
        if (is_array($userRow) && !empty($userRow['EmployeeID']) && (int) $userRow['EmployeeID'] > 0) {
            $employeeId = (int) $userRow['EmployeeID'];
        }
    }

    return array(
        'request_user_id' => $userIdInt,
        'employee_id' => $employeeId,
        'portal_user_id' => $portalUserId,
        'app_log_user_id' => $employeeId > 0 ? $employeeId : $userIdInt,
    );
}

function pnc_markAllPushLogNotificationsSeen($conn, $userId)
{
    $ctx = pnc_resolveMobileNotificationUserContext($conn, $userId);
    if ($ctx['app_log_user_id'] <= 0 && $ctx['request_user_id'] <= 0) {
        return 0;
    }

    $appLogUserId = (int) $ctx['app_log_user_id'];
    $legacyUserId = (int) $ctx['request_user_id'];
    $sql = "UPDATE push_notifications_log
            SET userstatus = 'Seen', status = 'READ'
            WHERE userstatus = 'NotSeen'
              AND (user_id = $appLogUserId OR user_id = $legacyUserId)";

    if (!mysqli_query($conn, $sql)) {
        return false;
    }

    return (int) mysqli_affected_rows($conn);
}

function pnc_markAllPortalNotificationsSeenForMobileUser($conn, $userId)
{
    $ctx = pnc_resolveMobileNotificationUserContext($conn, $userId);
    $portalUserId = (int) ($ctx['portal_user_id'] ?? 0);
    $employeeId = (int) ($ctx['employee_id'] ?? 0);
    if ($portalUserId <= 0) {
        return 0;
    }

    $marked = 0;
    $items = pnc_getNotificationsForUser($conn, $portalUserId, $employeeId, 100);
    foreach ($items as $item) {
        if (($item['userstatus'] ?? 'NotSeen') !== 'NotSeen') {
            continue;
        }
        $portalId = (int) ($item['id'] ?? 0);
        if ($portalId <= 0) {
            continue;
        }
        $result = pnc_markNotificationSeen($conn, $portalId, $portalUserId);
        if (empty($result['error'])) {
            $marked++;
        }
    }

    return $marked;
}

function pnc_markMobileNotificationSeen($conn, $notificationId, $userId)
{
    $notificationIdRaw = is_scalar($notificationId) ? trim((string) $notificationId) : '';
    $ctx = pnc_resolveMobileNotificationUserContext($conn, $userId);

    if ($notificationIdRaw !== '' && stripos($notificationIdRaw, 'portal_') === 0) {
        $portalId = (int) substr($notificationIdRaw, 7);
        if ($portalId <= 0 || (int) ($ctx['portal_user_id'] ?? 0) <= 0) {
            return array('error' => true, 'message' => 'Invalid portal notification');
        }
        if (!pnc_userCanViewNotification($conn, $portalId, (int) $ctx['portal_user_id'], (int) $ctx['employee_id'])) {
            return array('error' => true, 'message' => 'Notification not found or already seen');
        }
        return pnc_markNotificationSeen($conn, $portalId, (int) $ctx['portal_user_id']);
    }

    $notificationId = (int) $notificationIdRaw;
    $appLogUserId = (int) ($ctx['app_log_user_id'] ?? 0);
    $legacyUserId = (int) ($ctx['request_user_id'] ?? 0);
    if ($notificationId <= 0 || $appLogUserId <= 0) {
        return array('error' => true, 'message' => 'notification_id and user_id required');
    }

    $notification = _getTableDetails(
        $conn,
        'push_notifications_log',
        "WHERE id = $notificationId
         AND (user_id = $appLogUserId OR user_id = $legacyUserId)
         AND userstatus = 'NotSeen'"
    );
    if (empty($notification)) {
        return array('error' => true, 'message' => 'Notification not found or already seen');
    }

    _UpdateTableRecords_prepare(
        $conn,
        'push_notifications_log',
        array(
            'userstatus' => 'Seen',
            'status' => 'READ',
        ),
        array('id' => $notificationId)
    );

    return array('error' => false, 'message' => 'Notification marked as Seen');
}

function pnc_markNotificationSeen($conn, $notificationId, $userId)
{
    $notificationId = (int) $notificationId;
    $userId = (int) $userId;
    if ($notificationId <= 0 || $userId <= 0) {
        return array('error' => true, 'message' => 'Invalid notification');
    }
    if (!pnc_portalTableExists($conn, 'portal_notification_reads')) {
        return array('error' => true, 'message' => 'Notification reads table missing');
    }

    $existing = _getTableDetails($conn, 'portal_notification_reads', " WHERE notification_id = $notificationId AND user_id = $userId");
    $now = date('Y-m-d H:i:s');
    if (is_array($existing) && !empty($existing['id'])) {
        _UpdateTableRecords_prepare($conn, 'portal_notification_reads', array(
            'userstatus' => 'Seen',
            'seen_at' => $now,
        ), array('id' => (int) $existing['id']));
    } else {
        _InsertTableRecords_prepare($conn, 'portal_notification_reads', array(
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'userstatus' => 'Seen',
            'seen_at' => $now,
        ));
    }

    return array('error' => false, 'message' => 'Notification marked as seen');
}

function pnc_listAdminNotifications($conn, $limit = 100)
{
    if (!pnc_portalTableExists($conn, 'portal_notifications')) {
        return array();
    }
    $limit = max(1, min(500, (int) $limit));
    $sql = "SELECT * FROM portal_notifications ORDER BY id DESC LIMIT $limit";
    $rows = _getSQLRecords($conn, $sql);
    return is_array($rows) ? $rows : array();
}

function pnc_getActivePortalUsers($conn)
{
    $sql = "
        SELECT u.*
        FROM users u
        INNER JOIN employees e ON e.ID = u.EmployeeID AND e.IsActive = 1
        ORDER BY u.UserName ASC
    ";
    $rows = _getSQLRecords($conn, $sql);
    return is_array($rows) ? $rows : array();
}

function pnc_getActiveEmployeesForNotify($conn)
{
    return _getTableRecords($conn, 'employees', ' WHERE IsActive = 1 AND Vendor = 0 ORDER BY Name ASC');
}
