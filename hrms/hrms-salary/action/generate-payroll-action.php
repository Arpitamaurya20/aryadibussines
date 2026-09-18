<?php
@session_start();
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

// Unlock session before KPI + slip work so other HRMS/admin tabs do not freeze
if (session_status() === PHP_SESSION_ACTIVE) {
	session_write_close();
}

$year = isset($_POST['year']) ? intval($_POST['year']) : intval(date('Y'));
$month = isset($_POST['month']) ? intval($_POST['month']) : intval(date('n'));
$overwrite = !empty($_POST['overwrite']);
$includeKpi = !empty($_POST['include_kpi']);
$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$returnY = isset($_POST['return_y']) ? intval($_POST['return_y']) : $year;
$returnM = isset($_POST['return_m']) ? intval($_POST['return_m']) : $month;

$by = isset($_SESSION['pp_email']) ? $_SESSION['pp_email'] : 'admin';
$salary = new Salarypayroll($conn);

/**
 * Hidden+checkbox pairs submit duplicate keys; use last value when an array is received.
 */
$readPayrollCheckbox = static function ($key, $default = 0) {
	if (!isset($_POST[$key])) {
		return $default ? 1 : 0;
	}
	$value = $_POST[$key];
	if (is_array($value)) {
		$value = end($value);
	}
	return intval($value) === 1 ? 1 : 0;
};

$paidDaysOptions = [
	'include_profile_weekly_off' => $readPayrollCheckbox('include_profile_weekly_off', 0),
	'include_fixed_weekly_offs' => $readPayrollCheckbox('include_fixed_weekly_offs', 1),
	'fixed_weekly_offs' => isset($_POST['fixed_weekly_offs']) ? intval($_POST['fixed_weekly_offs']) : 4,
	'include_approved_attendance' => $readPayrollCheckbox('include_approved_attendance', 1),
	'include_checkout_attendance' => $readPayrollCheckbox('include_checkout_attendance', 1),
	'include_min_hours_rule' => $readPayrollCheckbox('include_min_hours_rule', 1),
	'min_work_hours' => isset($_POST['min_work_hours']) ? floatval($_POST['min_work_hours']) : 9,
	'include_deduct_non_cl_sl_leave' => $readPayrollCheckbox('include_deduct_non_cl_sl_leave', 1),
	'include_half_day_deduction' => $readPayrollCheckbox('include_half_day_deduction', 1),
	'include_prorate_joining_date' => $readPayrollCheckbox('include_prorate_joining_date', 1),
];
if ($employeeId > 0) {
	$res = $salary->generateSingleEmployeePayroll($year, $month, $employeeId, $overwrite, $by, $includeKpi, $paidDaysOptions);
} else {
	$res = $salary->generateMonthlyPayroll($year, $month, $overwrite, $by, $paidDaysOptions);
}

$base = '../view-payroll.php?y=' . $returnY . '&m=' . $returnM;
if (!empty($res['error'])) {
	header('Location: ' . $base . '&err=' . urlencode($res['message']));
} else {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	$_SESSION['hrms_last_payroll_options'] = $paidDaysOptions;
	if ($employeeId > 0) {
		$_SESSION['hrms_last_payroll_employee_id'] = $employeeId;
	}
	session_write_close();
	$redirect = $base . '&ok=' . urlencode($res['message']);
	if (!empty($res['salary_slip_id'])) {
		$redirect .= '&latest_slip_id=' . intval($res['salary_slip_id']);
	}
	header('Location: ' . $redirect);
}
exit;
