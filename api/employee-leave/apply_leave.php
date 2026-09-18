<?php
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$data = elm_api_parse_input();
$payload = elm_api_normalize_apply_payload($data);
if ($payload['EmployeeID'] < 1) {
    elm_api_response(true, 'EmployeeID is required.');
}

$createdBy = trim((string) ($data['CreatedBy'] ?? $data['created_by'] ?? 'mobile_api'));

$elm = new Employeeleavemgmt($conn);
$result = $elm->applyLeave($payload, $createdBy);

if (!empty($result['error'])) {
    $validationShape = $elm->validateLeaveApplication($payload);
    $extra = [
        'days_to_deduct' => (float) ($validationShape['days_to_deduct'] ?? 0),
        'balance_available' => isset($validationShape['balance_available']) ? (float) $validationShape['balance_available'] : null,
        'balance' => elm_api_format_balance($elm->getBalanceSummary($payload['EmployeeID'], $payload['FromDate'] ?: date('Y-m-d'))),
    ];
    if (!empty($validationShape['calculation'])) {
        $extra['calculation'] = elm_api_format_calculation($validationShape['calculation']);
    }
    elm_api_response(true, $result['message'] ?? 'Could not apply leave.', $extra);
}

$leave = null;
if (!empty($result['leave_id'])) {
    $row = $elm->getLeaveById((int) $result['leave_id']);
    if ($row) {
        $leave = elm_api_format_leave($row);
    }
}

$balance = elm_api_format_balance(
    $elm->getBalanceSummary($payload['EmployeeID'], $payload['FromDate'] ?: date('Y-m-d'))
);

elm_api_response(false, $result['message'] ?? 'Leave applied successfully. Pending supervisor approval.', [
    'leave_id' => (int) ($result['leave_id'] ?? 0),
    'days' => (float) ($result['days'] ?? 0),
    'days_to_deduct' => (float) ($result['days'] ?? 0),
    'is_advance' => !empty($result['is_advance']),
    'data' => $leave,
    'leave' => $leave,
    'balance' => $balance,
]);
