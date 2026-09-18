<?php

require_once dirname(__DIR__, 2) . '/includes/autoloader.inc.php';
require_once dirname(__DIR__, 2) . '/attendance-list/controller/attendance_controller.php';
require_once dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';

function convenienceEnsurePushNotificationController()
{
    if (function_exists('pnc_notifyConvenienceDecision')) {
        return true;
    }
    $pushFile = dirname(__DIR__, 2) . '/controllers/push_notification_controller.php';
    if (is_readable($pushFile)) {
        require_once $pushFile;
    }
    return function_exists('pnc_notifyConvenienceDecision');
}

function convenienceNotifyDecisionSafe($conn, $convenience_id, $decision, $layer, $approved_amount = '')
{
    $record = getConvenienceRecordById($conn, (int) $convenience_id);
    if (!$record || empty($record['EmployeeID'])) {
        return;
    }

    convenienceEnsurePushNotificationController();

    if (function_exists('pnc_notifyConvenienceDecision')) {
        pnc_notifyConvenienceDecision($conn, (int) $convenience_id, $decision, $layer, $approved_amount);
        return;
    }

    convenienceSendEmployeeDecisionNotification($conn, $record, $decision, $layer, $approved_amount);
}

function convenienceBuildDecisionNotificationMessage($record, $decision, $layer, $approved_amount = '')
{
    if (!function_exists('pnc_formatDisplayDate')) {
        convenienceEnsurePushNotificationController();
    }

    $date_label = function_exists('pnc_formatDisplayDate')
        ? pnc_formatDisplayDate($record['ConvenienceDate'] ?? '')
        : trim((string) ($record['ConvenienceDate'] ?? ''));
    $amount_label = trim((string) $approved_amount);
    if ($amount_label === '') {
        $amount_label = trim((string) ($record['ConvenienceAmount'] ?? ''));
    }

    $title = 'Convenience Notification';
    $body = '';

    if ($layer === 'supervisor' && $decision === 'approved') {
        $body = "Your convenience claim for $date_label is approved by supervisor and sent to HR.";
    } elseif ($layer === 'supervisor' && $decision === 'rejected') {
        $body = "Your convenience claim for $date_label is rejected by supervisor.";
    } elseif ($layer === 'hr' && $decision === 'approved') {
        $body = "Your convenience reimbursement for $date_label is approved by HR (Rs. $amount_label). Payment is pending with finance.";
    } elseif ($layer === 'hr' && $decision === 'rejected') {
        $body = "Your convenience claim for $date_label is rejected by HR.";
    } elseif ($layer === 'finance' && $decision === 'paid') {
        $body = "Your convenience reimbursement for $date_label has been paid. Amount: Rs. $amount_label.";
    } elseif ($layer === 'finance' && $decision === 'rejected') {
        $body = "Your convenience reimbursement for $date_label is rejected by finance. Payment will not be processed.";
    }

    if ($body === '') {
        return null;
    }

    return array(
        'title' => $title,
        'body' => $body,
        'payload' => array(
            'module' => 'convenience',
            'convenience_id' => (int) ($record['ID'] ?? 0),
            'decision' => $decision,
            'layer' => $layer,
            'approved_amount' => $amount_label,
            'convenience_date' => $record['ConvenienceDate'] ?? '',
            'screen' => 'dashboard',
        ),
    );
}

function convenienceSendEmployeeDecisionNotification($conn, $record, $decision, $layer, $approved_amount = '')
{
    $employee_id = (int) ($record['EmployeeID'] ?? 0);
    if ($employee_id <= 0) {
        return;
    }

    $notification = convenienceBuildDecisionNotificationMessage($record, $decision, $layer, $approved_amount);
    if (!$notification) {
        return;
    }

    $pushSent = false;
    $portalFile = dirname(__DIR__, 2) . '/controllers/portal_notification_controller.php';
    if (is_readable($portalFile)) {
        require_once $portalFile;
        if (function_exists('pnc_publishNotification')) {
            $publish = pnc_publishNotification($conn, array(
                'target' => 'employee',
                'employee_id' => $employee_id,
                'title' => $notification['title'],
                'body' => $notification['body'],
                'payload' => $notification['payload'],
                'send_push' => true,
                'save_portal' => true,
            ));
            $pushSent = is_array($publish) && empty($publish['error']) && empty($publish['push_failed']);
        }
    }

    if (!$pushSent && function_exists('pnc_sendPushToEmployee')) {
        pnc_sendPushToEmployee(
            $conn,
            $employee_id,
            $notification['title'],
            $notification['body'],
            $notification['payload']
        );
    }
}

function convenienceStatusPendingSupervisor()
{
    return '1';
}

function convenienceStatusSupervisorApproved()
{
    return '2';
}

function convenienceStatusHrApproved()
{
    return '5';
}

function convenienceStatusPaid()
{
    return '6';
}

function convenienceStatusRejectedSupervisor()
{
    return '-1';
}

function convenienceStatusRejectedHr()
{
    return '-2';
}

function convenienceStatusRejectedFinance()
{
    return '-3';
}

function convenienceStatusMatchesPendingSupervisor($status)
{
    $status = trim((string) $status);
    return $status === '1' || $status === '01';
}

function convenienceStatusMatchesSupervisorApproved($status)
{
    $status = trim((string) $status);
    return $status === '2' || $status === '02';
}

function conveniencePendingSupervisorStatusSql()
{
    return "Status IN ('1', 1)";
}

function convenienceSupervisorApprovedStatusSql()
{
    return "Status IN ('2', 2)";
}

function convenienceSupervisorActionHours()
{
    return 24;
}

function convenienceGetRequestTimestamp($record)
{
    if (!is_array($record)) {
        return 0;
    }

    $date = trim((string) ($record['CreatedDate'] ?? ''));
    $time = trim((string) ($record['CreatedTime'] ?? ''));
    if ($date === '' || $date === '0000-00-00') {
        $date = trim((string) ($record['ConvenienceDate'] ?? ''));
    }
    if ($time === '') {
        $time = '00:00:00';
    }
    if ($date === '' || $date === '0000-00-00') {
        return 0;
    }

    $timestamp = strtotime($date . ' ' . $time);
    return $timestamp ? (int) $timestamp : 0;
}

function convenienceIsWithinSupervisorActionWindow($record)
{
    $requested_at = convenienceGetRequestTimestamp($record);
    if ($requested_at <= 0) {
        return true;
    }

    return (time() - $requested_at) <= (convenienceSupervisorActionHours() * 3600);
}

function convenienceIsSupervisorActionExpired($record)
{
    if (!is_array($record) || !convenienceStatusMatchesPendingSupervisor($record['Status'] ?? '')) {
        return false;
    }

    return !convenienceIsWithinSupervisorActionWindow($record);
}

function convenienceSupervisorExpiredPendingSql($conn = null)
{
    $hours = (int) convenienceSupervisorActionHours();
    if ($conn && convenienceColumnExists($conn, 'CreatedDate')) {
        $date_expr = "COALESCE(NULLIF(a.CreatedDate, '0000-00-00'), NULLIF(a.ConvenienceDate, '0000-00-00'))";
    } else {
        $date_expr = "NULLIF(a.ConvenienceDate, '0000-00-00')";
    }

    if ($conn && convenienceColumnExists($conn, 'CreatedTime')) {
        $time_expr = "COALESCE(NULLIF(a.CreatedTime, ''), '00:00:00')";
    } else {
        $time_expr = "'00:00:00'";
    }

    return "(a.Status IN ('1', 1)"
        . " AND $date_expr IS NOT NULL"
        . " AND CONCAT($date_expr, ' ', $time_expr) <= DATE_SUB(NOW(), INTERVAL $hours HOUR))";
}

function convenienceHrActionableStatusSql($conn = null)
{
    return '(' . convenienceSupervisorApprovedStatusSql() . ' OR ' . convenienceSupervisorExpiredPendingSql($conn) . ')';
}

