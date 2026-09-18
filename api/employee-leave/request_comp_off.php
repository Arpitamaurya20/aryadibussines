<?php
require_once('employee_leave_helpers.php');

$conn = elm_api_bootstrap();
$data = elm_api_parse_input();
$employeeId = elm_api_employee_id($data);
if ($employeeId < 1) {
    elm_api_response(true, 'EmployeeID is required.');
}

$workDate = trim((string) ($data['work_date'] ?? $data['WorkDate'] ?? ''));
$creditDays = $data['credit_days'] ?? $data['CreditDays'] ?? 1;
$reason = trim((string) ($data['reason'] ?? $data['Reason'] ?? ''));
$createdBy = trim((string) ($data['CreatedBy'] ?? $data['created_by'] ?? 'mobile_api'));

$elm = new Employeeleavemgmt($conn);
$result = $elm->requestCompOff($employeeId, $workDate, $creditDays, $reason, $createdBy);

$balance = elm_api_format_balance($elm->getBalanceSummary($employeeId));

elm_api_response(!empty($result['error']), $result['message'] ?? 'Done', [
    'comp_off_id' => (int) ($result['comp_off_id'] ?? 0),
    'balance' => $balance,
]);
