<?php
@session_start();
require_once('../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();
$core = new Core();

$year = isset($_GET['y']) ? intval($_GET['y']) : intval(date('Y'));
$month = isset($_GET['m']) ? intval($_GET['m']) : intval(date('n'));
$department = isset($_GET['department']) ? trim($_GET['department']) : '';
$division = isset($_GET['division']) ? trim($_GET['division']) : '';
$state = isset($_GET['state']) ? trim($_GET['state']) : '';
$employeeId = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$employeeQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($month < 1 || $month > 12) $month = intval(date('n'));
if ($year < 2000 || $year > 2099) $year = intval(date('Y'));

$monthStart = sprintf('%04d-%02d-01', $year, $month);
$monthEnd = date('Y-m-t', strtotime($monthStart));
$daysInMonth = intval(date('t', strtotime($monthStart)));
$today = date('Y-m-d');

function esc($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function parseWeeklyOffDays($value)
{
	$map = [
		'sunday' => 0, 'sun' => 0,
		'monday' => 1, 'mon' => 1,
		'tuesday' => 2, 'tue' => 2, 'tues' => 2,
		'wednesday' => 3, 'wed' => 3,
		'thursday' => 4, 'thu' => 4, 'thur' => 4, 'thurs' => 4,
		'friday' => 5, 'fri' => 5,
		'saturday' => 6, 'sat' => 6,
	];
	$tokens = preg_split('/[,\/\-\s]+/', strtolower(trim((string)$value)));
	$days = [];
	foreach ($tokens as $t) {
		if ($t === '') continue;
		if (isset($map[$t])) $days[$map[$t]] = true;
	}
	if (empty($days)) $days[0] = true; // default Sunday
	return array_keys($days);
}

function isDateInLeaveRange($date, $fromDate, $toDate)
{
	return ($date >= $fromDate && $date <= $toDate);
}

$where = "WHERE IFNULL(IsActive,1)=1 AND IFNULL(Vendor,0)=0";
if ($department !== '') {
	$depEsc = mysqli_real_escape_string($conn, $department);
	$where .= " AND Department = '$depEsc'";
}
if ($division !== '') {
	$divEsc = mysqli_real_escape_string($conn, $division);
	$where .= " AND Division = '$divEsc'";
}
if ($state !== '') {
	$stateEsc = mysqli_real_escape_string($conn, $state);
	$where .= " AND State = '$stateEsc'";
}
$employeeQueryEsc = '';
if ($employeeQuery !== '') {
	$employeeQueryEsc = mysqli_real_escape_string($conn, $employeeQuery);
	$where .= " AND (Name LIKE '%$employeeQueryEsc%' OR EmployeeNumber LIKE '%$employeeQueryEsc%')";
}
$employeeIdFilter = '';
if ($employeeId > 0) {
	$employeeIdFilter = " AND ID = " . intval($employeeId);
}
$employees = $core->_getTableRecords($conn, 'employees', $where . $employeeIdFilter . ' ORDER BY Name ASC');

$departments = $core->_getRecords($conn, "SELECT DISTINCT Department FROM employees WHERE IFNULL(IsActive,1)=1 AND IFNULL(Vendor,0)=0 AND IFNULL(Department,'')<>'' ORDER BY Department ASC");
$divisions = $core->_getRecords($conn, "SELECT DISTINCT Division FROM employees WHERE IFNULL(IsActive,1)=1 AND IFNULL(Vendor,0)=0 AND IFNULL(Division,'')<>'' ORDER BY Division ASC");
$states = $core->_getRecords($conn, "SELECT DISTINCT State FROM employees WHERE IFNULL(IsActive,1)=1 AND IFNULL(Vendor,0)=0 AND IFNULL(State,'')<>'' ORDER BY State ASC");

$employeeById = [];
$employeeIds = [];
foreach ($employees as $emp) {
	$id = intval($emp['ID']);
	$employeeById[$id] = $emp;
	$employeeIds[] = $id;
}

$attendanceMap = [];
$leaveMap = [];
$holidayMap = [];

if (!empty($employeeIds)) {
	$idList = implode(',', array_map('intval', $employeeIds));
	$attSql = "SELECT EmployeeID, RecordDate, InTime, OutTime FROM employee_attendance
		WHERE EmployeeID IN ($idList) AND RecordDate >= '$monthStart' AND RecordDate <= '$monthEnd'";
	$attRes = mysqli_query($conn, $attSql);
	if ($attRes) {
		while ($row = mysqli_fetch_assoc($attRes)) {
			$eid = intval($row['EmployeeID']);
			$d = $row['RecordDate'];
			$attendanceMap[$eid][$d] = $row;
		}
	}

	$leaveSql = "SELECT EmployeeID, TypeOfLeave, Status, FromDate, ToDate
		FROM employee_leave
		WHERE EmployeeID IN ($idList)
		  AND IFNULL(IsActive,1)=1
		  AND FromDate <= '$monthEnd' AND ToDate >= '$monthStart'";
	$leaveRes = mysqli_query($conn, $leaveSql);
	if ($leaveRes) {
		while ($row = mysqli_fetch_assoc($leaveRes)) {
			$eid = intval($row['EmployeeID']);
			$leaveMap[$eid][] = $row;
		}
	}
}

$holSql = "SELECT RegionName, HolidaysName, HolidaysDate FROM listofholidays
	WHERE IFNULL(IsActive,1)=1 AND HolidaysDate >= '$monthStart' AND HolidaysDate <= '$monthEnd'";
$holRes = mysqli_query($conn, $holSql);
if ($holRes) {
	while ($row = mysqli_fetch_assoc($holRes)) {
		$holidayMap[$row['HolidaysDate']] = $row;
	}
}

function getDayStatusForEmployee($emp, $date, $attendanceMap, $leaveMap, $holidayMap, $today)
{
	$eid = intval($emp['ID']);
	$weeklyOffDays = parseWeeklyOffDays($emp['WeeklyOff'] ?? 'Sunday');
	$weekday = intval(date('w', strtotime($date)));
	$hasAttendance = isset($attendanceMap[$eid][$date]);
	$leaveType = '';
	$leaveStatus = '';

	if (!empty($leaveMap[$eid])) {
		foreach ($leaveMap[$eid] as $lv) {
			if (isDateInLeaveRange($date, $lv['FromDate'], $lv['ToDate'])) {
				$leaveType = $lv['TypeOfLeave'] ?: 'Leave';
				$leaveStatus = strtolower(trim($lv['Status'] ?? ''));
				break;
			}
		}
	}

	if ($hasAttendance) {
		return ['code' => 'P', 'label' => 'Present', 'class' => 'present', 'extra' => ($attendanceMap[$eid][$date]['InTime'] ?? '')];
	}

	if ($leaveType !== '') {
		$isApproved = ($leaveStatus === 'approved' || $leaveStatus === 'approve');
		return [
			'code' => $isApproved ? 'L' : 'PL',
			'label' => $isApproved ? 'Leave' : 'Pending Leave',
			'class' => $isApproved ? 'leave' : 'pending-leave',
			'extra' => $leaveType
		];
	}

	if (isset($holidayMap[$date])) {
		return ['code' => 'H', 'label' => 'Public Holiday', 'class' => 'holiday', 'extra' => $holidayMap[$date]['HolidaysName']];
	}

	if (in_array($weekday, $weeklyOffDays, true)) {
		return ['code' => 'WO', 'label' => 'Weekly Off', 'class' => 'weekly-off', 'extra' => date('l', strtotime($date))];
	}

	if ($date > $today) {
		return ['code' => '-', 'label' => 'Future', 'class' => 'future', 'extra' => ''];
	}

	return ['code' => 'A', 'label' => 'Absent', 'class' => 'absent', 'extra' => ''];
}

$registerRows = [];
foreach ($employees as $emp) {
	$row = ['employee' => $emp, 'days' => [], 'P' => 0, 'A' => 0, 'L' => 0, 'PL' => 0, 'H' => 0, 'WO' => 0];
	for ($d = 1; $d <= $daysInMonth; $d++) {
		$date = sprintf('%04d-%02d-%02d', $year, $month, $d);
		$st = getDayStatusForEmployee($emp, $date, $attendanceMap, $leaveMap, $holidayMap, $today);
		$row['days'][$d] = $st;
		if (isset($row[$st['code']])) $row[$st['code']]++;
	}
	$row['paid'] = $row['P'] + $row['L'] + $row['H'] + $row['WO'];
	$registerRows[] = $row;
}

$exportType = isset($_GET['export']) ? trim($_GET['export']) : '';
if ($exportType === 'all_csv') {
	$fileName = 'attendance-register-' . $year . '-' . sprintf('%02d', $month) . '.csv';
	header('Content-Type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $fileName . '"');
	$out = fopen('php://output', 'w');
	$header = ['Name', 'EmployeeNumber', 'Department', 'Division', 'State'];
	for ($d = 1; $d <= $daysInMonth; $d++) $header[] = 'D' . $d;
	$header = array_merge($header, ['P', 'A', 'L', 'PL', 'H', 'WO', 'Paid']);
	fputcsv($out, $header);
	foreach ($registerRows as $r) {
		$e = $r['employee'];
		$line = [$e['Name'] ?? '', $e['EmployeeNumber'] ?? '', $e['Department'] ?? '', $e['Division'] ?? '', $e['State'] ?? ''];
		for ($d = 1; $d <= $daysInMonth; $d++) $line[] = $r['days'][$d]['code'] ?? '';
		$line[] = intval($r['P']); $line[] = intval($r['A']); $line[] = intval($r['L']); $line[] = intval($r['PL']);
		$line[] = intval($r['H']); $line[] = intval($r['WO']); $line[] = intval($r['paid']);
		fputcsv($out, $line);
	}
	fclose($out);
	exit;
}

if ($exportType === 'individual_csv' && $employeeId > 0) {
	$selected = null;
	foreach ($registerRows as $r) {
		if (intval($r['employee']['ID']) === $employeeId) { $selected = $r; break; }
	}
	if ($selected) {
		$emp = $selected['employee'];
		$fileName = 'attendance-' . preg_replace('/[^A-Za-z0-9_-]/', '-', ($emp['EmployeeNumber'] ?? 'employee')) . '-' . $year . '-' . sprintf('%02d', $month) . '.csv';
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		$out = fopen('php://output', 'w');
		fputcsv($out, ['Date', 'Day', 'StatusCode', 'Status', 'Detail']);
		for ($d = 1; $d <= $daysInMonth; $d++) {
			$date = sprintf('%04d-%02d-%02d', $year, $month, $d);
			$st = $selected['days'][$d];
			fputcsv($out, [$date, date('l', strtotime($date)), $st['code'] ?? '', $st['label'] ?? '', $st['extra'] ?? '']);
		}
		fclose($out);
		exit;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Attendance Calendar</title>
	<?php include('../include/common-head.php'); ?>
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<style>
		.att-cell { min-width: 46px; text-align:center; font-size:12px; font-weight:700; }
		.att-head-day { min-width: 46px; text-align:center; font-size:11px; color:#6b7280; }
		.att-sticky-col { position: sticky; left: 0; background: #fff; z-index: 2; }
		.att-sticky-head { position: sticky; top: 0; z-index: 3; background: #f8fafc; }
		.present { background:#dcfce7 !important; color:#166534; }
		.absent { background:#fee2e2 !important; color:#991b1b; }
		.leave { background:#dbeafe !important; color:#1d4ed8; }
		.pending-leave { background:#ffedd5 !important; color:#9a3412; }
		.holiday { background:#f3e8ff !important; color:#6b21a8; }
		.weekly-off { background:#cffafe !important; color:#155e75; }
		.future { background:#f3f4f6 !important; color:#6b7280; }
		.legend-badge { display:inline-block; padding:4px 8px; border-radius:8px; font-size:12px; margin-right:6px; background:#f3f4f6; }
	</style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
	<?php include('../navigation/top-header.php'); ?>
	<?php include('../navigation/side-navigation.php'); ?>
	<main class="app-main">
		<div class="app-content-header">
			<div class="container-fluid">
				<h1 class="mb-1">Attendance Calendar</h1>
				<p class="text-muted mb-0">Monthly attendance tracker with present, absent, leave, public holidays and weekly-off status.</p>
			</div>
		</div>
		<div class="app-content">
			<div class="container-fluid">
				<div class="d-flex justify-content-between align-items-center mb-3">
					<div>
						<span class="legend-badge">P</span>
						<span class="legend-badge">A</span>
						<span class="legend-badge">L</span>
						<span class="legend-badge">PL</span>
						<span class="legend-badge">H</span>
						<span class="legend-badge">WO</span>
					</div>
					<button class="btn btn-primary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#attendanceFilterCanvas" aria-controls="attendanceFilterCanvas">
						<i class="bi bi-funnel-fill me-1"></i> Filters
					</button>
				</div>
				<div class="d-flex flex-wrap gap-2 mb-3">
					<a class="btn btn-outline-success btn-sm" href="?m=<?php echo $month; ?>&y=<?php echo $year; ?>&department=<?php echo urlencode($department); ?>&division=<?php echo urlencode($division); ?>&state=<?php echo urlencode($state); ?>&q=<?php echo urlencode($employeeQuery); ?>&employee_id=<?php echo intval($employeeId); ?>&export=all_csv">
						<i class="bi bi-download me-1"></i> Export Whole Register
					</a>
					<?php if ($employeeId > 0) { ?>
						<a class="btn btn-outline-primary btn-sm" href="?m=<?php echo $month; ?>&y=<?php echo $year; ?>&department=<?php echo urlencode($department); ?>&division=<?php echo urlencode($division); ?>&state=<?php echo urlencode($state); ?>&q=<?php echo urlencode($employeeQuery); ?>&employee_id=<?php echo intval($employeeId); ?>&export=individual_csv">
							<i class="bi bi-person-lines-fill me-1"></i> Export Individual
						</a>
					<?php } else { ?>
						<span class="badge text-bg-warning align-self-center">Select employee in filter for individual export</span>
					<?php } ?>
				</div>
				<div class="card mb-3">
					<div class="card-body py-2">
						<label class="form-label mb-1">Search in attendance table</label>
						<input type="text" id="attendanceRowSearch" class="form-control form-control-sm" placeholder="Type employee name or code">
					</div>
				</div>
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<strong>Monthly Attendance Register - <?php echo esc(date('F Y', strtotime($monthStart))); ?></strong>
						<span class="text-muted small">Employees: <?php echo count($registerRows); ?></span>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-bordered table-sm mb-0">
								<thead>
									<tr>
										<th class="att-sticky-head att-sticky-col">Employee</th>
										<th class="att-sticky-head">Code</th>
										<?php for ($d = 1; $d <= $daysInMonth; $d++) { ?>
											<th class="att-sticky-head att-head-day">
												<div><?php echo $d; ?></div>
												<div><?php echo esc(date('D', strtotime(sprintf('%04d-%02d-%02d', $year, $month, $d)))); ?></div>
											</th>
										<?php } ?>
										<th class="att-sticky-head text-end">P</th>
										<th class="att-sticky-head text-end">A</th>
										<th class="att-sticky-head text-end">L</th>
										<th class="att-sticky-head text-end">PL</th>
										<th class="att-sticky-head text-end">H</th>
										<th class="att-sticky-head text-end">WO</th>
										<th class="att-sticky-head text-end">Paid</th>
									</tr>
								</thead>
								<tbody id="attendanceTableBody">
								<?php if (empty($registerRows)) { ?>
									<tr><td colspan="<?php echo 2 + $daysInMonth + 7; ?>" class="text-center text-muted py-4">No employees for selected filters.</td></tr>
								<?php } else { foreach ($registerRows as $r) { $e = $r['employee']; ?>
									<tr>
										<td class="att-sticky-col"><?php echo esc($e['Name'] ?? ''); ?></td>
										<td><?php echo esc($e['EmployeeNumber'] ?? ''); ?></td>
										<?php for ($d = 1; $d <= $daysInMonth; $d++) {
											$st = $r['days'][$d];
											?>
											<td class="att-cell <?php echo esc($st['class']); ?>" title="<?php echo esc($st['label'] . ($st['extra'] ? ' - ' . $st['extra'] : '')); ?>">
												<?php echo esc($st['code']); ?>
											</td>
										<?php } ?>
										<td class="text-end"><?php echo intval($r['P']); ?></td>
										<td class="text-end"><?php echo intval($r['A']); ?></td>
										<td class="text-end"><?php echo intval($r['L']); ?></td>
										<td class="text-end"><?php echo intval($r['PL']); ?></td>
										<td class="text-end"><?php echo intval($r['H']); ?></td>
										<td class="text-end"><?php echo intval($r['WO']); ?></td>
										<td class="text-end"><?php echo intval($r['paid']); ?></td>
									</tr>
								<?php }} ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
</div>
<?php include('../include/common-footer.php'); ?>
<?php include('../include/common-script.php'); ?>

<div class="offcanvas offcanvas-end" tabindex="-1" id="attendanceFilterCanvas" aria-labelledby="attendanceFilterCanvasLabel">
	<div class="offcanvas-header">
		<h5 id="attendanceFilterCanvasLabel">Attendance Filters</h5>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body">
		<form method="get" class="row g-3">
			<div class="col-6">
				<label class="form-label">Month</label>
				<select name="m" class="form-select">
					<?php for ($i = 1; $i <= 12; $i++) {
						$sel = ($i === $month) ? ' selected' : '';
						echo '<option value="' . $i . '"' . $sel . '>' . date('F', mktime(0,0,0,$i,1)) . '</option>';
					} ?>
				</select>
			</div>
			<div class="col-6">
				<label class="form-label">Year</label>
				<input type="number" name="y" value="<?php echo $year; ?>" class="form-control" min="2000" max="2099">
			</div>
			<div class="col-12">
				<label class="form-label">Department</label>
				<select name="department" class="form-select">
					<option value="">All</option>
					<?php foreach ($departments as $d) {
						$val = $d['Department'] ?? '';
						$sel = ($val === $department) ? ' selected' : '';
						echo '<option value="' . esc($val) . '"' . $sel . '>' . esc($val) . '</option>';
					} ?>
				</select>
			</div>
			<div class="col-12">
				<label class="form-label">Branch/Division</label>
				<select name="division" class="form-select">
					<option value="">All</option>
					<?php foreach ($divisions as $d) {
						$val = $d['Division'] ?? '';
						$sel = ($val === $division) ? ' selected' : '';
						echo '<option value="' . esc($val) . '"' . $sel . '>' . esc($val) . '</option>';
					} ?>
				</select>
			</div>
			<div class="col-12">
				<label class="form-label">State</label>
				<select name="state" class="form-select">
					<option value="">All</option>
					<?php foreach ($states as $s) {
						$val = $s['State'] ?? '';
						$sel = ($val === $state) ? ' selected' : '';
						echo '<option value="' . esc($val) . '"' . $sel . '>' . esc($val) . '</option>';
					} ?>
				</select>
			</div>
			<div class="col-12">
				<label class="form-label">Employee (for single export)</label>
				<select name="employee_id" class="form-select">
					<option value="">All</option>
					<?php foreach ($employees as $emp) {
						$eid = intval($emp['ID']);
						$sel = ($eid === $employeeId) ? ' selected' : '';
						echo '<option value="' . $eid . '"' . $sel . '>' . esc(($emp['EmployeeNumber'] ?? '') . ' - ' . ($emp['Name'] ?? '')) . '</option>';
					} ?>
				</select>
			</div>
			<div class="col-12">
				<label class="form-label">Employee search</label>
				<input type="text" name="q" value="<?php echo esc($employeeQuery); ?>" class="form-control" placeholder="Name or code">
			</div>
			<div class="col-12 d-grid">
				<button class="btn btn-primary">Apply Filters</button>
			</div>
		</form>
	</div>
</div>

<script>
document.getElementById('attendanceRowSearch')?.addEventListener('input', function () {
	const q = this.value.toLowerCase().trim();
	document.querySelectorAll('#attendanceTableBody tr').forEach(function (row) {
		const name = (row.children[0]?.textContent || '').toLowerCase();
		const code = (row.children[1]?.textContent || '').toLowerCase();
		row.style.display = (!q || name.includes(q) || code.includes(q)) ? '' : 'none';
	});
});
</script>
</body>
</html>