function convenienceFinanceActionableStatusSql()
{
    return "a.Status IN ('" . convenienceStatusHrApproved() . "', " . convenienceStatusHrApproved() . ")";
}

function conveniencePaidStatusSql()
{
    return "a.Status IN ('" . convenienceStatusPaid() . "', " . convenienceStatusPaid() . ")";
}

function convenienceBuildStatusFilterSql($conn, $filters)
{
    $status_values = convenienceParseMultiFilterValues(isset($filters['status']) ? $filters['status'] : '');
    if (empty($status_values)) {
        return '';
    }

    $or_parts = array();
    if (in_array('hr_actionable', $status_values, true)) {
        $or_parts[] = convenienceHrActionableStatusSql($conn);
    }
    if (in_array('finance_actionable', $status_values, true) || in_array('payment_pending', $status_values, true)) {
        $or_parts[] = convenienceFinanceActionableStatusSql();
    }
    if (in_array('payment_done', $status_values, true)) {
        $or_parts[] = conveniencePaidStatusSql();
    }
    if (in_array('supervisor_timeout', $status_values, true)) {
        $or_parts[] = convenienceSupervisorExpiredPendingSql($conn);
    }
    $status_values = array_values(array_diff($status_values, array('hr_actionable', 'finance_actionable', 'payment_pending', 'payment_done', 'supervisor_timeout')));

    if (!empty($status_values)) {
        $escaped = array();
        foreach ($status_values as $value) {
            $escaped[] = "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
        }
        $or_parts[] = 'a.Status IN (' . implode(',', $escaped) . ')';
    }

    if (empty($or_parts)) {
        return '';
    }

    return ' AND (' . implode(' OR ', $or_parts) . ')';
}

function convenienceGetSupervisorActionDeadlineLabel($record)
{
    $requested_at = convenienceGetRequestTimestamp($record);
    if ($requested_at <= 0) {
        return '';
    }

    return date('Y-m-d H:i:s', $requested_at + (convenienceSupervisorActionHours() * 3600));
}

function convenienceColumnExists($conn, $column_name)
{
    $column_name = mysqli_real_escape_string($conn, $column_name);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `employee_convenience` LIKE '$column_name'");
    return ($result && mysqli_num_rows($result) > 0);
}

function getConvenienceStatusLabel($status)
{
    $status = (string) $status;
    $map = array(
        '1' => 'Pending Supervisor Approval',
        '2' => 'Supervisor Approved - Pending HR',
        '3' => 'Finance Approval Pending',
        '4' => 'CFO Approval Pending',
        '5' => 'HR Approved - Pending Payment',
        '6' => 'Payment Done',
        '-1' => 'Rejected by Supervisor',
        '-2' => 'Rejected by HR',
        '-3' => 'Rejected by Finance',
        '-4' => 'Rejected by CFO',
    );
    return isset($map[$status]) ? $map[$status] : ('Status ' . $status);
}

function buildConvenienceStatusBadge($status)
{
    $status = (string) $status;
    $label = htmlspecialchars(getConvenienceStatusLabel($status));
    if ($status === convenienceStatusPaid()) {
        return '<span class="badge badge-success">' . $label . '</span>';
    }
    if ($status === convenienceStatusHrApproved()) {
        return '<span class="badge badge-info">' . $label . '</span>';
    }
    if ($status === convenienceStatusSupervisorApproved()) {
        return '<span class="badge badge-info">' . $label . '</span>';
    }
    if ($status === convenienceStatusPendingSupervisor()) {
        return '<span class="badge badge-warning">' . $label . '</span>';
    }
    if ((int) $status < 0) {
        return '<span class="badge badge-danger">' . $label . '</span>';
    }
    return '<span class="badge badge-secondary">' . $label . '</span>';
}

function buildConveniencePaymentStatusBadge($record)
{
    $status = (string) ($record['Status'] ?? '');
    if ($status === convenienceStatusHrApproved()) {
        return '<span class="badge badge-warning">Payment Pending</span>';
    }
    if ($status === convenienceStatusPaid()) {
        return '<span class="badge badge-success">Payment Done</span>';
    }
    if ($status === convenienceStatusRejectedFinance()) {
        return '<span class="badge badge-danger">Rejected by Finance</span>';
    }
    return '<span class="text-muted">-</span>';
}

function buildConvenienceStatusBadgeForRecord($record)
{
    if (convenienceIsSupervisorActionExpired($record)) {
        return '<span class="badge badge-danger">Supervisor Window Expired - HR Action</span>';
    }

    return buildConvenienceStatusBadge((string) ($record['Status'] ?? ''));
}

function hasHrConvenienceApprovalAccess($roles)
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
    $hr_roles = array('HR', 'Super Admin', 'Admin');
    foreach ($roles['EmployeeRoles'] as $role) {
        if (in_array($role, $hr_roles, true)) {
            return true;
        }
    }
    return false;
}

function hasFinanceConveniencePaymentAccess($roles)
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
    $finance_roles = array('Finance', 'Accounts', 'CFO', 'Super Admin', 'Admin');
    foreach ($roles['EmployeeRoles'] as $role) {
        if (in_array($role, $finance_roles, true)) {
            return true;
        }
    }
    return false;
}

function getConvenienceTeamEmployeeIds($conn, $leader_employee_id, $active_only = false)
{
    $leader_employee_id = (int) $leader_employee_id;
    if ($leader_employee_id <= 0) {
        return array();
    }

    $ids = getSupervisedEmployeeIds($conn, $leader_employee_id, $active_only);
    $employee = new Employee($conn);
    $city_lead_employees = $employee->getMappedEmployeesofCityLead($leader_employee_id);
    foreach ($city_lead_employees as $row) {
        if (!empty($row['ID'])) {
            $ids[] = (int) $row['ID'];
        }
    }
    return array_values(array_unique($ids));
}

function getConvenienceTeamEmployeesList($conn, $leader_employee_id, $active_only = false)
{
    $ids = getConvenienceTeamEmployeeIds($conn, $leader_employee_id, $active_only);
    if (empty($ids)) {
        return array();
    }
    $id_list = implode(',', array_map('intval', $ids));
    $sql = "SELECT * FROM employees WHERE ID IN ($id_list)";
    if ($active_only) {
        $sql .= ' AND IsActive = 1';
    }
    $sql .= ' ORDER BY Name ASC';
    return _getSQLRecords($conn, $sql);
}

function employeeHasConvenienceTeam($conn, $leader_employee_id)
{
    return count(getConvenienceTeamEmployeeIds($conn, $leader_employee_id, false)) > 0;
}

function employeeIsInConvenienceTeam($conn, $employee_id, $leader_employee_id)
{
    $employee_id = (int) $employee_id;
    $leader_employee_id = (int) $leader_employee_id;
    if ($employee_id <= 0 || $leader_employee_id <= 0) {
        return false;
    }
    return in_array($employee_id, getConvenienceTeamEmployeeIds($conn, $leader_employee_id, false), true);
}

function getConvenienceRecordById($conn, $convenience_id)
{
    $convenience_id = (int) $convenience_id;
    return _getTableDetails($conn, 'employee_convenience', " WHERE ID = $convenience_id");
}

function convenienceUpdateSucceeded($conn, $update_result)
{
    if (!is_array($update_result) || !isset($update_result['error']) || $update_result['error'] === true) {
        return false;
    }
    return mysqli_affected_rows($conn) > 0;
}

function convenienceUpdateErrorMessage($update_result)
{
    if (is_array($update_result) && !empty($update_result['message'])) {
        return $update_result['message'];
    }
    return 'Unable to update convenience record.';
}

