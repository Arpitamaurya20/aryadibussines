<?php
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$data = elm_api_parse_input();
$employeeId = elm_api_employee_id($data);
$leaveId = (int) ($data['LeaveID'] ?? $data['leave_id'] ?? 0);
if ($employeeId < 1 || $leaveId < 1) {
    elm_api_response(true, 'EmployeeID and leave_id are required.');
}

$cancelledBy = trim((string) ($data['CancelledBy'] ?? $data['cancelled_by'] ?? 'mobile_api'));

$elm = new Employeeleavemgmt($conn);
$result = $elm->cancelLeave($leaveId, $employeeId, $cancelledBy);

$balance = elm_api_format_balance($elm->getBalanceSummary($employeeId));

elm_api_response(!empty($result['error']), $result['message'] ?? 'Done', [
    'balance' => $balance,
]);
