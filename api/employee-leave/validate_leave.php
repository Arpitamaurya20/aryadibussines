<?php
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$data = elm_api_parse_input();
$payload = elm_api_normalize_apply_payload($data);
if ($payload['EmployeeID'] < 1) {
    elm_api_response(true, 'EmployeeID is required.');
}

$elm = new Employeeleavemgmt($conn);
$result = $elm->validateLeaveApplication($payload);

if (!empty($result['error'])) {
    $extra = elm_api_format_validation_result($result, $elm, $payload['EmployeeID'], $payload['FromDate']);
    elm_api_response(true, $result['message'] ?? 'Validation failed.', $extra);
}

$extra = elm_api_format_validation_result($result, $elm, $payload['EmployeeID'], $payload['FromDate']);
$type = strtoupper(trim((string) $payload['TypeOfLeave']));
$days = (float) ($result['days_to_deduct'] ?? 0);
$avail = (float) ($result['balance_available'] ?? 0);
$after = (float) ($result['balance_after'] ?? 0);

$message = $result['message'] ?? 'Leave can be applied.';
$message .= " Days to deduct: $days. $type balance: $avail available, $after will remain after approval.";
if (!empty($result['is_advance'])) {
    $message .= ' (includes advance leave)';
}

elm_api_response(false, $message, $extra);
