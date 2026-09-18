<?php

require_once(__DIR__ . '/../../includes/autoloader.inc.php');

function elm_session_employee_id($session = null)
{
    if ($session === null) {
        $session = $_SESSION;
    }
    if (isset($session['Roles']['EmployeeID']) && (int) $session['Roles']['EmployeeID'] > 0) {
        return (int) $session['Roles']['EmployeeID'];
    }
    return 0;
}

function elm_session_username()
{
    return $_SESSION['pb_username'] ?? '';
}

function elm_require_employee_access()
{
    if (elm_session_employee_id() < 1) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }
}

function hasElmHrAccess($roles)
{
    if (isset($_SESSION['UserType'])) {
        $userType = (string) $_SESSION['UserType'];
        if (in_array($userType, ['Admin', 'Super Admin'], true)) {
            return true;
        }
    }
    if (!isset($roles['EmployeeRoles']) || !is_array($roles['EmployeeRoles'])) {
        return false;
    }
    foreach ($roles['EmployeeRoles'] as $role) {
        if (in_array($role, ['HR', 'Super Admin', 'Admin'], true)) {
            return true;
        }
    }
    return false;
}

function elm_require_hr_access()
{
    if (!hasElmHrAccess($_SESSION['Roles'] ?? [])) {
        header('Location: ../dashboard/admin_dashboard.php');
        exit;
    }
}

function elm_leave_status_badge($status)
{
    $status = (string) $status;
    $map = [
        'Pending' => 'badge-warning',
        'SupervisorApproved' => 'badge-info',
        'Approved' => 'badge-success',
        'Rejected' => 'badge-danger',
    ];
    $cls = $map[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($status) . '</span>';
}

function elm_format_leave_row(array $row)
{
    $days = isset($row['LeaveDays']) ? (float) $row['LeaveDays'] : 0;
    if ($days <= 0 && function_exists('computeEmployeeLeaveDays')) {
        $days = computeEmployeeLeaveDays($row);
    }
    $row['DisplayDays'] = $days;
    if (!empty($row['IsSandwichApplied']) && (float) ($row['SandwichExtraDays'] ?? 0) > 0) {
        $row['SandwichNote'] = '+' . (float) $row['SandwichExtraDays'] . ' sandwich day(s)';
    }
    return $row;
}