function appendConvenienceRemarks($conn, $existing_remarks, $new_remarks)
{
    $new_remarks = trim((string) $new_remarks);
    if ($new_remarks === '') {
        return (string) $existing_remarks;
    }
    $new_remarks = mysqli_real_escape_string($conn, $new_remarks);
    if ($existing_remarks === '' || $existing_remarks === null) {
        return $new_remarks;
    }
    return $existing_remarks . '<br>' . $new_remarks;
}

function canSupervisorApproveConvenience($conn, $convenience_id, $approver_employee_id)
{
    $record = getConvenienceRecordById($conn, $convenience_id);
    if (!$record || !isset($record['EmployeeID'])) {
        return false;
    }
    if (!convenienceStatusMatchesPendingSupervisor($record['Status'] ?? '')) {
        return false;
    }
    if (!convenienceIsWithinSupervisorActionWindow($record)) {
        return false;
    }
    $employee_id = (int) $record['EmployeeID'];
    $approver_employee_id = (int) $approver_employee_id;
    if ($approver_employee_id <= 0) {
        return false;
    }
    return employeeIsInConvenienceTeam($conn, $employee_id, $approver_employee_id);
}

function canHrApproveConvenience($conn, $convenience_id, $roles)
{
    if (!hasHrConvenienceApprovalAccess($roles)) {
        return false;
    }
    $record = getConvenienceRecordById($conn, $convenience_id);
    if (!$record) {
        return false;
    }
    if (convenienceStatusMatchesSupervisorApproved($record['Status'] ?? '')) {
        return true;
    }

    return convenienceIsSupervisorActionExpired($record);
}

function getHrEmployeeIdsForNotification($conn)
{
    $ids = array();
    $sql = "SELECT DISTINCT ur.EmployeeID
            FROM user_roles ur
            INNER JOIN employees e ON e.ID = ur.EmployeeID
            WHERE e.IsActive = 1 AND ur.IsActive = 1 AND ur.Role IN ('HR', 'Admin', 'Super Admin')";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = (int) $row['EmployeeID'];
        }
    }
    if (empty($ids)) {
        $fallback = _getTableRecords($conn, 'employees', " WHERE IsActive = 1 AND Designation LIKE '%HR%' ORDER BY ID ASC LIMIT 20");
        foreach ($fallback as $row) {
            $ids[] = (int) $row['ID'];
        }
    }
    return array_values(array_unique($ids));
}

function getFinanceEmployeeIdsForNotification($conn)
{
    $ids = array();
    $sql = "SELECT DISTINCT ur.EmployeeID
            FROM user_roles ur
            INNER JOIN employees e ON e.ID = ur.EmployeeID
            WHERE e.IsActive = 1 AND ur.IsActive = 1 AND ur.Role IN ('Finance', 'Accounts', 'CFO', 'Admin', 'Super Admin')";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = (int) $row['EmployeeID'];
        }
    }
    return array_values(array_unique($ids));
}

function getConvenienceCityLeadIdsForEmployee($conn, $employee_id)
{
    $employee_id = (int) $employee_id;
    if ($employee_id <= 0) {
        return array();
    }

    $employee = _getTableDetails($conn, 'employees', " WHERE ID = $employee_id");
    if (!is_array($employee)) {
        return array();
    }

    $city = mysqli_real_escape_string($conn, trim((string) ($employee['City'] ?? '')));
    if ($city === '') {
        return array();
    }

    $ids = array();
    $sql = "SELECT DISTINCT cd.CorporateLead AS lead_id
            FROM citydata cd
            WHERE cd.StateID IN (
                SELECT DISTINCT StateID FROM citydata WHERE CityName = '$city'
            ) AND cd.CorporateLead > 0";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            if (!empty($row['lead_id']) && (int) $row['lead_id'] > 0) {
                $ids[] = (int) $row['lead_id'];
            }
        }
    }

    return array_values(array_unique($ids));
}

function getConvenienceSupervisorIdsForEmployee($conn, $employee_id)
{
    $employee_id = (int) $employee_id;
    if ($employee_id <= 0) {
        return array();
    }

    $ids = array();
    $employee = _getTableDetails($conn, 'employees', " WHERE ID = $employee_id");
    if (is_array($employee) && !empty($employee['Supervisor']) && (int) $employee['Supervisor'] > 0) {
        $ids[] = (int) $employee['Supervisor'];
    }

    $ids = array_merge($ids, getConvenienceCityLeadIdsForEmployee($conn, $employee_id));

    return array_values(array_unique(array_filter($ids, function ($id) {
        return (int) $id > 0;
    })));
}

function conveniencePublishNotificationToEmployees($conn, $employee_ids, $title, $body, $payload = array())
{
    $employee_ids = array_values(array_unique(array_filter(array_map('intval', (array) $employee_ids), function ($id) {
        return $id > 0;
    })));
    if (empty($employee_ids)) {
        return;
    }

    convenienceEnsurePushNotificationController();

    $portalFile = dirname(__DIR__, 2) . '/controllers/portal_notification_controller.php';
    if (is_readable($portalFile)) {
        require_once $portalFile;
    }

    foreach ($employee_ids as $employee_id) {
        $pushSent = false;
        if (function_exists('pnc_publishNotification')) {
            $publish = pnc_publishNotification($conn, array(
                'target' => 'employee',
                'employee_id' => $employee_id,
                'title' => (string) $title,
                'body' => (string) $body,
                'payload' => is_array($payload) ? $payload : array(),
                'send_push' => true,
                'save_portal' => true,
            ));
            $pushSent = is_array($publish) && empty($publish['error']) && empty($publish['push_failed']);
        }

        if (!$pushSent && function_exists('pnc_sendPushToEmployee')) {
            pnc_sendPushToEmployee($conn, $employee_id, $title, $body, is_array($payload) ? $payload : array());
        }
    }
}

function convenienceNotifySupervisorPending($conn, $convenience_id)
{
    $record = getConvenienceRecordById($conn, (int) $convenience_id);
    if (!$record || empty($record['EmployeeID'])) {
        return;
    }
    if (!convenienceStatusMatchesPendingSupervisor($record['Status'] ?? '')) {
        return;
    }

    $employee_id = (int) $record['EmployeeID'];
    $supervisor_ids = getConvenienceSupervisorIdsForEmployee($conn, $employee_id);
    if (empty($supervisor_ids)) {
        return;
    }

    $employee = _getTableDetails($conn, 'employees', ' WHERE ID = ' . $employee_id);
    $employee_name = trim((string) ($employee['Name'] ?? 'Employee'));
    $amount = trim((string) ($record['ConvenienceAmount'] ?? ''));
    $date_label = function_exists('pnc_formatDisplayDate')
        ? pnc_formatDisplayDate($record['ConvenienceDate'] ?? '')
        : trim((string) ($record['ConvenienceDate'] ?? ''));

    conveniencePublishNotificationToEmployees($conn, $supervisor_ids, 'Convenience Request', "$employee_name submitted a convenience claim of Rs. $amount for $date_label. Please review.", array(
        'module' => 'convenience',
        'convenience_id' => (int) $convenience_id,
        'layer' => 'supervisor_pending',
        'employee_id' => $employee_id,
        'screen' => 'dashboard',
    ));
}

function convenienceNotifyHrPending($conn, $convenience_id)
{
    $record = getConvenienceRecordById($conn, (int) $convenience_id);
    if (!$record || empty($record['EmployeeID'])) {
        return;
    }
    if (!convenienceStatusMatchesSupervisorApproved($record['Status'] ?? '')) {
        return;
    }

    $employee = _getTableDetails($conn, 'employees', ' WHERE ID = ' . (int) $record['EmployeeID']);
    $employee_name = trim((string) ($employee['Name'] ?? 'Employee'));
    $amount = trim((string) ($record['ConvenienceAmount'] ?? ''));
    $date_label = function_exists('pnc_formatDisplayDate')
        ? pnc_formatDisplayDate($record['ConvenienceDate'] ?? '')
        : trim((string) ($record['ConvenienceDate'] ?? ''));

    $hr_ids = getHrEmployeeIdsForNotification($conn);
    if (empty($hr_ids)) {
        return;
    }

    conveniencePublishNotificationToEmployees($conn, $hr_ids, 'Convenience HR Approval', "Convenience claim from $employee_name (Rs. $amount, $date_label) is approved by supervisor and pending HR final approval.", array(
        'module' => 'convenience',
        'convenience_id' => (int) $convenience_id,
        'layer' => 'hr_pending',
        'employee_id' => (int) $record['EmployeeID'],
        'screen' => 'dashboard',
    ));
}

