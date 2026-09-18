<?php
@session_start();
require_once('../include/autoloader.inc.php');
$conf = new Conf();
$_ProductName = $conf->_ProductName;
$_ProductLogo = $conf->_ProductLogo;

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$kpi = new Employeekpi($conn);

$periods = $kpi->listAvailablePeriods();
if (empty($periods)) {
	$y = intval(date('Y'));
	$m = intval(date('n'));
} else {
	$y = isset($_GET['y']) ? intval($_GET['y']) : $periods[0]['year'];
	$m = isset($_GET['m']) ? intval($_GET['m']) : $periods[0]['month'];
}
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

$rows     = $kpi->listSnapshots($y, $m, $filters);
$top5     = $kpi->getTopPerformers($y, $m, 5);
$bottom5  = $kpi->getLowestPerformers($y, $m, 5);
$summary  = $kpi->getMonthSummary($y, $m);
$monthTitle = date('F Y', mktime(0, 0, 0, $m, 1, $y));

$flashOk  = isset($_GET['ok'])  ? $_GET['ok']  : '';
$flashErr = isset($_GET['err']) ? $_GET['err'] : '';

$queryBase = http_build_query(['y' => $y, 'm' => $m] + $filters);

function kpi_h($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function kpi_pct($v) { return number_format(floatval($v), 1) . '%'; }
function kpi_money($v) { return '₹' . number_format(floatval($v), 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>HRMS KPI — Monthly snapshots</title>
	<?php include('../include/common-head.php'); ?>
	<style>
		.kpi-summary .stat-card { border-radius: 8px; }
		.kpi-summary .stat-card .stat-value { font-size: 1.4rem; font-weight: 600; }
		.kpi-summary .stat-card .stat-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; }
		.perf-list .perf-row { padding: 8px 12px; border-bottom: 1px solid #f1f3f5; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
		.perf-list .perf-row:last-child { border-bottom: 0; }
		.perf-list .perf-name { font-weight: 500; }
		.perf-list .perf-meta { font-size: 11px; color: #6c757d; }
		.perf-list .perf-pct  { font-weight: 700; min-width: 60px; text-align: right; }
		.perf-rank { display: inline-block; width: 22px; height: 22px; border-radius: 50%; background: #e9ecef; color: #495057; font-weight: 600; text-align: center; line-height: 22px; font-size: 12px; margin-right: 6px; }
		.perf-rank.gold   { background:#ffc107; color:#212529; }
		.perf-rank.silver { background:#adb5bd; color:#212529; }
		.perf-rank.bronze { background:#d49b6a; color:#fff; }
		.kpi-table th, .kpi-table td { vertical-align: middle; white-space: nowrap; }
		.kpi-table td.right { text-align: right; }
		.kpi-table .pct { font-variant-numeric: tabular-nums; }
		.editable-num { width: 70px; }
		.editable-pct { width: 70px; }
		.editable-money { width: 110px; }
	</style>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
	<?php include('../navigation/top-header.php'); ?>
	<?php include('../navigation/side-navigation.php'); ?>

	<main class="app-main">
		<div class="app-content-header">
			<div class="container-fluid">
				<h1 class="mb-1">Employee KPI snapshots</h1>
				<p class="text-muted mb-0">Review, edit and export monthly KPI saved from the dashboard tracker. Use the filters to drill down by performance band or role.</p>
			</div>
		</div>

		<div class="app-content">
			<div class="container-fluid">

				<?php if ($flashOk !== '') { ?>
					<div class="alert alert-success"><?php echo kpi_h($flashOk); ?></div>
				<?php } ?>
				<?php if ($flashErr !== '') { ?>
					<div class="alert alert-danger"><?php echo kpi_h($flashErr); ?></div>
				<?php } ?>

				<!-- Period + filters -->
				<div class="card mb-4">
					<div class="card-body">
						<form method="get" class="row g-3 align-items-end">
							<div class="col-md-3">
								<label class="form-label mb-1">Period</label>
								<select name="period" class="form-select" onchange="(function(s){ if(!s.value)return; var p=s.value.split('-'); var u=new URL(window.location); u.searchParams.set('y',p[0]); u.searchParams.set('m',p[1]); window.location=u.toString(); })(this)">
<?php if (empty($periods)) { ?>
									<option value="">No snapshots yet</option>
<?php } else {
	foreach ($periods as $p) {
		$key = $p['year'] . '-' . $p['month'];
		$sel = ($p['year'] === $y && $p['month'] === $m) ? ' selected' : '';
		echo '<option value="' . kpi_h($key) . '"' . $sel . '>' . kpi_h($p['label']) . ' (' . intval($p['rows']) . ')</option>';
	}
} ?>
								</select>
								<input type="hidden" name="y" value="<?php echo intval($y); ?>">
								<input type="hidden" name="m" value="<?php echo intval($m); ?>">
							</div>
							<div class="col-md-3">
								<label class="form-label mb-1">Search</label>
								<input type="text" name="search" class="form-control" placeholder="Name / designation / phone" value="<?php echo kpi_h($filters['search']); ?>">
							</div>
							<div class="col-md-2">
								<label class="form-label mb-1">Role</label>
								<select name="role" class="form-select">
									<option value="">All</option>
									<option value="executive"     <?php echo $filters['role'] === 'executive'     ? 'selected' : ''; ?>>Executive only</option>
									<option value="non_executive" <?php echo $filters['role'] === 'non_executive' ? 'selected' : ''; ?>>Non-executive only</option>
								</select>
							</div>
							<div class="col-md-2">
								<label class="form-label mb-1">Overall KPI</label>
								<div class="input-group">
									<input type="number" step="0.1" min="0" max="100" name="min_overall" class="form-control" placeholder="min" value="<?php echo kpi_h($filters['min_overall']); ?>">
									<input type="number" step="0.1" min="0" max="100" name="max_overall" class="form-control" placeholder="max" value="<?php echo kpi_h($filters['max_overall']); ?>">
								</div>
							</div>
							<div class="col-md-2">
								<label class="form-label mb-1">WhatsApp</label>
								<select name="whatsapp" class="form-select">
									<option value="">Any</option>
									<option value="sent"    <?php echo $filters['whatsapp'] === 'sent'    ? 'selected' : ''; ?>>Sent</option>
									<option value="pending" <?php echo $filters['whatsapp'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
								</select>
							</div>
							<div class="col-md-12 d-flex gap-2">
								<button type="submit" class="btn btn-primary">Apply filters</button>
								<a class="btn btn-outline-secondary" href="view-kpi.php?y=<?php echo intval($y); ?>&m=<?php echo intval($m); ?>">Reset</a>
								<a class="btn btn-success ms-auto" href="action/export-kpi-csv.php?<?php echo kpi_h($queryBase); ?>" target="_blank">
									<i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
								</a>
							</div>
						</form>
					</div>
				</div>

				<!-- Summary cards -->
				<div class="row g-3 kpi-summary mb-4">
					<div class="col-md-3 col-sm-6">
						<div class="card stat-card h-100"><div class="card-body">
							<div class="stat-label">Snapshots — <?php echo kpi_h($monthTitle); ?></div>
							<div class="stat-value text-primary"><?php echo intval($summary['total_rows']); ?></div>
							<div class="small text-muted"><?php echo intval($summary['executives']); ?> exec · <?php echo intval($summary['non_executives']); ?> non-exec</div>
						</div></div>
					</div>
					<div class="col-md-3 col-sm-6">
						<div class="card stat-card h-100"><div class="card-body">
							<div class="stat-label">Avg overall KPI</div>
							<div class="stat-value"><?php echo kpi_pct($summary['avg_overall']); ?></div>
							<div class="small text-muted">Max <?php echo kpi_pct($summary['max_overall']); ?> · Min <?php echo kpi_pct($summary['min_overall']); ?></div>
						</div></div>
					</div>
					<div class="col-md-3 col-sm-6">
						<div class="card stat-card h-100"><div class="card-body">
							<div class="stat-label">Performance bands</div>
							<div class="stat-value" style="font-size:.95rem; line-height:1.3;">
								<span class="badge bg-success">Excellent <?php echo intval($summary['rows_excellent']); ?></span>
								<span class="badge bg-primary">Good <?php echo intval($summary['rows_good']); ?></span><br>
								<span class="badge bg-warning text-dark">Avg <?php echo intval($summary['rows_average']); ?></span>
								<span class="badge bg-danger">Needs imp. <?php echo intval($summary['rows_needs_imp']); ?></span>
							</div>
						</div></div>
					</div>
					<div class="col-md-3 col-sm-6">
						<div class="card stat-card h-100"><div class="card-body">
							<div class="stat-label">Payroll (this month)</div>
							<div class="stat-value text-success"><?php echo kpi_money($summary['sum_payable']); ?></div>
							<div class="small text-muted">Full <?php echo kpi_money($summary['sum_full_salary']); ?> · KPI deduction <?php echo kpi_money($summary['kpi_deduction']); ?></div>
						</div></div>
					</div>
				</div>

				<!-- Top / bottom performers -->
				<div class="row g-3 mb-4">
					<div class="col-md-6">
						<div class="card h-100">
							<div class="card-header bg-success-subtle d-flex justify-content-between align-items-center">
								<strong><i class="bi bi-trophy-fill text-success"></i> Top 5 performers — <?php echo kpi_h($monthTitle); ?></strong>
								<span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><?php echo count($top5); ?></span>
							</div>
							<div class="card-body p-0">
								<div class="perf-list">
<?php if (empty($top5)) { ?>
									<div class="perf-row text-muted">No KPI data for this month yet.</div>
<?php } else {
	$rank = 0;
	foreach ($top5 as $p) {
		$rank++;
		$rankCls = $rank === 1 ? 'gold' : ($rank === 2 ? 'silver' : ($rank === 3 ? 'bronze' : ''));
		?>
									<div class="perf-row">
										<div>
											<span class="perf-rank <?php echo $rankCls; ?>"><?php echo $rank; ?></span>
											<span class="perf-name"><?php echo kpi_h($p['EmployeeName']); ?></span>
											<div class="perf-meta">
												<?php echo kpi_h($p['Designation'] ?: ''); ?>
												<?php if (!empty($p['IsExecutive'])) { ?><span class="badge bg-light text-dark ms-1">Executive</span><?php } ?>
												· Att <?php echo kpi_pct($p['AttendancePerformancePct']); ?>
											</div>
										</div>
										<div class="perf-pct text-success"><?php echo kpi_pct($p['OverallKpiPct']); ?></div>
									</div>
		<?php
	}
} ?>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="card h-100">
							<div class="card-header bg-danger-subtle d-flex justify-content-between align-items-center">
								<strong><i class="bi bi-exclamation-triangle-fill text-danger"></i> Bottom 5 performers — <?php echo kpi_h($monthTitle); ?></strong>
								<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle"><?php echo count($bottom5); ?></span>
							</div>
							<div class="card-body p-0">
								<div class="perf-list">
<?php if (empty($bottom5)) { ?>
									<div class="perf-row text-muted">No KPI data for this month yet.</div>
<?php } else {
	$rank = 0;
	foreach ($bottom5 as $p) {
		$rank++;
		?>
									<div class="perf-row">
										<div>
											<span class="perf-rank"><?php echo $rank; ?></span>
											<span class="perf-name"><?php echo kpi_h($p['EmployeeName']); ?></span>
											<div class="perf-meta">
												<?php echo kpi_h($p['Designation'] ?: ''); ?>
												<?php if (!empty($p['IsExecutive'])) { ?><span class="badge bg-light text-dark ms-1">Executive</span><?php } ?>
												· Att <?php echo kpi_pct($p['AttendancePerformancePct']); ?>
												· Absent <?php echo intval($p['AbsentDays']); ?>d
											</div>
										</div>
										<div class="perf-pct text-danger"><?php echo kpi_pct($p['OverallKpiPct']); ?></div>
									</div>
		<?php
	}
} ?>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Full table -->
				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<strong>All snapshots — <?php echo kpi_h($monthTitle); ?> (<?php echo count($rows); ?> rows)</strong>
						<span class="text-muted small">Tip: click <em>Edit</em> on any row to correct numbers.</span>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-sm table-striped mb-0 kpi-table">
								<thead class="table-light">
									<tr>
										<th>#</th>
										<th>Employee</th>
										<th>Designation</th>
										<th>Role</th>
										<th class="right">Assign %</th>
										<th class="right">Quote %</th>
										<th class="right">Closed %</th>
										<th class="right">Att %</th>
										<th class="right">Overall</th>
										<th>Status</th>
										<th class="right">Days (P/A/W)</th>
										<th class="right">Full</th>
										<th class="right">Payable</th>
										<th class="right">WA</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
<?php if (empty($rows)) { ?>
									<tr><td colspan="15" class="text-center text-muted py-4">No KPI snapshots found for the selected period / filters.</td></tr>
<?php } else {
	$i = 0;
	foreach ($rows as $r) {
		$i++;
		$status = $kpi->statusForPct($r['OverallKpiPct']);
		?>
									<tr data-snapshot-id="<?php echo intval($r['ID']); ?>">
										<td><?php echo $i; ?></td>
										<td>
											<div><strong><?php echo kpi_h($r['EmployeeName']); ?></strong></div>
											<div class="small text-muted"><?php echo kpi_h($r['ContactNumber']); ?></div>
										</td>
										<td><?php echo kpi_h($r['Designation']); ?></td>
										<td>
											<?php if (!empty($r['IsExecutive'])) { ?>
												<span class="badge bg-info text-dark">Executive</span>
											<?php } else { ?>
												<span class="badge bg-light text-dark">Non-exec</span>
											<?php } ?>
										</td>
										<td class="right pct"><?php echo kpi_pct($r['AssignPerformancePct']); ?></td>
										<td class="right pct"><?php echo kpi_pct($r['QuotePerformancePct']); ?></td>
										<td class="right pct"><?php echo kpi_pct($r['ClosedPerformancePct']); ?></td>
										<td class="right pct"><?php echo kpi_pct($r['AttendancePerformancePct']); ?></td>
										<td class="right pct"><strong><?php echo kpi_pct($r['OverallKpiPct']); ?></strong></td>
										<td><span class="badge <?php echo $status['badge']; ?>"><?php echo kpi_h($status['label']); ?></span></td>
										<td class="right small">
											<?php echo intval($r['PresentDays']); ?> / <?php echo intval($r['AbsentDays']); ?> / <?php echo intval($r['WorkingDays']); ?>
										</td>
										<td class="right"><?php echo kpi_money($r['FullSalary']); ?></td>
										<td class="right text-success"><strong><?php echo kpi_money($r['PayableSalary']); ?></strong></td>
										<td class="right">
											<?php if (!empty($r['WhatsappSent'])) { ?>
												<span class="badge bg-success">Sent</span>
											<?php } else { ?>
												<span class="badge bg-secondary">—</span>
											<?php } ?>
										</td>
										<td class="text-nowrap">
											<button type="button" class="btn btn-sm btn-outline-primary btn-kpi-edit"
												data-snapshot='<?php echo kpi_h(json_encode($r, JSON_UNESCAPED_UNICODE)); ?>'>
												Edit
											</button>
											<button type="button" class="btn btn-sm btn-outline-danger btn-kpi-delete"
												data-snapshot-id="<?php echo intval($r['ID']); ?>"
												data-emp-name="<?php echo kpi_h($r['EmployeeName']); ?>">
												Delete
											</button>
										</td>
									</tr>
		<?php
	}
} ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>

			</div>
		</div>
	</main>
</div>

<!-- Edit modal -->
<div class="modal fade" id="kpiEditModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content">
			<form id="kpiEditForm" action="action/save-kpi-action.php" method="post">
				<input type="hidden" name="id" id="editId">
				<input type="hidden" name="return_y" value="<?php echo intval($y); ?>">
				<input type="hidden" name="return_m" value="<?php echo intval($m); ?>">
				<input type="hidden" name="return_qs" value="<?php echo kpi_h($queryBase); ?>">
				<div class="modal-header">
					<h5 class="modal-title">
						Edit KPI snapshot — <span id="editEmpName" class="text-muted"></span>
						<span class="badge bg-light text-dark ms-2" id="editRoleBadge" style="display:none;">Executive</span>
					</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>
				<div class="modal-body">

					<!-- Tabs keep every section above the fold without endless scrolling -->
					<ul class="nav nav-tabs mb-3" role="tablist">
						<li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-outcome"    role="tab">Outcome <span class="text-success">★</span></button></li>
						<li class="nav-item"><button type="button" class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-attendance" role="tab">Attendance</button></li>
						<li class="nav-item"><button type="button" class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-assign"     role="tab">Assignment</button></li>
						<li class="nav-item"><button type="button" class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-quote"      role="tab">Quotation</button></li>
						<li class="nav-item"><button type="button" class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-closed"     role="tab">Closed</button></li>
						<li class="nav-item"><button type="button" class="nav-link"        data-bs-toggle="tab" data-bs-target="#tab-identity"   role="tab">Identity</button></li>
					</ul>

					<div class="tab-content">

						<!-- Outcome -->
						<div class="tab-pane fade show active" id="tab-outcome" role="tabpanel">
							<div class="row g-3">
								<div class="col-md-3">
									<label class="form-label">Overall KPI %</label>
									<input type="number" step="0.01" min="0" max="100" name="OverallKpiPct" id="ed_OverallKpiPct" class="form-control form-control-lg">
								</div>
								<div class="col-md-3">
									<label class="form-label">Attendance %</label>
									<input type="number" step="0.01" min="0" max="100" name="AttendancePerformancePct" id="ed_AttendancePerformancePct" class="form-control form-control-lg">
								</div>
								<div class="col-md-3">
									<label class="form-label">Salary %</label>
									<input type="number" step="0.01" min="0" max="100" name="SalaryPct" id="ed_SalaryPct" class="form-control form-control-lg">
								</div>
								<div class="col-md-3 d-flex align-items-end">
									<button type="button" class="btn btn-outline-secondary w-100" id="btn_kpi_recompute">
										Auto compute overall %
									</button>
								</div>
								<div class="col-md-6">
									<label class="form-label">Full salary (₹)</label>
									<input type="number" step="0.01" min="0" name="FullSalary" id="ed_FullSalary" class="form-control">
								</div>
								<div class="col-md-6">
									<label class="form-label">Payable salary (₹)</label>
									<input type="number" step="0.01" min="0" name="PayableSalary" id="ed_PayableSalary" class="form-control">
								</div>
								<div class="col-12">
									<div id="outcomeHint" class="alert alert-light border small mb-0"></div>
								</div>
							</div>
						</div>

						<!-- Attendance -->
						<div class="tab-pane fade" id="tab-attendance" role="tabpanel">
							<div class="row g-3">
								<div class="col-md-3"><label class="form-label">Working days</label><input type="number" min="0" name="WorkingDays" id="ed_WorkingDays" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Present days</label><input type="number" min="0" name="PresentDays" id="ed_PresentDays" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Absent days</label><input type="number" min="0" name="AbsentDays" id="ed_AbsentDays" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Attendance %</label><input type="number" step="0.01" min="0" max="100" name="AttendancePerformancePct" id="ed_AttendancePerformancePct_b" class="form-control" disabled></div>
							</div>
							<p class="small text-muted mt-2 mb-0">The Attendance % field on the Outcome tab is the master value used in payroll. The disabled mirror here is just a reference.</p>
						</div>

						<!-- Assignment -->
						<div class="tab-pane fade" id="tab-assign" role="tabpanel">
							<div class="row g-3">
								<div class="col-md-3"><label class="form-label">Total tickets</label><input type="number" min="0" name="AssignTotalTickets" id="ed_AssignTotalTickets" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">≤ 1Hr</label><input type="number" min="0" name="AssignWithin1Hour" id="ed_AssignWithin1Hour" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">&gt; 1Hr</label><input type="number" min="0" name="AssignAfter1Hour" id="ed_AssignAfter1Hour" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Reassign count</label><input type="number" min="0" name="AssignReassignCount" id="ed_AssignReassignCount" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Assignment %</label><input type="number" step="0.01" min="0" max="100" name="AssignPerformancePct" id="ed_AssignPerformancePct" class="form-control"></div>
							</div>
						</div>

						<!-- Quotation -->
						<div class="tab-pane fade" id="tab-quote" role="tabpanel">
							<div class="row g-3">
								<div class="col-md-3"><label class="form-label">Total quotations</label><input type="number" min="0" name="QuoteTotalTickets" id="ed_QuoteTotalTickets" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">≤ 48Hr</label><input type="number" min="0" name="QuoteWithin48Hour" id="ed_QuoteWithin48Hour" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">&gt; 48Hr</label><input type="number" min="0" name="QuoteAfter48Hour" id="ed_QuoteAfter48Hour" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Pending</label><input type="number" min="0" name="QuotePending" id="ed_QuotePending" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Not approved (48Hr)</label><input type="number" min="0" name="QuoteNotApproved48" id="ed_QuoteNotApproved48" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">AMC tickets</label><input type="number" min="0" name="QuoteAmcTickets" id="ed_QuoteAmcTickets" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Quote %</label><input type="number" step="0.01" min="0" max="100" name="QuotePerformancePct" id="ed_QuotePerformancePct" class="form-control"></div>
							</div>
						</div>

						<!-- Closed -->
						<div class="tab-pane fade" id="tab-closed" role="tabpanel">
							<div class="row g-3">
								<div class="col-md-3"><label class="form-label">Total closed</label><input type="number" min="0" name="ClosedTotal" id="ed_ClosedTotal" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">≤ 24Hr</label><input type="number" min="0" name="ClosedWithin24" id="ed_ClosedWithin24" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">&gt; 24Hr</label><input type="number" min="0" name="ClosedAfter24" id="ed_ClosedAfter24" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Closing target</label><input type="number" min="0" name="ClosingTarget" id="ed_ClosingTarget" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Closed %</label><input type="number" step="0.01" min="0" max="100" name="ClosedPerformancePct" id="ed_ClosedPerformancePct" class="form-control"></div>
							</div>
						</div>

						<!-- Identity -->
						<div class="tab-pane fade" id="tab-identity" role="tabpanel">
							<div class="row g-3">
								<div class="col-md-4"><label class="form-label">Employee name</label><input type="text" name="EmployeeName" id="ed_EmployeeName" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Designation</label><input type="text" name="Designation" id="ed_Designation" class="form-control"></div>
								<div class="col-md-2"><label class="form-label">Contact</label><input type="text" name="ContactNumber" id="ed_ContactNumber" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Role</label>
									<select name="IsExecutive" id="ed_IsExecutive" class="form-select">
										<option value="0">Non-executive</option>
										<option value="1">Executive</option>
									</select>
								</div>
								<div class="col-md-3"><label class="form-label">Date From</label><input type="date" name="DateFrom" id="ed_DateFrom" class="form-control"></div>
								<div class="col-md-3"><label class="form-label">Date To</label><input type="date" name="DateTo" id="ed_DateTo" class="form-control"></div>
							</div>
						</div>
					</div>

					<p class="text-muted small mt-3 mb-0">Performance % values are clamped server-side to 0–100. Money is non-negative. The disabled mirror fields are read-only previews.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-primary">Save changes</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php include('../include/common-footer.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
	function fmtMoney(v) {
		var n = parseFloat(v || 0);
		if (isNaN(n)) n = 0;
		return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}

	function refreshOutcomeHint() {
		var full = parseFloat($('#ed_FullSalary').val()) || 0;
		var pay  = parseFloat($('#ed_PayableSalary').val()) || 0;
		var ded  = Math.max(0, full - pay);
		var overall = parseFloat($('#ed_OverallKpiPct').val()) || 0;
		var label = overall >= 90 ? 'Excellent' : (overall >= 75 ? 'Good' : (overall >= 60 ? 'Average' : (overall > 0 ? 'Needs Improvement' : 'No Data')));
		$('#outcomeHint').html(
			'<strong>Status:</strong> ' + label
			+ ' &nbsp;|&nbsp; <strong>Full:</strong> ' + fmtMoney(full)
			+ ' &nbsp;|&nbsp; <strong>KPI deduction:</strong> ' + fmtMoney(ded)
			+ ' &nbsp;|&nbsp; <strong>Payable:</strong> ' + fmtMoney(pay)
		);
	}

	function syncAttendanceMirror() {
		$('#ed_AttendancePerformancePct_b').val($('#ed_AttendancePerformancePct').val());
	}

	$(document)
		.on('input change', '#ed_FullSalary, #ed_PayableSalary, #ed_OverallKpiPct', refreshOutcomeHint)
		.on('input change', '#ed_AttendancePerformancePct', syncAttendanceMirror);

	$('#btn_kpi_recompute').on('click', function () {
		// Weighted recompute matching how the dashboard computes Overall KPI:
		//   Non-exec: avg(Assign, Quote, Closed, Attendance)
		//   Exec:     Attendance% (or Salary% if Attendance% is 0)
		var isExec = $('#ed_IsExecutive').val() === '1';
		var att = parseFloat($('#ed_AttendancePerformancePct').val()) || 0;
		var sal = parseFloat($('#ed_SalaryPct').val()) || 0;
		var overall;
		if (isExec) {
			overall = att > 0 ? att : sal;
		} else {
			var assign = parseFloat($('#ed_AssignPerformancePct').val()) || 0;
			var quote  = parseFloat($('#ed_QuotePerformancePct').val())  || 0;
			var closed = parseFloat($('#ed_ClosedPerformancePct').val()) || 0;
			overall = (assign + quote + closed + att) / 4;
		}
		overall = Math.max(0, Math.min(100, overall));
		$('#ed_OverallKpiPct').val(overall.toFixed(2));
		refreshOutcomeHint();
	});

$(function () {
	$('.btn-kpi-edit').on('click', function () {
		var snap;
		try { snap = JSON.parse(this.getAttribute('data-snapshot') || '{}'); } catch (e) { snap = {}; }
		$('#editId').val(snap.ID || '');
		$('#editEmpName').text(snap.EmployeeName || '');

		var isExec = parseInt(snap.IsExecutive, 10) ? true : false;
		$('#editRoleBadge').toggle(isExec).text(isExec ? 'Executive' : '').removeClass('bg-light bg-info').addClass(isExec ? 'bg-info text-dark' : 'bg-light text-dark');

		$('#ed_EmployeeName').val(snap.EmployeeName || '');
		$('#ed_Designation').val(snap.Designation || '');
		$('#ed_ContactNumber').val(snap.ContactNumber || '');
		$('#ed_IsExecutive').val(isExec ? '1' : '0');
		$('#ed_DateFrom').val((snap.DateFrom || '').substring(0, 10));
		$('#ed_DateTo').val((snap.DateTo || '').substring(0, 10));
		[
			'AssignTotalTickets', 'AssignWithin1Hour', 'AssignAfter1Hour', 'AssignReassignCount', 'AssignPerformancePct',
			'QuoteTotalTickets', 'QuoteWithin48Hour', 'QuoteAfter48Hour', 'QuotePending', 'QuoteNotApproved48', 'QuoteAmcTickets', 'QuotePerformancePct',
			'ClosedTotal', 'ClosedWithin24', 'ClosedAfter24', 'ClosingTarget', 'ClosedPerformancePct',
			'WorkingDays', 'PresentDays', 'AbsentDays', 'AttendancePerformancePct',
			'OverallKpiPct', 'FullSalary', 'PayableSalary', 'SalaryPct'
		].forEach(function (k) {
			var v = snap[k];
			if (v === null || typeof v === 'undefined') v = 0;
			$('#ed_' + k).val(v);
		});

		// Always reset to the Outcome tab so the most important numbers are first.
		var firstTabBtn = document.querySelector('[data-bs-target="#tab-outcome"]');
		if (firstTabBtn && typeof bootstrap !== 'undefined') {
			(new bootstrap.Tab(firstTabBtn)).show();
		}
		syncAttendanceMirror();
		refreshOutcomeHint();

		var modal = new bootstrap.Modal(document.getElementById('kpiEditModal'));
		modal.show();
	});

	$('.btn-kpi-delete').on('click', function () {
		var id   = this.getAttribute('data-snapshot-id');
		var name = this.getAttribute('data-emp-name') || 'this snapshot';
		if (!id) return;
		if (!confirm('Delete the KPI snapshot for ' + name + '?\nThis cannot be undone.')) return;
		var form = $('<form>', { method: 'POST', action: 'action/delete-kpi-action.php' });
		form.append($('<input>', { type: 'hidden', name: 'id', value: id }));
		form.append($('<input>', { type: 'hidden', name: 'return_y', value: <?php echo intval($y); ?> }));
		form.append($('<input>', { type: 'hidden', name: 'return_m', value: <?php echo intval($m); ?> }));
		form.append($('<input>', { type: 'hidden', name: 'return_qs', value: '<?php echo kpi_h($queryBase); ?>' }));
		$('body').append(form);
		form.submit();
	});
});
</script>
</body>
</html>
