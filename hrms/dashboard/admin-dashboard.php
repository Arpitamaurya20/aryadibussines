<?php
@session_start();
require_once('../include/autoloader.inc.php');
$conf = new Conf();
$_ProductName = $conf->_ProductName;

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$core = new Core();
$salary = new Salarypayroll($conn);

$year = intval(date('Y'));
$month = intval(date('n'));
$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

function scalarOrZero($conn, $sql, $col)
{
	$res = mysqli_query($conn, $sql);
	if ($res) {
		$row = mysqli_fetch_assoc($res);
		return floatval($row[$col] ?? 0);
	}
	return 0;
}

$activeEmployees = (int) scalarOrZero(
	$conn,
	"SELECT COUNT(*) AS cnt FROM employees WHERE IFNULL(IsActive,1) = 1 AND IFNULL(`Vendor`,0) = 0",
	'cnt'
);
$payrollRunCount = $core->_getTotalRows($conn, 'hrms_salary_slip', "WHERE PayrollYear=$year AND PayrollMonth=$month");
$totalNetPayout = scalarOrZero($conn, "SELECT COALESCE(SUM(NetSalary),0) AS amount FROM hrms_salary_slip WHERE PayrollYear=$year AND PayrollMonth=$month", 'amount');
$totalDeduction = scalarOrZero($conn, "SELECT COALESCE(SUM(TotalDeductions),0) AS amount FROM hrms_salary_slip WHERE PayrollYear=$year AND PayrollMonth=$month", 'amount');
$presentToday = scalarOrZero($conn, "SELECT COUNT(DISTINCT a.EmployeeID) AS cnt FROM employee_attendance a INNER JOIN employees e ON e.ID = a.EmployeeID WHERE a.RecordDate='$today' AND IFNULL(a.InTime,'')<>'' AND IFNULL(e.IsActive,1)=1 AND IFNULL(e.Vendor,0)=0", 'cnt');
$pendingLeaves = $core->_getTotalRows($conn, 'employee_leave', "WHERE IFNULL(IsActive,1)=1 AND Status IN ('Pending','pending')");
$absentToday = max(0, intval($activeEmployees) - intval($presentToday));

$absentEmployees = [];
$absSql = "SELECT e.ID, e.Name, e.EmployeeNumber, e.State, e.Department, e.ContactNumber
	FROM employees e
	WHERE IFNULL(e.IsActive,1)=1
	  AND IFNULL(e.Vendor,0)=0
	  AND NOT EXISTS (
		SELECT 1
		FROM employee_attendance a
		WHERE a.EmployeeID = e.ID
		  AND a.RecordDate = '$today'
		  AND IFNULL(a.InTime,'') <> ''
	  )
	ORDER BY e.State ASC, e.Name ASC";
$absRes = mysqli_query($conn, $absSql);
if ($absRes) {
	while ($row = mysqli_fetch_assoc($absRes)) {
		$absentEmployees[] = $row;
	}
}

$absentByState = [];
foreach ($absentEmployees as $row) {
	$state = trim((string)($row['State'] ?? ''));
	if ($state === '') $state = 'Unknown';
	if (!isset($absentByState[$state])) $absentByState[$state] = 0;
	$absentByState[$state]++;
}

// Attendance trend for last 7 days
$attendanceTrendLabels = [];
$attendanceTrendPresent = [];
$attendanceTrendAbsent = [];
for ($i = 6; $i >= 0; $i--) {
	$d = date('Y-m-d', strtotime("-$i days"));
	$label = date('d M', strtotime($d));
	$present = scalarOrZero($conn, "SELECT COUNT(DISTINCT a.EmployeeID) AS cnt FROM employee_attendance a INNER JOIN employees e ON e.ID = a.EmployeeID WHERE a.RecordDate='$d' AND IFNULL(a.InTime,'')<>'' AND IFNULL(e.IsActive,1)=1 AND IFNULL(e.Vendor,0)=0", 'cnt');
	$absent = max(0, intval($activeEmployees) - intval($present));
	$attendanceTrendLabels[] = $label;
	$attendanceTrendPresent[] = intval($present);
	$attendanceTrendAbsent[] = intval($absent);
}