function convenienceNotifyFinancePending($conn, $convenience_id)
{
    $record = getConvenienceRecordById($conn, (int) $convenience_id);
    if (!$record || empty($record['EmployeeID'])) {
        return;
    }
    $status = (string) ($record['Status'] ?? '');
    if ($status !== convenienceStatusHrApproved()) {
        return;
    }

    $employee = _getTableDetails($conn, 'employees', ' WHERE ID = ' . (int) $record['EmployeeID']);
    $employee_name = trim((string) ($employee['Name'] ?? 'Employee'));
    $amount = trim((string) ($record['ApprovedAmount'] ?? ''));
    if ($amount === '') {
        $amount = trim((string) ($record['ConvenienceAmount'] ?? ''));
    }
    $date_label = function_exists('pnc_formatDisplayDate')
        ? pnc_formatDisplayDate($record['ConvenienceDate'] ?? '')
        : trim((string) ($record['ConvenienceDate'] ?? ''));

    $finance_ids = getFinanceEmployeeIdsForNotification($conn);
    if (empty($finance_ids)) {
        return;
    }

    conveniencePublishNotificationToEmployees($conn, $finance_ids, 'Convenience Payment Pending', "Convenience reimbursement for $employee_name (Rs. $amount, $date_label) is HR approved and pending finance payment.", array(
        'module' => 'convenience',
        'convenience_id' => (int) $convenience_id,
        'layer' => 'finance_pending',
        'employee_id' => (int) $record['EmployeeID'],
        'screen' => 'dashboard',
    ));
}

function supervisorApproveEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $remarks = '')
{
    $convenience_id = (int) $convenience_id;
    $record = getConvenienceRecordById($conn, $convenience_id);
    if (!$record) {
        return array('success' => false, 'result' => array('error' => true, 'message' => 'Record not found.'));
    }
    if (!convenienceIsWithinSupervisorActionWindow($record)) {
        return array('success' => false, 'result' => array('error' => true, 'message' => 'Supervisor action window of 24 hours has expired. HR will process this request.'));
    }
    $approver_employee_id = (int) $approver_employee_id;
    $approved_at = date('Y-m-d H:i:s');
    $remarks_sql = appendConvenienceRemarks($conn, $record['ApproverRemarks'] ?? '', $remarks);

    $set_parts = array(
        "Status = '" . convenienceStatusSupervisorApproved() . "'",
        "ApproverRemarks = '$remarks_sql'",
    );
    if (convenienceColumnExists($conn, 'SupervisorApprovedBy')) {
        $set_parts[] = "SupervisorApprovedBy = $approver_employee_id";
    }
    if (convenienceColumnExists($conn, 'SupervisorApprovedAt')) {
        $set_parts[] = "SupervisorApprovedAt = '$approved_at'";
    }

    $update_param = implode(', ', $set_parts) . " WHERE ID = $convenience_id AND " . conveniencePendingSupervisorStatusSql();
    $result = _UpdateTableRecords($conn, 'employee_convenience', $update_param);
    $success = convenienceUpdateSucceeded($conn, $result);
    if ($success) {
        convenienceNotifyDecisionSafe($conn, $convenience_id, 'approved', 'supervisor');
        convenienceNotifyHrPending($conn, $convenience_id);
    }
    return array('success' => $success, 'result' => $result);
}

function supervisorRejectEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $reason = '')
{
    $convenience_id = (int) $convenience_id;
    $record = getConvenienceRecordById($conn, $convenience_id);
    if (!$record) {
        return array('success' => false, 'result' => array('error' => true, 'message' => 'Record not found.'));
    }
    if (!convenienceIsWithinSupervisorActionWindow($record)) {
        return array('success' => false, 'result' => array('error' => true, 'message' => 'Supervisor action window of 24 hours has expired. HR will process this request.'));
    }
    $reason = trim((string) $reason);
    $remarks_sql = appendConvenienceRemarks($conn, $record['ApproverRemarks'] ?? '', $reason !== '' ? 'Supervisor Rejected: ' . $reason : 'Supervisor Rejected');

    $update_param = "Status = '" . convenienceStatusRejectedSupervisor() . "', ApproverRemarks = '$remarks_sql' WHERE ID = $convenience_id AND " . conveniencePendingSupervisorStatusSql();
    $result = _UpdateTableRecords($conn, 'employee_convenience', $update_param);
    $success = convenienceUpdateSucceeded($conn, $result);
    if ($success) {
        convenienceNotifyDecisionSafe($conn, $convenience_id, 'rejected', 'supervisor');
    }
    return array('success' => $success, 'result' => $result);
}

function hrFinalApproveEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $remarks = '', $approved_amount = '')
{
    $convenience_id = (int) $convenience_id;
    $record = getConvenienceRecordById($conn, $convenience_id);
    $expired_supervisor_pending = is_array($record) && convenienceIsSupervisorActionExpired($record);
    if (!$record || (!convenienceStatusMatchesSupervisorApproved($record['Status'] ?? '') && !$expired_supervisor_pending)) {
        return array('success' => false, 'result' => array('error' => true, 'message' => 'Convenience is not pending HR approval.'));
    }

    $approved_amount = trim((string) $approved_amount);
    if ($approved_amount === '') {
        $approved_amount = trim((string) ($record['ConvenienceAmount'] ?? ''));
    }
    $approved_amount_sql = mysqli_real_escape_string($conn, $approved_amount);
    $approver_employee_id = (int) $approver_employee_id;
    $approved_at = date('Y-m-d H:i:s');
    if ($expired_supervisor_pending) {
        $remark_text = $remarks !== '' ? 'HR Approved (Supervisor timeout): ' . $remarks : 'HR Approved (Supervisor timeout)';
    } else {
        $remark_text = $remarks !== '' ? 'HR Approved: ' . $remarks : 'HR Approved';
    }
    $remarks_sql = appendConvenienceRemarks($conn, $record['ApproverRemarks'] ?? '', $remark_text);

    $set_parts = array(
        "Status = '" . convenienceStatusHrApproved() . "'",
        "ConvenienceAmount = '$approved_amount_sql'",
        "ApproverRemarks = '$remarks_sql'",
    );
    if (convenienceColumnExists($conn, 'ApprovedAmount')) {
        $set_parts[] = "ApprovedAmount = '$approved_amount_sql'";
    }
    if (convenienceColumnExists($conn, 'HrApprovedBy')) {
        $set_parts[] = "HrApprovedBy = $approver_employee_id";
    }
    if (convenienceColumnExists($conn, 'HrApprovedAt')) {
        $set_parts[] = "HrApprovedAt = '$approved_at'";
    }

    $status_where = $expired_supervisor_pending
        ? conveniencePendingSupervisorStatusSql()
        : convenienceSupervisorApprovedStatusSql();
    $update_param = implode(', ', $set_parts) . " WHERE ID = $convenience_id AND " . $status_where;
    $result = _UpdateTableRecords($conn, 'employee_convenience', $update_param);
    $success = convenienceUpdateSucceeded($conn, $result);
    if ($success) {
        convenienceNotifyDecisionSafe($conn, $convenience_id, 'approved', 'hr', $approved_amount);
        convenienceNotifyFinancePending($conn, $convenience_id);
    }
    return array('success' => $success, 'result' => $result);
}

