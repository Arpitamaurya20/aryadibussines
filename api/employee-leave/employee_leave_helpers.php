<?php
/**
 * Shared helpers for employee leave mobile APIs (aligned with portal module).
 */

require_once dirname(__DIR__, 2) . '/admin/includes/autoloader.inc.php';

function elm_api_parse_input()
{
    $dataRaw = file_get_contents('php://input');
    $data = json_decode($dataRaw, true);
    if (!is_array($data)) {
        $data = [];
    }
    if (!empty($_GET)) {
        $data = array_merge($_GET, $data);
    }
    if (!empty($_POST)) {
        $data = array_merge($data, $_POST);
    }
    return $data;
}

function elm_api_response($error, $message, $extra = [])
{
    header('Content-Type: application/json; charset=utf-8');
    $response = array_merge([
        'error' => (bool) $error,
        'message' => $message,
    ], $extra);
    echo json_encode($response);
    exit;
}

function elm_api_employee_id($data)
{
    return (int) ($data['EmployeeID'] ?? $data['employee_id'] ?? 0);
}

function elm_api_format_leave(array $row)
{
    $days = isset($row['LeaveDays']) ? (float) $row['LeaveDays'] : null;
    return [
        'ID' => (int) ($row['ID'] ?? 0),
        'leave_id' => (int) ($row['ID'] ?? 0),
        'EmployeeID' => (int) ($row['EmployeeID'] ?? 0),
        'TypeOfLeave' => $row['TypeOfLeave'] ?? '',
        'type_of_leave' => $row['TypeOfLeave'] ?? '',
        'ReasonOfLeave' => $row['ReasonOfLeave'] ?? '',
        'reason' => $row['ReasonOfLeave'] ?? '',
        'FromDate' => $row['FromDate'] ?? '',
        'from_date' => $row['FromDate'] ?? '',
        'ToDate' => $row['ToDate'] ?? '',
        'to_date' => $row['ToDate'] ?? '',
        'Duration' => $row['Duration'] ?? '',
        'duration' => $row['Duration'] ?? '',
        'LeaveDays' => $days,
        'leave_days' => $days,
        'HalfDaySession' => $row['HalfDaySession'] ?? null,
        'half_day_session' => $row['HalfDaySession'] ?? null,
        'IsAdvanceLeave' => !empty($row['IsAdvanceLeave']),
        'is_advance_leave' => !empty($row['IsAdvanceLeave']),
        'IsSandwichApplied' => !empty($row['IsSandwichApplied']),
        'is_sandwich_applied' => !empty($row['IsSandwichApplied']),
        'SandwichExtraDays' => (float) ($row['SandwichExtraDays'] ?? 0),
        'sandwich_extra_days' => (float) ($row['SandwichExtraDays'] ?? 0),
        'Status' => $row['Status'] ?? '',
        'status' => $row['Status'] ?? '',
        'Approved' => $row['Approved'] ?? '',
        'CreatedDate' => $row['CreatedDate'] ?? '',
        'CreatedTime' => $row['CreatedTime'] ?? '',
        'RejectionReason' => $row['RejectionReason'] ?? null,
        'rejection_reason' => $row['RejectionReason'] ?? null,
        'can_cancel' => in_array((string) ($row['Status'] ?? ''), ['Pending', 'SupervisorApproved'], true)
            && empty($row['CancelledAt']),
    ];
}

/**
 * Mobile-friendly balance — same numbers as portal My Leave cards.
 */
function elm_api_format_balance(array $summary)
{
    $cl = $summary['monthly']['CL'] ?? [];
    $sl = $summary['monthly']['SL'] ?? [];
    $annual = $summary['annual'] ?? [];
    $policy = $summary['policy'] ?? [];
    $clAvail = (float) ($cl['available'] ?? 0);
    $slAvail = (float) ($sl['available'] ?? 0);
    $maxConsec = (int) ($policy['max_consecutive_days'] ?? 10);
    $fy = $summary['financial_year'] ?? [];
    $fyLabel = (string) ($fy['financial_year_label'] ?? '');

    return [
        'employee_id' => (int) ($summary['employee_id'] ?? 0),
        'joining_date' => $summary['joining_date'] ?? null,
        'accrual_from' => $summary['accrual_from'] ?? null,
        'accrual_months' => (int) ($summary['accrual_months'] ?? 0),
        'year_type' => 'financial',
        'leave_year' => (int) ($summary['leave_year'] ?? 0),
        'financial_year' => $fy,
        'financial_year_label' => $fyLabel,
        'calendar_year' => (int) ($summary['calendar_year'] ?? 0),
        'current_month' => (int) ($summary['month'] ?? 0),
        'cl' => [
            'available' => $clAvail,
            'entitled_this_month' => (float) ($cl['entitled'] ?? 0),
            'carried_in' => (float) ($cl['carried_in'] ?? 0),
            'used' => (float) ($cl['used'] ?? 0),
            'pending' => (float) ($cl['pending'] ?? 0),
        ],
        'sl' => [
            'available' => $slAvail,
            'entitled_this_month' => (float) ($sl['entitled'] ?? 0),
            'carried_in' => (float) ($sl['carried_in'] ?? 0),
            'used' => (float) ($sl['used'] ?? 0),
            'pending' => (float) ($sl['pending'] ?? 0),
        ],
        'comp_off' => [
            'available' => (float) ($summary['comp_off']['available'] ?? 0),
            'enabled' => !empty($summary['comp_off']['enabled']),
        ],
        'total_cl_sl_available' => $clAvail + $slAvail,
        'total_available' => $clAvail + $slAvail,
        'annual' => [
            'cl_quota' => (float) ($annual['cl_quota'] ?? 12),
            'sl_quota' => (float) ($annual['sl_quota'] ?? 12),
            'total_quota' => (float) ($annual['total_quota'] ?? 24),
            'cl_used' => (float) ($annual['cl_used'] ?? 0),
            'sl_used' => (float) ($annual['sl_used'] ?? 0),
            'cl_remaining' => (float) ($annual['cl_remaining'] ?? 0),
            'sl_remaining' => (float) ($annual['sl_remaining'] ?? 0),
            'total_remaining' => (float) (($annual['cl_remaining'] ?? 0) + ($annual['sl_remaining'] ?? 0)),
            'cl_advance_used' => (float) ($annual['cl_advance_used'] ?? 0),
            'sl_advance_used' => (float) ($annual['sl_advance_used'] ?? 0),
            'advance_max' => (float) ($annual['advance_max'] ?? 3),
        ],
        'policy' => $policy,
        'apply_rules' => [
            'max_days_per_application' => $maxConsec,
            'half_day_enabled' => !empty($policy['half_day_enabled']),
            'advance_leave_enabled' => !empty($policy['advance_leave_enabled']),
            'sandwich_rule_enabled' => !empty($policy['sandwich_rule_enabled']),
            'min_notice_days_cl' => (int) ($policy['min_notice_days_cl'] ?? 1),
            'min_notice_days_sl' => (int) ($policy['min_notice_days_sl'] ?? 0),
        ],
        'apply_hint' => 'Financial year (Apr–Mar). Only working days count (weekly offs/holidays excluded). '
            . "Max $maxConsec leave days per application. "
            . 'Unused balance carries forward month to month from joining date or FY start.',
        'monthly' => $summary['monthly'] ?? [],
        'raw' => $summary,
    ];
}