// Payroll trend for last 6 months
$payrollTrendLabels = [];
$payrollTrendNet = [];
for ($i = 5; $i >= 0; $i--) {
	$ts = strtotime(date('Y-m-01') . " -$i months");
	$py = intval(date('Y', $ts));
	$pm = intval(date('n', $ts));
	$label = date('M Y', $ts);
	$net = scalarOrZero($conn, "SELECT COALESCE(SUM(NetSalary),0) AS amount FROM hrms_salary_slip WHERE PayrollYear=$py AND PayrollMonth=$pm", 'amount');
	$payrollTrendLabels[] = $label;
	$payrollTrendNet[] = round(floatval($net), 2);
}

$absentStateLabels = array_keys($absentByState);
$absentStateSeries = array_values($absentByState);

$recentEmployees = $core->_getTableRecords($conn, 'employees', 'WHERE IFNULL(IsActive,1)=1 AND IFNULL(Vendor,0)=0 ORDER BY ID DESC LIMIT 8');
$recentSlips = $salary->listSlipsForPeriod($year, $month);
$recentSlips = array_slice($recentSlips, 0, 8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="keywords" content="">
	<title>Dashboard - <?= htmlspecialchars($_ProductName, ENT_QUOTES, 'UTF-8') ?> HRMS</title>
	<?php include("../include/common-head.php"); ?>
	<style>
		.metric-card { border: 0; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.07); }
		.metric-value { font-size: 1.8rem; font-weight: 700; line-height: 1.1; }
		.metric-title { color: #64748b; font-size: .82rem; text-transform: uppercase; letter-spacing: .4px; }
		.quick-link { text-decoration: none; display:block; padding:12px; border:1px solid #e5e7eb; border-radius:10px; color:#1f2937; }
		.quick-link:hover { background:#f8fafc; }
	</style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
	<?php
	include("../navigation/top-header.php");
	include("../navigation/side-navigation.php");
	?>
	<main class="app-main">
		<div class="app-content-header">
			<div class="container-fluid">
				<div class="row align-items-center">
					<div class="col-sm-7">
						<h3 class="mb-0">HRMS Dashboard</h3>
						<p class="text-muted mb-0">Employee, payroll and attendance insights for <?= htmlspecialchars(date('F Y'), ENT_QUOTES, 'UTF-8') ?>.</p>
					</div>
					<div class="col-sm-5 text-sm-end">
						<a href="../hrms-salary/view-payroll" class="btn btn-primary btn-sm">Run Payroll</a>
						<a href="../hrms-employees/view-employees" class="btn btn-outline-secondary btn-sm">Employee Directory</a>
					</div>
				</div>
			</div>
		</div>

		<div class="app-content">
			<div class="container-fluid">
				<div class="row g-3 mb-3">
					<div class="col-lg-3 col-md-6">
						<div class="card metric-card"><div class="card-body">
							<div class="metric-title">Active Employees</div>
							<div class="metric-value text-primary"><?= number_format($activeEmployees) ?></div>
						</div></div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="card metric-card"><div class="card-body">
							<div class="metric-title">Slips Generated (Month)</div>
							<div class="metric-value text-success"><?= number_format($payrollRunCount) ?></div>
						</div></div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="card metric-card"><div class="card-body">
							<div class="metric-title">Total Net Payout (Month)</div>
							<div class="metric-value text-dark">INR <?= number_format($totalNetPayout, 2) ?></div>
						</div></div>
					</div>
					<div class="col-lg-3 col-md-6">
						<div class="card metric-card"><div class="card-body">
							<div class="metric-title">Total Deductions (Month)</div>
							<div class="metric-value text-danger">INR <?= number_format($totalDeduction, 2) ?></div>
						</div></div>
					</div>
				</div>

				<div class="row g-3 mb-3">
					<div class="col-lg-2 col-md-6">
						<div class="card metric-card"><div class="card-body">
							<div class="metric-title">Present Today</div>
							<div class="metric-value"><?= number_format($presentToday) ?></div>
						</div></div>
					</div>
					<div class="col-lg-2 col-md-6">
						<div class="card metric-card"><div class="card-body">
							<div class="metric-title">Absent Today</div>
							<div class="metric-value text-danger"><?= number_format($absentToday) ?></div>
						</div></div>
					</div>
					<div class="col-lg-2 col-md-6">
						<div class="card metric-card"><div class="card-body">
							<div class="metric-title">Pending Leaves</div>
							<div class="metric-value"><?= number_format($pendingLeaves) ?></div>
						</div></div>
					</div>
					<div class="col-lg-6">
						<div class="card metric-card"><div class="card-body">
							<div class="metric-title mb-2">Quick Actions</div>
							<div class="row g-2">
								<div class="col-md-4"><a class="quick-link" href="../hrms-employees/view-employees"><i class="bi bi-people me-2"></i>Employee Profiles</a></div>
								<div class="col-md-4"><a class="quick-link" href="../hrms-salary/view-payroll"><i class="bi bi-cash-stack me-2"></i>Payroll & Slips</a></div>
								<div class="col-md-4"><a class="quick-link" href="../hrms-salary/view-payroll?m=<?= $month ?>&y=<?= $year ?>"><i class="bi bi-receipt me-2"></i>Current Month Slips</a></div>
							</div>
						</div></div>
					</div>
				</div>

				<div class="row g-3">
					<div class="col-lg-6">
						<div class="card">
							<div class="card-header bg-white"><strong>Recent Employees</strong></div>
							<div class="card-body p-0">
								<div class="table-responsive">
									<table class="table mb-0">
										<thead><tr><th>Name</th><th>Code</th><th>Department</th><th></th></tr></thead>
										<tbody>
										<?php if (empty($recentEmployees)) { ?>
											<tr><td colspan="4" class="text-center text-muted py-4">No employees found</td></tr>
										<?php } else { foreach ($recentEmployees as $emp) { ?>
											<tr>
												<td><?= htmlspecialchars($emp['Name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
												<td><?= htmlspecialchars($emp['EmployeeNumber'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
												<td><?= htmlspecialchars($emp['Department'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
												<td><a class="btn btn-sm btn-outline-primary" href="../hrms-employees/view-employee-profile.php?id=<?= intval($emp['ID']) ?>&m=<?= $month ?>&y=<?= $year ?>">Profile</a></td>
											</tr>
										<?php }} ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="card">
							<div class="card-header bg-white d-flex justify-content-between align-items-center">
								<strong>Latest Salary Slips (<?= htmlspecialchars(date('M Y'), ENT_QUOTES, 'UTF-8') ?>)</strong>
								<a class="btn btn-sm btn-outline-secondary" href="../hrms-salary/view-payroll">View All</a>
							</div>
							<div class="card-body p-0">
								<div class="table-responsive">
									<table class="table mb-0">
										<thead><tr><th>Employee</th><th class="text-end">Net</th><th></th></tr></thead>
										<tbody>
										<?php if (empty($recentSlips)) { ?>
											<tr><td colspan="3" class="text-center text-muted py-4">No slips generated this month</td></tr>
										<?php } else { foreach ($recentSlips as $slip) { ?>
											<tr>
												<td><?= htmlspecialchars($slip['Name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
												<td class="text-end"><?= number_format(floatval($slip['NetSalary'] ?? 0), 2) ?></td>
												<td>
													<a class="btn btn-sm btn-primary" target="_blank" href="../hrms-salary/view-payslip-pro.php?id=<?= intval($slip['ID']) ?>">Slip</a>
												</td>
											</tr>
										<?php }} ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-8">
						<div class="card">
							<div class="card-header bg-white"><strong>Attendance Trend (Last 7 Days)</strong></div>
							<div class="card-body">
								<div id="attendanceTrendChart" style="height: 300px;"></div>
							</div>
						</div>
					</div>
					<div class="col-lg-4">
						<div class="card">
							<div class="card-header bg-white"><strong>Absent by State (Today)</strong></div>
							<div class="card-body">
								<div id="absentStateChart" style="height: 300px;"></div>
							</div>
						</div>
					</div>
					<div class="col-lg-12">
						<div class="card">
							<div class="card-header bg-white"><strong>Payroll Net Payout Trend (Last 6 Months)</strong></div>
							<div class="card-body">
								<div id="payrollTrendChart" style="height: 300px;"></div>
							</div>
						</div>
					</div>
					<div class="col-lg-12">
						<div class="card">
							<div class="card-header bg-white d-flex justify-content-between align-items-center">
								<strong>Absent Employees Today (<?= htmlspecialchars(date('d M Y'), ENT_QUOTES, 'UTF-8') ?>)</strong>
								<span class="small text-muted">Total: <?= number_format(count($absentEmployees)) ?></span>
							</div>
							<div class="card-body">
								<?php if (!empty($absentByState)) { ?>
									<div class="mb-3">
										<?php foreach ($absentByState as $state => $cnt) { ?>
											<span class="badge text-bg-light border me-1 mb-1"><?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>: <?= intval($cnt) ?></span>
										<?php } ?>
									</div>
								<?php } ?>
								<div class="table-responsive">
									<table class="table table-sm mb-0">
										<thead>
											<tr>
												<th>Employee</th>
												<th>Code</th>
												<th>State</th>
												<th>Department</th>
												<th>Contact</th>
											</tr>
										</thead>
										<tbody>
										<?php if (empty($absentEmployees)) { ?>
											<tr><td colspan="5" class="text-center text-success py-3">No absentees today.</td></tr>
										<?php } else { foreach ($absentEmployees as $emp) { ?>
											<tr>
												<td><?= htmlspecialchars($emp['Name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
												<td><?= htmlspecialchars($emp['EmployeeNumber'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
												<td><?= htmlspecialchars($emp['State'] ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></td>
												<td><?= htmlspecialchars($emp['Department'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
												<td><?= htmlspecialchars($emp['ContactNumber'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
											</tr>
										<?php }} ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</main>
	<?php include("../include/common-footer.php"); ?>
</div>
<?php include("../include/common-script.php"); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
	const attendanceLabels = <?php echo json_encode($attendanceTrendLabels); ?>;
	const attendancePresent = <?php echo json_encode($attendanceTrendPresent); ?>;
	const attendanceAbsent = <?php echo json_encode($attendanceTrendAbsent); ?>;
	const payrollLabels = <?php echo json_encode($payrollTrendLabels); ?>;
	const payrollNet = <?php echo json_encode($payrollTrendNet); ?>;
	const absentStateLabels = <?php echo json_encode($absentStateLabels); ?>;
	const absentStateSeries = <?php echo json_encode($absentStateSeries); ?>;

	const attendanceTrendEl = document.querySelector('#attendanceTrendChart');
	if (attendanceTrendEl && typeof ApexCharts !== 'undefined') {
		new ApexCharts(attendanceTrendEl, {
			chart: { type: 'line', height: 300, toolbar: { show: false } },
			series: [
				{ name: 'Present', data: attendancePresent },
				{ name: 'Absent', data: attendanceAbsent }
			],
			stroke: { width: 3, curve: 'smooth' },
			colors: ['#16a34a', '#dc2626'],
			xaxis: { categories: attendanceLabels },
			yaxis: { min: 0, forceNiceScale: true },
			grid: { borderColor: '#e5e7eb' },
			legend: { position: 'top' }
		}).render();
	}

	const absentStateEl = document.querySelector('#absentStateChart');
	if (absentStateEl && typeof ApexCharts !== 'undefined') {
		if (absentStateSeries.length > 0) {
			new ApexCharts(absentStateEl, {
				chart: { type: 'donut', height: 300 },
				series: absentStateSeries,
				labels: absentStateLabels,
				legend: { position: 'bottom' }
			}).render();
		} else {
			absentStateEl.innerHTML = '<div class="text-center text-success pt-5">No absentees today</div>';
		}
	}

	const payrollTrendEl = document.querySelector('#payrollTrendChart');
	if (payrollTrendEl && typeof ApexCharts !== 'undefined') {
		new ApexCharts(payrollTrendEl, {
			chart: { type: 'bar', height: 300, toolbar: { show: false } },
			series: [{ name: 'Net Payout', data: payrollNet }],
			xaxis: { categories: payrollLabels },
			colors: ['#1d4ed8'],
			dataLabels: { enabled: false },
			grid: { borderColor: '#e5e7eb' },
			yaxis: {
				labels: {
					formatter: function (v) { return 'INR ' + Math.round(v).toLocaleString(); }
				}
			}
		}).render();
	}
});
</script>
</body>
</html>
