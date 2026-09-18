<?php
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$data = elm_api_parse_input();
$employeeId = elm_api_employee_id($data);
if ($employeeId < 1) {
    elm_api_response(true, 'EmployeeID is required.');
}

$elm = new Employeeleavemgmt($conn);
$rows = $elm->listCompOff($employeeId);
$balance = elm_api_format_balance($elm->getBalanceSummary($employeeId));

elm_api_response(false, 'Comp-off list.', [
    'data' => $rows,
    'comp_off_list' => $rows,
    'balance' => $balance,
]);