function canFinanceMarkConveniencePaid($conn, $convenience_id, $roles)
{
    if (!hasFinanceConveniencePaymentAccess($roles)) {
        return false;
    }
    $record = getConvenienceRecordById($conn, $convenience_id);
    if (!$record) {
        return false;
    }
    return (string) ($record['Status'] ?? '') === convenienceStatusHrApproved();
}

function financeMarkConveniencePaid($conn, $convenience_id, $finance_employee_id, $payment_reference = '', $payment_mode = '', $payment_remarks = '')
{
    $convenience_id = (int) $convenience_id;
    $record = getConvenienceRecordById($conn, $convenience_id);
    if (!$record || (string) ($record['Status'] ?? '') !== convenienceStatusHrApproved()) {
        return array('success' => false, 'result' => array('error' => true, 'message' => 'Convenience is not pending finance payment.'));
    }

    $finance_employee_id = (int) $finance_employee_id;
    $paid_at = date('Y-m-d H:i:s');
    $payment_reference = mysqli_real_escape_string($conn, trim((string) $payment_reference));
    $payment_mode = mysqli_real_escape_string($conn, trim((string) $payment_mode));
    $remark_text = trim((string) $payment_remarks);
    if ($remark_text !== '') {
        $remark_text = 'Finance Payment: ' . $remark_text;
    } else {
        $remark_text = 'Finance Payment Done';
    }
    $remarks_sql = appendConvenienceRemarks($conn, $record['ApproverRemarks'] ?? '', $remark_text);

    $set_parts = array(
        "Status = '" . convenienceStatusPaid() . "'",
        "ApproverRemarks = '$remarks_sql'",
    );
    if (convenienceColumnExists($conn, 'FinancePaidBy')) {
        $set_parts[] = "FinancePaidBy = $finance_employee_id";
    }
    if (convenienceColumnExists($conn, 'FinancePaidAt')) {
        $set_parts[] = "FinancePaidAt = '$paid_at'";
    }
    if (convenienceColumnExists($conn, 'PaymentReference') && $payment_reference !== '') {
        $set_parts[] = "PaymentReference = '$payment_reference'";
    }
    if (convenienceColumnExists($conn, 'PaymentMode') && $payment_mode !== '') {
        $set_parts[] = "PaymentMode = '$payment_mode'";
    }
    if (convenienceColumnExists($conn, 'PaymentRemarks') && trim((string) $payment_remarks) !== '') {
        $set_parts[] = "PaymentRemarks = '" . mysqli_real_escape_string($conn, trim((string) $payment_remarks)) . "'";
    }

    $status_where = "Status IN ('" . convenienceStatusHrApproved() . "', " . convenienceStatusHrApproved() . ")";
    $update_param = implode(', ', $set_parts) . " WHERE ID = $convenience_id AND $status_where";
    $result = _UpdateTableRecords($conn, 'employee_convenience', $update_param);
    $success = convenienceUpdateSucceeded($conn, $result);
    if ($success) {
        $paid_amount = trim((string) ($record['ApprovedAmount'] ?? ''));
        if ($paid_amount === '') {
            $paid_amount = trim((string) ($record['ConvenienceAmount'] ?? ''));
        }
        convenienceNotifyDecisionSafe($conn, $convenience_id, 'paid', 'finance', $paid_amount);
    }
    return array('success' => $success, 'result' => $result);
}

function financeBulkMarkConveniencePaid($conn, $convenience_ids, $finance_employee_id, $roles, $payment_reference = '', $payment_mode = '', $payment_remarks = '')
{
    $finance_employee_id = (int) $finance_employee_id;
    return convenienceBulkProcessConvenienceIds($conn, $convenience_ids, function ($conn, $convenience_id) use ($finance_employee_id, $roles, $payment_reference, $payment_mode, $payment_remarks) {
        if (!canFinanceMarkConveniencePaid($conn, $convenience_id, $roles)) {
            return array('success' => false);
        }
        return financeMarkConveniencePaid($conn, $convenience_id, $finance_employee_id, $payment_reference, $payment_mode, $payment_remarks);
    });
}

function canFinanceRejectConveniencePayment($conn, $convenience_id, $roles)
{
    return canFinanceMarkConveniencePaid($conn, $convenience_id, $roles);
}

function financeRejectConveniencePayment($conn, $convenience_id, $finance_employee_id, $reason = '')
{
    $convenience_id = (int) $convenience_id;
    $record = getConvenienceRecordById($conn, $convenience_id);
    if (!$record || (string) ($record['Status'] ?? '') !== convenienceStatusHrApproved()) {
        return array('success' => false, 'result' => array('error' => true, 'message' => 'Convenience is not pending finance payment.'));
    }

    $reason = trim((string) $reason);
    $reject_prefix = 'Finance Rejected';
    $remarks_sql = appendConvenienceRemarks($conn, $record['ApproverRemarks'] ?? '', $reason !== '' ? $reject_prefix . ': ' . $reason : $reject_prefix);

    $status_where = "Status IN ('" . convenienceStatusHrApproved() . "', " . convenienceStatusHrApproved() . ")";
    $update_param = "Status = '" . convenienceStatusRejectedFinance() . "', ApproverRemarks = '$remarks_sql' WHERE ID = $convenience_id AND $status_where";
    $result = _UpdateTableRecords($conn, 'employee_convenience', $update_param);
    $success = convenienceUpdateSucceeded($conn, $result);
    if ($success) {
        convenienceNotifyDecisionSafe($conn, $convenience_id, 'rejected', 'finance');
    }
    return array('success' => $success, 'result' => $result);
}

function financeBulkRejectConveniencePayment($conn, $convenience_ids, $finance_employee_id, $roles, $reason = '')
{
    $finance_employee_id = (int) $finance_employee_id;
    return convenienceBulkProcessConvenienceIds($conn, $convenience_ids, function ($conn, $convenience_id) use ($finance_employee_id, $roles, $reason) {
        if (!canFinanceRejectConveniencePayment($conn, $convenience_id, $roles)) {
            return array('success' => false);
        }
        return financeRejectConveniencePayment($conn, $convenience_id, $finance_employee_id, $reason);
    });
}

function hrRejectEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $reason = '')
{
    $convenience_id = (int) $convenience_id;
    $record = getConvenienceRecordById($conn, $convenience_id);
    if (!$record) {
        return array('success' => false, 'result' => array('error' => true, 'message' => 'Record not found.'));
    }

    $expired_supervisor_pending = convenienceIsSupervisorActionExpired($record);
    if (!convenienceStatusMatchesSupervisorApproved($record['Status'] ?? '') && !$expired_supervisor_pending) {
        return array('success' => false, 'result' => array('error' => true, 'message' => 'Convenience is not pending HR approval.'));
    }

    $reason = trim((string) $reason);
    $reject_prefix = $expired_supervisor_pending ? 'HR Rejected (Supervisor timeout)' : 'HR Rejected';
    $remarks_sql = appendConvenienceRemarks($conn, $record['ApproverRemarks'] ?? '', $reason !== '' ? $reject_prefix . ': ' . $reason : $reject_prefix);

    $status_where = $expired_supervisor_pending
        ? conveniencePendingSupervisorStatusSql()
        : convenienceSupervisorApprovedStatusSql();
    $update_param = "Status = '" . convenienceStatusRejectedHr() . "', ApproverRemarks = '$remarks_sql' WHERE ID = $convenience_id AND " . $status_where;
    $result = _UpdateTableRecords($conn, 'employee_convenience', $update_param);
    $success = convenienceUpdateSucceeded($conn, $result);
    if ($success) {
        convenienceNotifyDecisionSafe($conn, $convenience_id, 'rejected', 'hr');
    }
    return array('success' => $success, 'result' => $result);
}

