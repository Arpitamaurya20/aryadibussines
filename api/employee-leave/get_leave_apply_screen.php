<?php
/**
 * Single call for mobile Apply Leave screen: policy + balance + types.
 * POST/GET { "EmployeeID": 123, "as_of_date": "2026-06-30" }
 */
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$data = elm_api_parse_input();
$employeeId = elm_api_employee_id($data);
if ($employeeId < 1) {
    elm_api_response(true, 'EmployeeID is required.');
}

$asOfDate = trim((string) ($data['as_of_date'] ?? $data['AsOfDate'] ?? date('Y-m-d')));
$elm = new Employeeleavemgmt($conn);
$policy = $elm->getPolicyForApi();
$balance = elm_api_format_balance($elm->getBalanceSummary($employeeId, $asOfDate));

elm_api_response(false, 'Apply leave screen data.', [
    'balance' => $balance,
    'leave_types' => elm_api_leave_types(),
    'durations' => ['Full Day', 'Half Day'],
    'half_day_sessions' => [
        ['code' => 'first_half', 'label' => 'First Half'],
        ['code' => 'second_half', 'label' => 'Second Half'],
    ],
    'policy' => $policy,
    'apply_rules' => $balance['apply_rules'] ?? [],
    'apply_hint' => $balance['apply_hint'] ?? '',
]);
