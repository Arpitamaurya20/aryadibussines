<?php
@session_start();
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$year = isset($_POST['year']) ? intval($_POST['year']) : intval(date('Y'));
$month = isset($_POST['month']) ? intval($_POST['month']) : intval(date('n'));
$overwrite = !empty($_POST['overwrite']);
$includeKpi = !empty($_POST['include_kpi']);
$employeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$returnY = isset($_POST['return_y']) ? intval($_POST['return_y']) : $year;
$returnM = isset($_POST['return_m']) ? intval($_POST['return_m']) : $month;

$by = isset($_SESSION['pp_email']) ? $_SESSION['pp_email'] : 'admin';
$salary = new Salarypayroll($conn);
if ($employeeId > 0) {
	$res = $salary->generateSingleEmployeePayroll($year, $month, $employeeId, $overwrite, $by, $includeKpi);
} else {
	$res = $salary->generateMonthlyPayroll($year, $month, $overwrite, $by);
}

$base = '../view-payroll.php?y=' . $returnY . '&m=' . $returnM;
if (!empty($res['error'])) {
	header('Location: ' . $base . '&err=' . urlencode($res['message']));
} else {
	$redirect = $base . '&ok=' . urlencode($res['message']);
	if (!empty($res['salary_slip_id'])) {
		$redirect .= '&latest_slip_id=' . intval($res['salary_slip_id']);
	}
	header('Location: ' . $redirect);
}
exit;