function convenienceParseMultiFilterValues($value)
{
    if (is_array($value)) {
        $items = $value;
    } else {
        $value = trim((string) $value);
        if ($value === '' || $value === '-1') {
            return array();
        }
        $items = preg_split('/\s*,\s*/', $value);
    }

    $result = array();
    foreach ($items as $item) {
        $item = trim((string) $item);
        if ($item !== '' && $item !== '-1') {
            $result[] = $item;
        }
    }

    return array_values(array_unique($result));
}

function convenienceAppendSqlInClause($conn, $column, $values, $numeric = false)
{
    if (empty($values)) {
        return '';
    }

    if ($numeric) {
        $ids = array();
        foreach ($values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return '';
        }
        return ' AND ' . $column . ' IN (' . implode(',', $ids) . ')';
    }

    $escaped = array();
    foreach ($values as $value) {
        $escaped[] = "'" . mysqli_real_escape_string($conn, (string) $value) . "'";
    }
    if (empty($escaped)) {
        return '';
    }

    return ' AND ' . $column . ' IN (' . implode(',', $escaped) . ')';
}

function convenienceBulkProcessConvenienceIds($conn, $convenience_ids, $processor)
{
    $summary = array(
        'success' => 0,
        'failed' => 0,
    );

    if (!is_array($convenience_ids)) {
        return $summary;
    }

    foreach ($convenience_ids as $convenience_id) {
        $convenience_id = (int) $convenience_id;
        if ($convenience_id <= 0) {
            continue;
        }

        $result = call_user_func($processor, $conn, $convenience_id);
        if (!empty($result['success'])) {
            $summary['success']++;
        } else {
            $summary['failed']++;
        }
    }

    return $summary;
}

function supervisorBulkApproveEmployeeConvenience($conn, $convenience_ids, $approver_employee_id, $remarks = '')
{
    $approver_employee_id = (int) $approver_employee_id;
    return convenienceBulkProcessConvenienceIds($conn, $convenience_ids, function ($conn, $convenience_id) use ($approver_employee_id, $remarks) {
        if (!canSupervisorApproveConvenience($conn, $convenience_id, $approver_employee_id)) {
            return array('success' => false);
        }
        return supervisorApproveEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $remarks);
    });
}

function supervisorBulkRejectEmployeeConvenience($conn, $convenience_ids, $approver_employee_id, $reason = '')
{
    $approver_employee_id = (int) $approver_employee_id;
    return convenienceBulkProcessConvenienceIds($conn, $convenience_ids, function ($conn, $convenience_id) use ($approver_employee_id, $reason) {
        if (!canSupervisorApproveConvenience($conn, $convenience_id, $approver_employee_id)) {
            return array('success' => false);
        }
        return supervisorRejectEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $reason);
    });
}

function hrBulkApproveEmployeeConvenience($conn, $convenience_ids, $approver_employee_id, $roles, $remarks = '', $approved_amount = '')
{
    $approver_employee_id = (int) $approver_employee_id;
    return convenienceBulkProcessConvenienceIds($conn, $convenience_ids, function ($conn, $convenience_id) use ($approver_employee_id, $roles, $remarks, $approved_amount) {
        if (!canHrApproveConvenience($conn, $convenience_id, $roles)) {
            return array('success' => false);
        }
        return hrFinalApproveEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $remarks, $approved_amount);
    });
}

function hrBulkRejectEmployeeConvenience($conn, $convenience_ids, $approver_employee_id, $roles, $reason = '')
{
    $approver_employee_id = (int) $approver_employee_id;
    return convenienceBulkProcessConvenienceIds($conn, $convenience_ids, function ($conn, $convenience_id) use ($approver_employee_id, $roles, $reason) {
        if (!canHrApproveConvenience($conn, $convenience_id, $roles)) {
            return array('success' => false);
        }
        return hrRejectEmployeeConvenience($conn, $convenience_id, $approver_employee_id, $reason);
    });
}

function buildConvenienceBulkActionMessage($summary, $action_label)
{
    $success = (int) ($summary['success'] ?? 0);
    $failed = (int) ($summary['failed'] ?? 0);
    if ($success <= 0 && $failed <= 0) {
        return 'No records were updated.';
    }
    if ($failed <= 0) {
        return $success . ' record(s) ' . $action_label . ' successfully.';
    }
    if ($success <= 0) {
        return 'Unable to ' . $action_label . ' selected record(s).';
    }
    return $success . ' record(s) ' . $action_label . '. ' . $failed . ' record(s) could not be updated.';
}

function buildConvenienceListJoinSql()
{
    $ticket_join = " LEFT JOIN corporate_tickets ct ON ct.ID = a.TicketID AND a.TicketID > 0 ";
    return " FROM employee_convenience a
             INNER JOIN employees e ON e.ID = a.EmployeeID
             $ticket_join ";
}

function buildConvenienceListFilterSql($conn, $filters = array(), $options = array())
{
    $scope = isset($options['scope']) ? (string) $options['scope'] : 'admin';
    $supervisor_employee_id = isset($options['supervisor_employee_id']) ? (int) $options['supervisor_employee_id'] : -1;

    $where = ' WHERE 1 ';

    if ($scope === 'supervisor') {
        $supervised_ids = getConvenienceTeamEmployeeIds($conn, $supervisor_employee_id, false);
        if (empty($supervised_ids)) {
            return ' WHERE 1=0 ';
        }
        $where .= ' AND a.EmployeeID IN (' . implode(',', $supervised_ids) . ')';
    }

    $filter_date = isset($filters['filter_date']) ? trim((string) $filters['filter_date']) : '';
    if ($filter_date !== '' && strtolower($filter_date) !== 'all' && strpos($filter_date, ' - ') !== false) {
        $date_parts = explode(' - ', $filter_date, 2);
        if (count($date_parts) === 2) {
            $start_date = mysqli_real_escape_string($conn, trim($date_parts[0]));
            $end_date = mysqli_real_escape_string($conn, trim($date_parts[1]));
            $where .= " AND (a.ConvenienceDate >= '$start_date' AND a.ConvenienceDate <= '$end_date')";
        }
    }

    $employee_ids = convenienceParseMultiFilterValues(isset($filters['employee_id']) ? $filters['employee_id'] : '');
    $where .= convenienceAppendSqlInClause($conn, 'a.EmployeeID', $employee_ids, true);

    if (!empty($filters['employee_number'])) {
        $employee_number = mysqli_real_escape_string($conn, trim((string) $filters['employee_number']));
        $where .= " AND (e.EmployeeNumber LIKE '%$employee_number%' OR CAST(e.ID AS CHAR) LIKE '%$employee_number%')";
    }

    $where .= convenienceBuildStatusFilterSql($conn, $filters);

    $state_values = convenienceParseMultiFilterValues(isset($filters['state']) ? $filters['state'] : '');
    $where .= convenienceAppendSqlInClause($conn, 'e.State', $state_values, false);

    $department_values = convenienceParseMultiFilterValues(isset($filters['department']) ? $filters['department'] : '');
    $where .= convenienceAppendSqlInClause($conn, 'e.Department', $department_values, false);

    $designation_values = convenienceParseMultiFilterValues(isset($filters['designation']) ? $filters['designation'] : '');
    $where .= convenienceAppendSqlInClause($conn, 'e.Designation', $designation_values, false);

    if (!empty($filters['ticket_id']) && (int) $filters['ticket_id'] > 0) {
        $where .= ' AND a.TicketID = ' . (int) $filters['ticket_id'];
    }

    if (!empty($filters['search'])) {
        $search = mysqli_real_escape_string($conn, trim((string) $filters['search']));
        $where .= " AND (e.Name LIKE '%$search%' OR a.ConvenienceFrom LIKE '%$search%' OR a.ConvenienceTo LIKE '%$search%' OR a.Reference LIKE '%$search%' OR ct.TicketID LIKE '%$search%')";
    }

    return $where;
}