function elm_api_format_calculation(array $calc)
{
    if (empty($calc) || !empty($calc['error'])) {
        return $calc;
    }
    return [
        'total_days' => (float) ($calc['total_days'] ?? 0),
        'working_days' => (float) ($calc['working_days'] ?? 0),
        'sandwich_extra' => (float) ($calc['sandwich_extra'] ?? 0),
        'is_half_day' => !empty($calc['is_half_day']),
        'breakdown' => $calc['breakdown'] ?? [],
    ];
}

function elm_api_normalize_apply_payload(array $data)
{
    $duration = trim((string) ($data['Duration'] ?? $data['duration'] ?? 'Full Day'));
    $isHalf = (stripos($duration, 'half') !== false);
    $halfSession = '';
    if ($isHalf) {
        $halfSession = strtolower(trim((string) ($data['HalfDaySession'] ?? $data['half_day_session'] ?? '')));
    }

    return [
        'EmployeeID' => elm_api_employee_id($data),
        'TypeOfLeave' => $data['TypeOfLeave'] ?? $data['type_of_leave'] ?? '',
        'ReasonOfLeave' => $data['ReasonOfLeave'] ?? $data['reason'] ?? $data['leave_reason'] ?? '',
        'FromDate' => $data['FromDate'] ?? $data['from_date'] ?? '',
        'ToDate' => $data['ToDate'] ?? $data['to_date'] ?? '',
        'Duration' => $duration,
        'HalfDaySession' => $halfSession,
        'CompOffId' => (int) ($data['CompOffId'] ?? $data['comp_off_id'] ?? 0),
    ];
}

function elm_api_format_validation_result(array $result, Employeeleavemgmt $elm, $employeeId, $fromDate = '')
{
    $payload = [
        'days_to_deduct' => (float) ($result['days_to_deduct'] ?? 0),
        'is_advance' => !empty($result['is_advance']),
        'balance_available' => isset($result['balance_available']) ? (float) $result['balance_available'] : null,
        'balance_after' => isset($result['balance_after']) ? (float) $result['balance_after'] : null,
        'days_calculated' => isset($result['days_calculated']) ? (float) $result['days_calculated'] : null,
        'max_consecutive_days' => isset($result['max_consecutive_days']) ? (int) $result['max_consecutive_days'] : null,
        'calculation' => elm_api_format_calculation($result['calculation'] ?? []),
        'balance_check' => $result['balance_check'] ?? null,
    ];

    if ($employeeId > 0) {
        $asOf = $fromDate !== '' ? $fromDate : date('Y-m-d');
        if (!empty($result['calculation']['dates'][0])) {
            $asOf = (string) $result['calculation']['dates'][0];
        }
        $payload['balance'] = elm_api_format_balance($elm->getBalanceSummary($employeeId, $asOf));
    }

    return $payload;
}

function elm_api_bootstrap()
{
    @ini_set('display_errors', '0');
    header('Content-Type: application/json; charset=utf-8');
    require_once dirname(__DIR__) . '/common_api_header.php';
    require_once dirname(__DIR__, 2) . '/admin/controllers/common_controllers.php';
    // common_controllers sets $servername etc. in this function scope — expose for _connectodb()
    $GLOBALS['servername'] = $servername ?? '';
    $GLOBALS['dbusername'] = $dbusername ?? '';
    $GLOBALS['password'] = $password ?? '';
    $GLOBALS['dbname'] = $dbname ?? '';
    if (isset($_URL)) {
        $GLOBALS['_URL'] = $_URL;
    }
    setTimeZone();
    return _connectodb();
}

function elm_api_leave_types()
{
    return [
        ['code' => 'CL', 'name' => 'Casual Leave', 'label' => 'CL — Casual Leave'],
        ['code' => 'SL', 'name' => 'Sick Leave', 'label' => 'SL — Sick Leave'],
        ['code' => 'COMPOFF', 'name' => 'Compensatory Off', 'label' => 'Comp-off'],
        ['code' => 'UNPAID', 'name' => 'Unpaid Leave', 'label' => 'Unpaid Leave (LWP)'],
    ];
}
