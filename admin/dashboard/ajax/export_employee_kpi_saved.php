<?php
/**
 * Export saved monthly KPI snapshots to Excel-compatible CSV.
 * GET: year=2026&month=4  (month optional — exports full year if omitted)
 */
ini_set('display_errors', 0);
@session_start();
include('../../includes/autoloader.inc.php');
include('../../controllers/common_controllers.php');
include('../inc/employee_kpi_snapshot.php');

$UserType = SessionCheck();
if (!$UserType) {
	die('Not authenticated');
}

$conn = _connectodb();
employee_kpi_snapshot_ensure_table($conn);

$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;

$where = "KpiYear = $year";
if ($month >= 1 && $month <= 12) {
	$where .= " AND KpiMonth = $month";
}
$sql = "SELECT * FROM employee_kpi_monthly_snapshot WHERE $where ORDER BY KpiYear, KpiMonth, EmployeeName ASC";
$res = mysqli_query($conn, $sql);

$filename = 'employee_kpi_saved_' . $year;
if ($month > 0) {
	$filename .= '_' . sprintf('%02d', $month);
}
$filename .= '_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, [
	'Employee ID', 'Employee Name', 'Designation', 'Contact', 'Year', 'Month',
	'Date From', 'Date To', 'Executive Role',
	'Assign Total', 'Assign Within 1Hr', 'Assign After 1Hr', 'Assign Reassign', 'Assign %',
	'Quote Total', 'Quote Within 48Hr', 'Quote After 48Hr', 'Quote Pending', 'Quote Not Approved 48Hr', 'Quote AMC', 'Quote %',
	'Closed Total', 'Closed Within 24Hr', 'Closed After 24Hr', 'Closing Target', 'Closed %',
	'Working Days', 'Present Days', 'Absent Days', 'Attendance %',
	'Overall KPI %', 'Full Salary', 'Payable Salary', 'Salary %',
	'WhatsApp Sent', 'Sent At', 'Last Updated',
]);

if ($res) {
	while ($r = mysqli_fetch_assoc($res)) {
		fputcsv($out, [
			$r['EmployeeID'],
			$r['EmployeeName'],
			$r['Designation'],
			$r['ContactNumber'],
			$r['KpiYear'],
			$r['KpiMonth'],
			$r['DateFrom'],
			$r['DateTo'],
			$r['IsExecutive'] ? 'Yes' : 'No',
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
			$r['FullSalary'],
			$r['PayableSalary'],
			$r['SalaryPct'],
			$r['WhatsappSent'] ? 'Yes' : 'No',
			$r['SentAt'],
			$r['UpdatedAt'],
		]);
	}
}
fclose($out);
exit;
