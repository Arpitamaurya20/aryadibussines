<?php
@session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$readPayrollCheckbox = static function ($key, $default = 0) {
	$source = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
	if (!isset($source[$key])) {
		return $default ? 1 : 0;
	}
	$value = $source[$key];
	if (is_array($value)) {
		$value = end($value);
	}
	return intval($value) === 1 ? 1 : 0;
};

$employeeId = isset($_REQUEST['employee_id']) ? intval($_REQUEST['employee_id']) : 0;
$year = isset($_REQUEST['year']) ? intval($_REQUEST['year']) : intval(date('Y'));
$month = isset($_REQUEST['month']) ? intval($_REQUEST['month']) : intval(date('n'));

$paidDaysOptions = [
	'include_profile_weekly_off' => $readPayrollCheckbox('include_profile_weekly_off', 0),
	'include_fixed_weekly_offs' => $readPayrollCheckbox('include_fixed_weekly_offs', 1),
	'fixed_weekly_offs' => isset($_REQUEST['fixed_weekly_offs']) ? intval($_REQUEST['fixed_weekly_offs']) : 4,
	'include_approved_attendance' => $readPayrollCheckbox('include_approved_attendance', 1),
	'include_checkout_attendance' => $readPayrollCheckbox('include_checkout_attendance', 1),
	'include_min_hours_rule' => $readPayrollCheckbox('include_min_hours_rule', 1),
	'min_work_hours' => isset($_REQUEST['min_work_hours']) ? floatval($_REQUEST['min_work_hours']) : 9,
	'include_deduct_non_cl_sl_leave' => $readPayrollCheckbox('include_deduct_non_cl_sl_leave', 1),
	'include_half_day_deduction' => $readPayrollCheckbox('include_half_day_deduction', 1),
	'include_prorate_joining_date' => $readPayrollCheckbox('include_prorate_joining_date', 1),
];

$salary = new Salarypayroll($conn);
$result = $salary->getPayrollAttendanceLeavePreview($employeeId, $year, $month, $paidDaysOptions);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