function getConvenienceFilterOptions($conn)
{
    $options = array(
        'states' => array(),
        'departments' => array(),
        'designations' => array(),
    );
    $state_rows = _getSQLRecords($conn, "SELECT DISTINCT State FROM employees WHERE IsActive = 1 AND Vendor = 0 AND State <> '' ORDER BY State ASC");
    foreach ($state_rows as $row) {
        $options['states'][] = $row['State'];
    }
    $department_rows = _getSQLRecords($conn, "SELECT DISTINCT Department FROM employees WHERE IsActive = 1 AND Vendor = 0 AND Department <> '' ORDER BY Department ASC");
    foreach ($department_rows as $row) {
        $options['departments'][] = $row['Department'];
    }
    $designation_rows = _getSQLRecords($conn, "SELECT DISTINCT Designation FROM employees WHERE IsActive = 1 AND Vendor = 0 AND Designation <> '' ORDER BY Designation ASC");
    foreach ($designation_rows as $row) {
        $options['designations'][] = $row['Designation'];
    }
    return $options;
}

function sendConvenienceDatatableJson($response)
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    $flags = 0;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags = JSON_INVALID_UTF8_SUBSTITUTE;
    }
    $json = json_encode($response, $flags);
    if ($json === false) {
        $json = json_encode(array(
            'draw' => isset($response['draw']) ? (int) $response['draw'] : 0,
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'aaData' => array(),
        ));
    }
    echo $json;
    exit;
}

function convenienceLogDatatableSqlError($context, $conn, $sql = '')
{
    if (!$conn) {
        error_log($context . ': database connection unavailable');
        return;
    }
    $message = $context . ': ' . mysqli_error($conn);
    if ($sql !== '') {
        $message .= ' | SQL: ' . $sql;
    }
    error_log($message);
}

function buildConvenienceApprovalInfoHtml($record)
{
    $lines = array();
    if (convenienceStatusMatchesPendingSupervisor($record['Status'] ?? '')) {
        $deadline_label = convenienceGetSupervisorActionDeadlineLabel($record);
        if ($deadline_label !== '') {
            if (convenienceIsWithinSupervisorActionWindow($record)) {
                $lines[] = '<strong>Supervisor deadline:</strong> <small>' . htmlspecialchars($deadline_label) . '</small>';
            } else {
                $lines[] = '<strong>Supervisor window:</strong> <small class="text-danger">Expired - HR only</small>';
            }
        }
    }
    if (!empty($record['SupervisorApproverName']) && !empty($record['SupervisorApprovedAt'])) {
        $lines[] = '<strong>Supervisor:</strong> ' . htmlspecialchars($record['SupervisorApproverName']) . '<br><small>' . htmlspecialchars($record['SupervisorApprovedAt']) . '</small>';
    }
    if (!empty($record['HrApproverName']) && !empty($record['HrApprovedAt'])) {
        $lines[] = '<strong>HR:</strong> ' . htmlspecialchars($record['HrApproverName']) . '<br><small>' . htmlspecialchars($record['HrApprovedAt']) . '</small>';
    }
    if (!empty($record['FinancePayerName']) && !empty($record['FinancePaidAt'])) {
        $lines[] = '<strong>Finance:</strong> ' . htmlspecialchars($record['FinancePayerName']) . '<br><small>' . htmlspecialchars($record['FinancePaidAt']) . '</small>';
    }
    if (!empty($record['PaymentReference'])) {
        $lines[] = '<strong>Payment Ref:</strong> <small>' . htmlspecialchars($record['PaymentReference']) . '</small>';
    }
    if (!empty($record['PaymentMode'])) {
        $lines[] = '<strong>Payment Mode:</strong> <small>' . htmlspecialchars($record['PaymentMode']) . '</small>';
    }
    if (!empty($record['ApproverRemarks'])) {
        $remarks = (string) $record['ApproverRemarks'];
        if (function_exists('mb_convert_encoding')) {
            $remarks = mb_convert_encoding($remarks, 'UTF-8', 'UTF-8');
        }
        $lines[] = '<small>' . $remarks . '</small>';
    }
    if (empty($lines)) {
        return '-';
    }
    return implode('<br>', $lines);
}

function getConvenienceApprovalSqlParts($conn)
{
    $parts = array(
        'supervisor_select' => '',
        'hr_select' => '',
        'finance_select' => '',
        'supervisor_join' => '',
        'hr_join' => '',
        'finance_join' => '',
    );
    if (convenienceColumnExists($conn, 'SupervisorApprovedBy')) {
        $parts['supervisor_select'] = ', sup.Name AS SupervisorApproverName, a.SupervisorApprovedAt';
        $parts['supervisor_join'] = ' LEFT JOIN employees sup ON sup.ID = a.SupervisorApprovedBy ';
    }
    if (convenienceColumnExists($conn, 'HrApprovedBy')) {
        $parts['hr_select'] = ', hr.Name AS HrApproverName, a.HrApprovedAt';
        $parts['hr_join'] = ' LEFT JOIN employees hr ON hr.ID = a.HrApprovedBy ';
    }
    if (convenienceColumnExists($conn, 'FinancePaidBy')) {
        $parts['finance_select'] = ', fin.Name AS FinancePayerName, a.FinancePaidAt, a.PaymentReference, a.PaymentMode, a.PaymentRemarks';
        $parts['finance_join'] = ' LEFT JOIN employees fin ON fin.ID = a.FinancePaidBy ';
    }
    return $parts;
}

function convenienceParseAmountValue($record)
{
    if (!is_array($record)) {
        return 0.0;
    }
    $amount = trim((string) ($record['ApprovedAmount'] ?? ''));
    if ($amount === '' || $amount === '0') {
        $amount = trim((string) ($record['ConvenienceAmount'] ?? ''));
    }
    return convenienceParseAmountString($amount);
}

function convenienceParseAmountString($amount)
{
    $amount = trim((string) $amount);
    if ($amount === '' || $amount === '-' || strtolower($amount) === 'null') {
        return 0.0;
    }

    $amount = str_replace(array(',', ' '), '', $amount);
    $amount = preg_replace('/[^0-9.]/', '', $amount);
    if ($amount === '' || $amount === '.') {
        return 0.0;
    }

    if (substr_count($amount, '.') > 1) {
        $parts = explode('.', $amount);
        $amount = array_shift($parts) . '.' . implode('', $parts);
    }

    if (!preg_match('/^(\d+)(?:\.(\d+))?$/', $amount, $matches)) {
        return 0.0;
    }

    $whole = $matches[1];
    $fraction = isset($matches[2]) ? substr($matches[2], 0, 2) : '';
    if (strlen($whole) > 8) {
        return 0.0;
    }

    $normalized = $whole . ($fraction !== '' ? '.' . $fraction : '');
    $value = (float) $normalized;
    if (!is_finite($value) || $value < 0) {
        return 0.0;
    }

    $max_per_claim = 1000000.0;
    if ($value > $max_per_claim) {
        return 0.0;
    }

    return round($value, 2);
}

function convenienceAddAmountValues($left, $right)
{
    $left = convenienceClampAmountScalar($left);
    $right = convenienceClampAmountScalar($right);
    if (function_exists('bcadd')) {
        return (float) bcadd((string) $left, (string) $right, 2);
    }
    return round($left + $right, 2);
}

function convenienceClampAmountScalar($amount)
{
    $amount = round((float) $amount, 2);
    if (!is_finite($amount) || $amount < 0) {
        return 0.0;
    }
    $max_total = 1000000000.0;
    if ($amount > $max_total) {
        return $max_total;
    }
    return $amount;
}

function convenienceFormatAmountLabel($amount)
{
    $amount = convenienceClampAmountScalar($amount);
    return 'Rs. ' . number_format($amount, 2);
}

