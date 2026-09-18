<?php
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$data = elm_api_parse_input();
$employeeId = elm_api_employee_id($data);

$elm = new Employeeleavemgmt($conn);
$policy = $elm->getPolicyForApi();

$response = [
    'leave_types' => elm_api_leave_types(),
    'durations' => ['Full Day', 'Half Day'],
    'half_day_sessions' => [
        ['code' => 'first_half', 'label' => 'First Half'],
        ['code' => 'second_half', 'label' => 'Second Half'],
    ],
    'policy' => $policy,
    'apply_rules' => [
        'max_days_per_application' => (int) ($policy['max_consecutive_days'] ?? 10),
        'half_day_enabled' => !empty($policy['half_day_enabled']),
        'advance_leave_enabled' => !empty($policy['advance_leave_enabled']),
        'sandwich_rule_enabled' => !empty($policy['sandwich_rule_enabled']),
        'min_notice_days_cl' => (int) ($policy['min_notice_days_cl'] ?? 1),
        'min_notice_days_sl' => (int) ($policy['min_notice_days_sl'] ?? 0),
    ],
    'apply_hint' => 'Only working days count. Max ' . (int) ($policy['max_consecutive_days'] ?? 10)
        . ' leave days per application. Check balance before submit.',
];

if ($employeeId > 0) {
    $asOfDate = trim((string) ($data['as_of_date'] ?? $data['AsOfDate'] ?? date('Y-m-d')));
    $balance = elm_api_format_balance($elm->getBalanceSummary($employeeId, $asOfDate));
    $response['balance'] = $balance;
    $response['data'] = $balance;
}

elm_api_response(false, 'Leave apply metadata.', $response);
