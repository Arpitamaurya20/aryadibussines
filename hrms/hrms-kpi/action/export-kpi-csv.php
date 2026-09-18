<?php
/**
 * Export KPI snapshots for the current view (period + filters) to CSV.
 * Reads the same GET filters as view-kpi.php so the export reflects what
 * the user sees on screen.
 */
@session_start();
ini_set('display_errors', 0);
require_once('../../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$kpi = new Employeekpi($conn);

$y = isset($_GET['y']) ? intval($_GET['y']) : intval(date('Y'));
$m = isset($_GET['m']) ? intval($_GET['m']) : intval(date('n'));
if ($m < 1 || $m > 12) {
	$m = intval(date('n'));
}

$filters = [
	'search'      => isset($_GET['search'])      ? trim((string) $_GET['search'])      : '',
	'role'        => isset($_GET['role'])        ? trim((string) $_GET['role'])        : '',
	'min_overall' => isset($_GET['min_overall']) ? trim((string) $_GET['min_overall']) : '',
	'max_overall' => isset($_GET['max_overall']) ? trim((string) $_GET['max_overall']) : '',
	'whatsapp'    => isset($_GET['whatsapp'])    ? trim((string) $_GET['whatsapp'])    : '',
];

$rows = $kpi->listSnapshots($y, $m, $filters);

$filename = sprintf('employee_kpi_%04d_%02d_%s.csv', $y, $m, date('Ymd_His'));
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF"; // BOM so Excel opens UTF-8 correctly.

$out = fopen('php://output', 'w');
fputcsv($out, [
	'ID', 'Employee ID', 'Employee Name', 'Designation', 'Contact', 'Role',
	'Year', 'Month', 'Date From', 'Date To',
	'Assign Total', 'Assign Within 1Hr', 'Assign After 1Hr', 'Assign Reassign', 'Assign %',
	'Quote Total', 'Quote Within 48Hr', 'Quote After 48Hr', 'Quote Pending', 'Quote Not Approved 48Hr', 'Quote AMC', 'Quote %',
	'Closed Total', 'Closed Within 24Hr', 'Closed After 24Hr', 'Closing Target', 'Closed %',
	'Working Days', 'Present Days', 'Absent Days', 'Attendance %',
	'Overall KPI %', 'Status',
	'Full Salary', 'Payable Salary', 'KPI Deduction', 'Salary %',
	'WhatsApp Sent', 'Sent At', 'Last Updated',
]);

foreach ($rows as $r) {
	$status = $kpi->statusForPct($r['OverallKpiPct']);
	$fullSal = floatval($r['FullSalary']);
	$paySal  = floatval($r['PayableSalary']);
	fputcsv($out, [
		$r['ID'],
		$r['EmployeeID'],
		$r['EmployeeName'],
		$r['Designation'],
		$r['ContactNumber'],
		!empty($r['IsExecutive']) ? 'Executive' : 'Non-executive',
		$r['KpiYear'],
		$r['KpiMonth'],
		$r['DateFrom'],
		$r['DateTo'],
		$r['AssignTotalTickets'],
		$r['AssignWithin1Hour'],
		$r['AssignAfter1Hour'],
		$r['AssignReassignCount'],
		$r['AssignPerformancePct'],
		$r['QuoteTotalTickets'],
		$r['QuoteWithin48Hour'],
		$r['QuoteAfter48Hour'],
		$r['QuotePending'],
		$r['QuoteNotApproved48'],
		$r['QuoteAmcTickets'],
		$r['QuotePerformancePct'],
		$r['ClosedTotal'],
		$r['ClosedWithin24'],
		$r['ClosedAfter24'],
		$r['ClosingTarget'],
		$r['ClosedPerformancePct'],
		$r['WorkingDays'],
		$r['PresentDays'],
		$r['AbsentDays'],
		$r['AttendancePerformancePct'],
		$r['OverallKpiPct'],
		$status['label'],
		number_format($fullSal, 2, '.', ''),
		number_format($paySal, 2, '.', ''),
		number_format(max(0, $fullSal - $paySal), 2, '.', ''),
		$r['SalaryPct'],
		!empty($r['WhatsappSent']) ? 'Yes' : 'No',
		$r['SentAt'],
		$r['UpdatedAt'],
	]);
}
fclose($out);
exit;