function convenienceEmptyDashboardSummary()
{
    return array(
        'total_count' => 0,
        'total_amount' => 0,
        'pending_supervisor_count' => 0,
        'pending_supervisor_amount' => 0,
        'supervisor_timeout_count' => 0,
        'supervisor_timeout_amount' => 0,
        'pending_hr_count' => 0,
        'pending_hr_amount' => 0,
        'pending_payment_count' => 0,
        'pending_payment_amount' => 0,
        'payment_done_count' => 0,
        'payment_done_amount' => 0,
        'rejected_count' => 0,
        'rejected_amount' => 0,
    );
}

function getConvenienceDashboardSummary($conn, $filters = array(), $options = array())
{
    $summary = convenienceEmptyDashboardSummary();
    if (!$conn) {
        return $summary;
    }

    $dashboard_filters = $filters;
    unset($dashboard_filters['status'], $dashboard_filters['search']);

    $where = buildConvenienceListFilterSql($conn, $dashboard_filters, $options);
    if (strpos($where, '1=0') !== false) {
        return $summary;
    }

    $join = buildConvenienceListJoinSql();
    $sql = 'SELECT a.Status, a.ConvenienceAmount, a.ApprovedAmount, a.CreatedDate, a.CreatedTime, a.ConvenienceDate '
        . $join . $where;

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log('Convenience dashboard summary query failed: ' . mysqli_error($conn));
        return $summary;
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $amount = convenienceParseAmountValue($row);
        $status = trim((string) ($row['Status'] ?? ''));

        $summary['total_count']++;
        $summary['total_amount'] = convenienceAddAmountValues($summary['total_amount'], $amount);

        if (convenienceStatusMatchesPendingSupervisor($status)) {
            if (convenienceIsSupervisorActionExpired($row)) {
                $summary['supervisor_timeout_count']++;
                $summary['supervisor_timeout_amount'] = convenienceAddAmountValues($summary['supervisor_timeout_amount'], $amount);
                $summary['pending_hr_count']++;
                $summary['pending_hr_amount'] = convenienceAddAmountValues($summary['pending_hr_amount'], $amount);
            } else {
                $summary['pending_supervisor_count']++;
                $summary['pending_supervisor_amount'] = convenienceAddAmountValues($summary['pending_supervisor_amount'], $amount);
            }
            continue;
        }

        if (convenienceStatusMatchesSupervisorApproved($status)) {
            $summary['pending_hr_count']++;
            $summary['pending_hr_amount'] = convenienceAddAmountValues($summary['pending_hr_amount'], $amount);
            continue;
        }

        if ($status === convenienceStatusHrApproved()) {
            $summary['pending_payment_count']++;
            $summary['pending_payment_amount'] = convenienceAddAmountValues($summary['pending_payment_amount'], $amount);
            continue;
        }

        if ($status === convenienceStatusPaid()) {
            $summary['payment_done_count']++;
            $summary['payment_done_amount'] = convenienceAddAmountValues($summary['payment_done_amount'], $amount);
            continue;
        }

        if ((int) $status < 0) {
            $summary['rejected_count']++;
            $summary['rejected_amount'] = convenienceAddAmountValues($summary['rejected_amount'], $amount);
        }
    }

    foreach ($summary as $key => $value) {
        if (strpos($key, '_amount') !== false) {
            $summary[$key] = convenienceClampAmountScalar($value);
        }
    }

    return $summary;
}

function buildConvenienceDashboardCardsHtml($scope = 'admin')
{
    $scope = (string) $scope;
    ob_start();
    ?>
    <div class="panel mb-2" id="convenience_dashboard_panel">
        <div class="panel-hdr">
            <h2>Convenience Summary <small class="text-muted font-weight-normal">(based on current filters)</small></h2>
            <div class="panel-toolbar">
                <span class="badge badge-light" id="convenience_dashboard_loading">Updating...</span>
            </div>
        </div>
        <div class="panel-container show">
            <div class="panel-content p-3">
                <div class="row convenience-dashboard-cards" data-scope="<?php echo htmlspecialchars($scope); ?>">
                    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3 card-total">
                        <div class="conv-stat-card conv-stat-total">
                            <div class="conv-stat-label">Total Requests</div>
                            <div class="conv-stat-value" id="conv_dash_total_count">0</div>
                            <div class="conv-stat-amount" id="conv_dash_total_amount">Rs. 0.00</div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3 card-pending-supervisor">
                        <div class="conv-stat-card conv-stat-warning">
                            <div class="conv-stat-label">Pending Supervisor</div>
                            <div class="conv-stat-value" id="conv_dash_pending_supervisor_count">0</div>
                            <div class="conv-stat-amount" id="conv_dash_pending_supervisor_amount">Rs. 0.00</div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3 card-supervisor-timeout">
                        <div class="conv-stat-card conv-stat-danger-soft">
                            <div class="conv-stat-label">Supervisor Timeout</div>
                            <div class="conv-stat-value" id="conv_dash_supervisor_timeout_count">0</div>
                            <div class="conv-stat-amount" id="conv_dash_supervisor_timeout_amount">Rs. 0.00</div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3 card-pending-hr">
                        <div class="conv-stat-card conv-stat-info">
                            <div class="conv-stat-label">Pending HR</div>
                            <div class="conv-stat-value" id="conv_dash_pending_hr_count">0</div>
                            <div class="conv-stat-amount" id="conv_dash_pending_hr_amount">Rs. 0.00</div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3 card-pending-payment">
                        <div class="conv-stat-card conv-stat-orange">
                            <div class="conv-stat-label">Payment Pending</div>
                            <div class="conv-stat-value" id="conv_dash_pending_payment_count">0</div>
                            <div class="conv-stat-amount" id="conv_dash_pending_payment_amount">Rs. 0.00</div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3 card-payment-done">
                        <div class="conv-stat-card conv-stat-success">
                            <div class="conv-stat-label">Payment Done</div>
                            <div class="conv-stat-value" id="conv_dash_payment_done_count">0</div>
                            <div class="conv-stat-amount" id="conv_dash_payment_done_amount">Rs. 0.00</div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3 card-rejected">
                        <div class="conv-stat-card conv-stat-danger">
                            <div class="conv-stat-label">Rejected</div>
                            <div class="conv-stat-value" id="conv_dash_rejected_count">0</div>
                            <div class="conv-stat-amount" id="conv_dash_rejected_amount">Rs. 0.00</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function buildConvenienceDashboardStylesHtml()
{
    return '<style>
    .conv-stat-card {
        border-radius: 10px;
        padding: 16px 18px;
        color: #fff;
        min-height: 108px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        position: relative;
        overflow: hidden;
    }
    .conv-stat-card::after {
        content: "";
        position: absolute;
        right: -20px;
        top: -20px;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.12);
    }
    .conv-stat-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        opacity: 0.92;
        margin-bottom: 6px;
    }
    .conv-stat-value {
        font-size: 28px;
        font-weight: 700;
        line-height: 1.1;
    }
    .conv-stat-amount {
        font-size: 13px;
        margin-top: 6px;
        opacity: 0.95;
        font-weight: 500;
    }
    .conv-stat-total { background: linear-gradient(135deg, #003f88 0%, #005fa3 100%); }
    .conv-stat-warning { background: linear-gradient(135deg, #f6ad55 0%, #ed8936 100%); }
    .conv-stat-info { background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%); }
    .conv-stat-orange { background: linear-gradient(135deg, #f6ad55 0%, #dd6b20 100%); }
    .conv-stat-success { background: linear-gradient(135deg, #48bb78 0%, #2f855a 100%); }
    .conv-stat-danger { background: linear-gradient(135deg, #fc8181 0%, #e53e3e 100%); }
    .conv-stat-danger-soft { background: linear-gradient(135deg, #feb2b2 0%, #f56565 100%); color: #742a2a; }
    .conv-stat-danger-soft .conv-stat-label,
    .conv-stat-danger-soft .conv-stat-amount { opacity: 0.85; }
    </style>';
}
