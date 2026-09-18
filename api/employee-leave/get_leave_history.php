<?php
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$data = elm_api_parse_input();
$employeeId = elm_api_employee_id($data);
if ($employeeId < 1) {
    elm_api_response(true, 'EmployeeID is required.');
}

$limit = (int) ($data['limit'] ?? 100);
$includeBalance = !isset($data['include_balance']) || !in_array(strtolower((string) $data['include_balance']), ['0', 'false', 'no'], true);

$elm = new Employeeleavemgmt($conn);
$elm->syncApprovedLeaveDeductions($employeeId);
$rows = $elm->listEmployeeLeaves($employeeId, $limit);
$formatted = [];
foreach ($rows as $row) {
    $formatted[] = elm_api_format_leave($row);
}

$extra = ['data' => $formatted, 'leaves' => $formatted];
if ($includeBalance) {
    $balance = elm_api_format_balance($elm->getBalanceSummary($employeeId));
    $extra['balance'] = $balance;
}

elm_api_response(false, 'Leave history.', $extra);
