<?php
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$data = elm_api_parse_input();
$employeeId = elm_api_employee_id($data);
if ($employeeId < 1) {
    elm_api_response(true, 'EmployeeID is required.');
}

$asOfDate = trim((string) ($data['as_of_date'] ?? $data['AsOfDate'] ?? ''));
if ($asOfDate === '') {
    $asOfDate = date('Y-m-d');
}

$elm = new Employeeleavemgmt($conn);
$summary = $elm->getBalanceSummary($employeeId, $asOfDate);
$formatted = elm_api_format_balance($summary);

elm_api_response(false, 'Leave balance fetched.', [
    'data' => $formatted,
    'balance' => $formatted,
]);
