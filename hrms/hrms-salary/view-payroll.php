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

$core = new Core();
$salary = new Salarypayroll($conn);

$y = isset($_GET['y']) ? intval($_GET['y']) : intval(date('Y'));
$m = isset($_GET['m']) ? intval($_GET['m']) : intval(date('n'));
if ($m < 1 || $m > 12) {
	$m = intval(date('n'));
}

$employeeSlipRows = $salary->listEmployeesWithSlipSummary($y, $m);
$slipGeneratedCount = 0;
$slipPendingCount = 0;
foreach ($employeeSlipRows as $slipRow) {
	if (!empty($slipRow['SalarySlipID'])) {
		$slipGeneratedCount++;
	} else {
		$slipPendingCount++;
	}
}
$employees = $core->_getTableRecords($conn, 'employees', 'WHERE IFNULL(IsActive,1)=1 ORDER BY Name ASC');
$quoteCompanies = $salary->listQuoteCompanies();

$flashOk = isset($_GET['ok']) ? $_GET['ok'] : '';
$flashErr = isset($_GET['err']) ? $_GET['err'] : '';
$latestSlipId = isset($_GET['latest_slip_id']) ? intval($_GET['latest_slip_id']) : 0;
$latestSlipRev = '';
if ($latestSlipId > 0) {
	foreach ($employeeSlipRows as $slipRow) {
		if (intval($slipRow['SalarySlipID'] ?? 0) === $latestSlipId) {
			$latestSlipRev = trim(($slipRow['CreatedDate'] ?? '') . ' ' . ($slipRow['CreatedTime'] ?? ''));
			break;
		}
	}
}

$lastPayrollOptions = isset($_SESSION['hrms_last_payroll_options']) && is_array($_SESSION['hrms_last_payroll_options'])
	? $salary->normalizePaidDaysOptions($_SESSION['hrms_last_payroll_options'])
	: $salary->normalizePaidDaysOptions([]);
$lastPayrollEmployeeId = isset($_SESSION['hrms_last_payroll_employee_id']) ? intval($_SESSION['hrms_last_payroll_employee_id']) : 0;

$payrollCheckboxChecked = static function ($key) use ($lastPayrollOptions) {
	return !empty($lastPayrollOptions[$key]) ? ' checked' : '';
};
$payrollNumberValue = static function ($key, $default) use ($lastPayrollOptions) {
	return isset($lastPayrollOptions[$key]) ? htmlspecialchars((string) $lastPayrollOptions[$key], ENT_QUOTES, 'UTF-8') : htmlspecialchars((string) $default, ENT_QUOTES, 'UTF-8');
};
$payrollMonthHint = $salary->explainPayrollMonthContext($y, $m);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>HRMS Payroll — Salary slips</title>
	<?php include('../include/common-head.php'); ?>
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<style>
		.select2-container .select2-selection--single {
			height: 42px !important;
			padding-top: 6px;
			border-color: #dee2e6;
		}
		.select2-container .select2-selection--single .select2-selection__arrow {
			height: 40px !important;
		}
		.payroll-gen-card .card-header {
			background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
			color: #fff;
			border-bottom: 0;
		}
		.payroll-step {
			background: #f8f9fa;
			border: 1px solid #e9ecef;
			border-radius: .5rem;
			padding: 1rem 1.25rem;
			margin-bottom: 1rem;
		}
		.payroll-step-title {
			font-size: .8rem;
			font-weight: 700;
			letter-spacing: .04em;
			text-transform: uppercase;
			color: #6c757d;
			margin-bottom: .75rem;
		}
		.payroll-rule-group {
			background: #fff;
			border: 1px solid #e9ecef;
			border-radius: .5rem;
			padding: 1rem;
			height: 100%;
		}
		.payroll-rule-group-title {
			font-weight: 600;
			font-size: .95rem;
			margin-bottom: .75rem;
			padding-bottom: .5rem;
			border-bottom: 1px solid #eee;
		}
		.payroll-rule-item {
			padding: .65rem 0;
			border-bottom: 1px dashed #eee;
		}
		.payroll-rule-item:last-child {
			border-bottom: 0;
			padding-bottom: 0;
		}
		.payroll-rule-item .form-check-label {
			cursor: pointer;
		}
		.payroll-action-bar {
			background: #fff;
			border: 1px solid #e9ecef;
			border-radius: .5rem;
			padding: 1rem 1.25rem;
		}
		.payroll-legend {
			font-size: .8rem;
			background: #fff;
			border: 1px solid #e9ecef;
			border-radius: .375rem;
			padding: .5rem .75rem;
		}
		.slip-actions .btn {
			padding: .2rem .45rem;
			font-size: .78rem;
		}
		#slipsTable thead th {
			font-size: .85rem;
			white-space: nowrap;
		}
		.slip-status-pending td {
			color: #6c757d;
		}
		.statutory-card .card-header {
			background: #fff3cd;
			border-bottom: 1px solid #ffecb5;
		}
		.payroll-leave-balance-card {
			font-size: .82rem;
		}
		.payroll-leave-balance-card .balance-type-title {
			font-weight: 600;
		}
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
				<h1 class="mb-1">Payroll &amp; salary slips</h1>
				<p class="text-muted mb-0">Generate slips per employee, manage statutory flags, and send or download slip PDFs.</p>
			</div>
		</div>

		<div class="app-content">
			<div class="container-fluid">

				<?php if ($flashOk !== '') { ?>
					<div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-1"></i><?php echo htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8'); ?></div>
				<?php } ?>
				<?php if ($flashErr !== '') { ?>
					<div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-triangle me-1"></i><?php echo htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8'); ?></div>
				<?php } ?>

				<div class="card mb-4 payroll-gen-card shadow-sm">
					<div class="card-header py-3">
						<div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
							<div>
								<strong class="fs-5">Generate salary slip</strong>
								<div class="small opacity-75">Select employee, set rules, preview attendance, then generate</div>
							</div>
						</div>
					</div>
					<div class="card-body">
						<form id="formGenerateSingleSlip" action="action/generate-payroll-action.php" method="post">
							<input type="hidden" name="return_y" value="<?php echo $y; ?>">
							<input type="hidden" name="return_m" value="<?php echo $m; ?>">

							<div class="payroll-step">
								<div class="payroll-step-title">Step 1 — Employee &amp; period</div>
								<div class="row g-3 align-items-end">
									<div class="col-lg-5">
										<label class="form-label fw-medium" for="employeeSelectSingle">Employee</label>
										<select name="employee_id" id="employeeSelectSingle" class="form-select" required>
											<option value="">Search by code or name…</option>
<?php foreach ($employees as $empOpt) {
	$woLabel = trim($empOpt['WeeklyOff'] ?? '');
	if ($woLabel === '') {
		$woLabel = 'Sunday';
	}
?>
											<option value="<?php echo intval($empOpt['ID']); ?>" data-weekly-off="<?php echo htmlspecialchars($woLabel, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($lastPayrollEmployeeId === intval($empOpt['ID'])) ? ' selected' : ''; ?>>
												<?php echo htmlspecialchars(($empOpt['EmployeeNumber'] ?? '') . ' - ' . ($empOpt['Name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
											</option>
<?php } ?>
										</select>
									</div>
									<div class="col-6 col-md-3 col-lg-2">
										<label class="form-label fw-medium" for="payrollMonthSelect">Month</label>
										<select name="month" id="payrollMonthSelect" class="form-select">
<?php for ($i = 1; $i <= 12; $i++) {
	$sel = ($i === $m) ? ' selected' : '';
	echo '<option value="' . $i . '"' . $sel . '>' . date('F', mktime(0, 0, 0, $i, 1)) . '</option>';
} ?>
										</select>
									</div>
									<div class="col-6 col-md-3 col-lg-2">
										<label class="form-label fw-medium" for="payrollYearInput">Year</label>
										<input type="number" name="year" id="payrollYearInput" class="form-control" value="<?php echo $y; ?>" min="2000" max="2099">
									</div>
									<div class="col-lg-3">
										<label class="form-label fw-medium d-none d-lg-block">&nbsp;</label>
										<button type="button" class="btn btn-outline-primary w-100" id="btnPayrollPreviewSummary" disabled>
											View attendance &amp; leave
										</button>
									</div>
								</div>
								<?php if ($payrollMonthHint !== '') { ?>
								<div class="alert alert-warning py-2 px-3 mb-0 mt-3 small" id="payrollMonthHint"><?php echo htmlspecialchars($payrollMonthHint, ENT_QUOTES, 'UTF-8'); ?></div>
								<?php } ?>
							</div>

							<div class="payroll-step">
								<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
									<div class="payroll-step-title mb-0">Step 2 — Payroll rules</div>
									<span class="badge text-bg-light border">All ON by default except profile weekly off</span>
								</div>
								<div class="row g-3">
									<div class="col-lg-4">
										<div class="payroll-rule-group">
											<div class="payroll-rule-group-title">Weekly offs</div>
											<div class="payroll-rule-item">
												<div class="form-check form-switch">
													<input type="hidden" name="include_profile_weekly_off" value="0">
													<input type="checkbox" name="include_profile_weekly_off" id="includeProfileWeeklyOff" value="1" class="form-check-input payroll-rule-checkbox"<?php echo $payrollCheckboxChecked('include_profile_weekly_off'); ?>>
													<label class="form-check-label" for="includeProfileWeeklyOff">Profile weekly off</label>
												</div>
												<p class="text-muted small mb-0 mt-1">Paid days on employee's configured off-day (e.g. Sunday).</p>
												<p class="text-primary small mb-0 mt-1" id="employeeWeeklyOffHint" style="display:none;"></p>
											</div>
											<div class="payroll-rule-item">
												<div class="form-check form-switch">
													<input type="hidden" name="include_fixed_weekly_offs" value="0">
													<input type="checkbox" name="include_fixed_weekly_offs" id="includeFixedWeeklyOffs" value="1" class="form-check-input payroll-rule-checkbox"<?php echo $payrollCheckboxChecked('include_fixed_weekly_offs'); ?>>
													<label class="form-check-label" for="includeFixedWeeklyOffs">Fixed weekly offs</label>
												</div>
												<div class="d-flex align-items-center gap-2 mt-2" id="fixedWeeklyOffRow">
													<label class="small mb-0" for="fixedWeeklyOffsInput">Days / month</label>
													<input type="number" name="fixed_weekly_offs" id="fixedWeeklyOffsInput" class="form-control form-control-sm" value="<?php echo $payrollNumberValue('fixed_weekly_offs', 4); ?>" min="1" max="6" style="width:4.5rem;">
												</div>
											</div>
										</div>
									</div>
									<div class="col-lg-4">
										<div class="payroll-rule-group">
											<div class="payroll-rule-group-title">Attendance</div>
											<div class="payroll-rule-item">
												<div class="form-check form-switch">
													<input type="hidden" name="include_approved_attendance" value="0">
													<input type="checkbox" name="include_approved_attendance" id="includeApprovedAttendance" value="1" class="form-check-input payroll-rule-checkbox"<?php echo $payrollCheckboxChecked('include_approved_attendance'); ?>>
													<label class="form-check-label" for="includeApprovedAttendance">HR approved only</label>
												</div>
												<p class="text-muted small mb-0 mt-1">Only <code>ApprovalStatus = Approved</code> counts.</p>
											</div>
											<div class="payroll-rule-item">
												<div class="form-check form-switch">
													<input type="hidden" name="include_checkout_attendance" value="0">
													<input type="checkbox" name="include_checkout_attendance" id="includeCheckoutAttendance" value="1" class="form-check-input payroll-rule-checkbox"<?php echo $payrollCheckboxChecked('include_checkout_attendance'); ?>>
													<label class="form-check-label" for="includeCheckoutAttendance">Checkout required</label>
												</div>
												<p class="text-muted small mb-0 mt-1">Needs <code>OutTime</code> recorded.</p>
											</div>
											<div class="payroll-rule-item">
												<div class="form-check form-switch">
													<input type="hidden" name="include_min_hours_rule" value="0">
													<input type="checkbox" name="include_min_hours_rule" id="includeMinHoursRule" value="1" class="form-check-input payroll-rule-checkbox"<?php echo $payrollCheckboxChecked('include_min_hours_rule'); ?>>
													<label class="form-check-label" for="includeMinHoursRule">Minimum hours</label>
												</div>
												<div class="d-flex align-items-center gap-2 mt-2" id="minHoursRow">
													<input type="number" name="min_work_hours" id="minWorkHoursInput" class="form-control form-control-sm" value="<?php echo $payrollNumberValue('min_work_hours', 9); ?>" min="1" max="24" step="0.5" style="width:4.5rem;">
													<span class="text-muted small">hours / day</span>
												</div>
											</div>
											<div class="payroll-rule-item">
												<div class="form-check form-switch">
													<input type="hidden" name="include_half_day_deduction" value="0">
													<input type="checkbox" name="include_half_day_deduction" id="includeHalfDayDeduction" value="1" class="form-check-input payroll-rule-checkbox"<?php echo $payrollCheckboxChecked('include_half_day_deduction'); ?>>
													<label class="form-check-label" for="includeHalfDayDeduction">Half-day rule</label>
												</div>
												<p class="text-muted small mb-0 mt-1">HD/HDO = 0.5 day · P = 1 day · A = absent</p>
											</div>
										</div>
									</div>
									<div class="col-lg-4">
										<div class="payroll-rule-group">
											<div class="payroll-rule-group-title">Leave &amp; joining</div>
											<div class="payroll-rule-item">
												<div class="form-check form-switch">
													<input type="hidden" name="include_deduct_non_cl_sl_leave" value="0">
													<input type="checkbox" name="include_deduct_non_cl_sl_leave" id="includeDeductNonClSlLeave" value="1" class="form-check-input payroll-rule-checkbox"<?php echo $payrollCheckboxChecked('include_deduct_non_cl_sl_leave'); ?>>
													<label class="form-check-label" for="includeDeductNonClSlLeave">Only CL &amp; SL paid</label>
												</div>
												<p class="text-muted small mb-0 mt-1">PL, LOP, etc. reduce paid days.</p>
											</div>
											<div class="payroll-rule-item">
												<div class="form-check form-switch">
													<input type="hidden" name="include_prorate_joining_date" value="0">
													<input type="checkbox" name="include_prorate_joining_date" id="includeProrateJoiningDate" value="1" class="form-check-input payroll-rule-checkbox"<?php echo $payrollCheckboxChecked('include_prorate_joining_date'); ?>>
													<label class="form-check-label" for="includeProrateJoiningDate">Mid-month joiner proration</label>
												</div>
												<p class="text-muted small mb-0 mt-1">Count paid days from <code>DateofJoining</code>.</p>
											</div>
											<div class="payroll-legend mt-3">
												<strong>Work codes:</strong>
												<span class="ms-1"><b>P</b> full</span> ·
												<span><b>HDO</b> 4–7.5h</span> ·
												<span><b>HD</b> 2–4h</span> ·
												<span><b>A</b> absent / no checkout</span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="payroll-action-bar">
								<div class="payroll-step-title">Step 3 — Generate</div>
								<div class="row g-3 align-items-center">
									<div class="col-md-6">
										<div class="form-check form-switch mb-2">
											<input type="checkbox" name="overwrite" id="overwrite_single" value="1" class="form-check-input" checked>
											<label class="form-check-label" for="overwrite_single">Overwrite existing slip for this month</label>
										</div>
										<div class="form-check form-switch mb-0">
											<input type="checkbox" name="include_kpi" id="include_kpi" value="1" class="form-check-input">
											<label class="form-check-label" for="include_kpi">Include KPI performance (refresh snapshot)</label>
										</div>
										<p class="text-muted small mb-0 mt-2">Tip: use <strong>View attendance &amp; leave</strong> above to verify paid days before generating.</p>
									</div>
									<div class="col-md-6 text-md-end">
										<button type="button" class="btn btn-outline-primary me-2 mb-2 mb-md-0 d-md-none" id="btnPayrollPreviewSummaryMobile" disabled>Preview</button>
										<button type="submit" class="btn btn-primary btn-lg px-4" id="btnGenerateSingleSlip">Generate salary slip</button>
									</div>
								</div>
								<div class="col-12 d-none mt-3" id="kpiSlipLoading">
									<div class="alert alert-info mb-0 py-2">
										<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
										Refreshing KPI and generating slip… This may take 30–60 seconds.
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
				<div class="card mb-4 statutory-card shadow-sm">
					<div class="card-header py-2"><strong>EPF / ESI settings</strong> <span class="text-muted small fw-normal">— per employee</span></div>
					<div class="card-body">
						<form action="action/save-employee-statutory-action.php" method="post" class="row g-3 align-items-end">
							<input type="hidden" name="return_y" value="<?php echo $y; ?>">
							<input type="hidden" name="return_m" value="<?php echo $m; ?>">
							<div class="col-md-4">
								<label class="form-label">Employee</label>
								<select name="employee_id" id="employeeStatutorySelect" class="form-select" required>
									<option value="">Select employee</option>
<?php foreach ($employees as $empOpt) { ?>
									<option value="<?php echo intval($empOpt['ID']); ?>" data-epf="<?php echo intval($empOpt['ApplyEPF'] ?? 1); ?>" data-esi="<?php echo intval($empOpt['ApplyESI'] ?? 1); ?>">
										<?php echo htmlspecialchars(($empOpt['EmployeeNumber'] ?? '') . ' - ' . ($empOpt['Name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
									</option>
<?php } ?>
								</select>
							</div>
							<div class="col-auto form-check form-switch mt-4">
								<input type="checkbox" name="apply_epf" id="applyEPFBox" value="1" class="form-check-input" checked>
								<label class="form-check-label" for="applyEPFBox">Apply EPF</label>
							</div>
							<div class="col-auto form-check form-switch mt-4">
								<input type="checkbox" name="apply_esi" id="applyESIBox" value="1" class="form-check-input" checked>
								<label class="form-check-label" for="applyESIBox">Apply ESI</label>
							</div>
							<div class="col-auto">
								<button type="submit" class="btn btn-warning">Save statutory settings</button>
							</div>
						</form>
					</div>
				</div>

				<div class="card shadow-sm">
					<div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-2">
						<div>
							<strong>Salary slips — <?php echo htmlspecialchars(date('F', mktime(0, 0, 0, $m, 1)) . ' ' . $y, ENT_QUOTES, 'UTF-8'); ?></strong>
							<div class="small text-muted mt-1">
								<span class="text-success fw-medium"><?php echo $slipGeneratedCount; ?> generated</span>
								<?php if ($slipPendingCount > 0) { ?>
									· <span><?php echo $slipPendingCount; ?> pending</span>
								<?php } ?>
								· <?php echo count($employeeSlipRows); ?> active employees
							</div>
						</div>
						<form method="get" class="row g-2 align-items-center">
							<div class="col-auto">
								<select name="m" class="form-select form-select-sm"><?php for ($i = 1; $i <= 12; $i++) {
	$sel = ($i === $m) ? ' selected' : '';
	echo '<option value="' . $i . '"' . $sel . '>' . date('M', mktime(0, 0, 0, $i, 1)) . '</option>';
} ?></select>
							</div>
							<div class="col-auto">
								<input type="number" name="y" class="form-control form-control-sm" value="<?php echo $y; ?>">
							</div>
							<div class="col-auto">
								<button type="submit" class="btn btn-sm btn-outline-secondary">Go</button>
							</div>
						</form>
					</div>
					<div class="card-body border-bottom">
						<div class="row g-2 align-items-end">
							<div class="col-md-4">
								<label class="form-label mb-1">Search employee</label>
								<input type="text" id="slipEmployeeSearch" class="form-control form-control-sm" placeholder="Name or code">
							</div>
							<div class="col-md-4">
								<label class="form-label mb-1">Show</label>
								<select id="slipStatusFilter" class="form-select form-select-sm">
									<option value="generated" selected>Generated only</option>
									<option value="all">All employees</option>
									<option value="pending">Pending only</option>
								</select>
							</div>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-striped mb-0" id="slipsTable">
								<thead>
									<tr>
										<th>Status</th>
										<th>Employee</th>
										<th>Code</th>
										<th class="text-end">Paid days</th>
										<th class="text-end">Gross</th>
										<th class="text-end">Deductions</th>
										<th class="text-end">Net</th>
										<th>Generated</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
<?php if (empty($employeeSlipRows)) { ?>
									<tr><td colspan="9" class="text-center text-muted py-4">No active employees found.</td></tr>
<?php } else {
	foreach ($employeeSlipRows as $row) {
		$hasSlip = !empty($row['SalarySlipID']);
		$slipId = intval($row['SalarySlipID'] ?? 0);
		$isLatest = ($latestSlipId > 0 && $slipId === $latestSlipId);
		$slipRev = trim(($row['CreatedDate'] ?? '') . ' ' . ($row['CreatedTime'] ?? ''));
		$generatedLabel = $hasSlip ? trim(($row['CreatedDate'] ?? '') . ' ' . ($row['CreatedTime'] ?? '')) : '—';
		$rowStatus = $hasSlip ? 'generated' : 'pending';
		?>
									<tr id="slip-row-<?php echo $slipId > 0 ? $slipId : ('emp-' . intval($row['ID'])); ?>" class="slip-filter-row<?php echo $hasSlip ? '' : ' slip-status-pending'; ?><?php echo $isLatest ? ' table-success fw-semibold' : ''; ?>" data-slip-status="<?php echo $rowStatus; ?>"<?php echo $isLatest ? ' data-latest-slip="1"' : ''; ?><?php echo !$hasSlip ? ' style="display:none;"' : ''; ?>>
										<td>
											<?php if ($hasSlip) { ?>
												<span class="badge text-bg-success">Generated</span><?php if ($isLatest) { ?> <span class="badge text-bg-primary ms-1">Latest</span><?php } ?>
											<?php } else { ?>
												<span class="badge text-bg-secondary">Pending</span>
											<?php } ?>
										</td>
										<td><?php echo htmlspecialchars($row['Name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($row['EmployeeNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="text-end"><?php echo $hasSlip ? rtrim(rtrim(number_format(floatval($row['PaidDays']), 2, '.', ''), '0'), '.') . ' / ' . intval($row['TotalDays']) : '—'; ?></td>
										<td class="text-end"><?php echo $hasSlip ? number_format(floatval($row['GrossSalary']), 2) : '—'; ?></td>
										<td class="text-end"><?php echo $hasSlip ? number_format(floatval($row['TotalDeductions']), 2) : '—'; ?></td>
										<td class="text-end"><?php echo $hasSlip ? number_format(floatval($row['NetSalary']), 2) : '—'; ?></td>
										<td class="text-nowrap small"><?php echo $hasSlip ? htmlspecialchars($generatedLabel, ENT_QUOTES, 'UTF-8') : '—'; ?></td>
										<td class="text-nowrap slip-actions">
											<?php if ($hasSlip) { ?>
											<div class="btn-group" role="group">
												<button type="button" class="btn btn-sm btn-outline-primary btn-slip-pdf" data-slip-id="<?php echo $slipId; ?>" data-slip-rev="<?php echo htmlspecialchars($slipRev, ENT_QUOTES, 'UTF-8'); ?>" data-action="preview" title="PDF preview">Preview</button>
												<button type="button" class="btn btn-sm btn-primary btn-slip-pdf" data-slip-id="<?php echo $slipId; ?>" data-slip-rev="<?php echo htmlspecialchars($slipRev, ENT_QUOTES, 'UTF-8'); ?>" data-action="download" title="Download PDF">PDF</button>
												<button type="button" class="btn btn-sm btn-success btn-slip-send" data-slip-id="<?php echo $slipId; ?>" data-email="<?php echo htmlspecialchars($row['Email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-phone="<?php echo htmlspecialchars($row['ContactNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo (trim($row['Email'] ?? '') === '' && trim($row['ContactNumber'] ?? '') === '') ? 'Add email/phone in employee profile' : 'Send slip'; ?>">Send</button>
											</div>
											<?php } else { ?>
											<button type="button" class="btn btn-sm btn-outline-secondary btn-select-for-generate" data-employee-id="<?php echo intval($row['ID']); ?>">Generate</button>
											<?php } ?>
										</td>
									</tr>
		<?php
	}
?>
									<tr id="slipTableEmptyRow" style="display:none;"><td colspan="9" class="text-center text-muted py-4"><?php if ($slipGeneratedCount === 0) { ?>No salary slips generated for <?php echo htmlspecialchars(date('F Y', mktime(0, 0, 0, $m, 1, $y)), ENT_QUOTES, 'UTF-8'); ?> yet. Use <strong>Pending only</strong> to see who is left, or generate from the form above.<?php } else { ?>No employees match this filter.<?php } ?></td></tr>
<?php } ?>
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
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php if ($latestSlipId > 0) { ?>
<div class="modal fade" id="latestSlipModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Latest salary slip ready</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				Salary generated successfully. You can open, print, or download this latest slip directly.
			</div>
			<div class="modal-footer flex-wrap">
				<button type="button" class="btn btn-outline-primary btn-slip-pdf" data-slip-id="<?php echo $latestSlipId; ?>" data-slip-rev="<?php echo htmlspecialchars($latestSlipRev, ENT_QUOTES, 'UTF-8'); ?>" data-action="preview">PDF Preview</button>
				<button type="button" class="btn btn-primary btn-slip-pdf" data-slip-id="<?php echo $latestSlipId; ?>" data-slip-rev="<?php echo htmlspecialchars($latestSlipRev, ENT_QUOTES, 'UTF-8'); ?>" data-action="download">Download PDF</button>
				<button type="button" class="btn btn-success btn-slip-send" data-slip-id="<?php echo $latestSlipId; ?>" data-email="" data-phone="">Send</button>
			</div>
		</div>
	</div>
</div>
<?php } ?>
<div class="modal fade" id="payrollPreviewModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="payrollPreviewModalTitle">Attendance &amp; leave summary</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body" id="payrollPreviewModalBody">
				<div class="text-center text-muted py-4">Select an employee and click <strong>View attendance &amp; leave</strong>.</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="companySlipModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Select company for salary slip</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<label class="form-label" for="slipCompanySelect">Company (letterhead on PDF)</label>
				<select id="slipCompanySelect" class="form-select" required>
					<option value="">— Choose company —</option>
<?php foreach ($quoteCompanies as $qc) { ?>
					<option value="<?php echo intval($qc['ID']); ?>">
						<?php echo htmlspecialchars($qc['CompanyName'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
					</option>
<?php } ?>
				</select>
<?php if (empty($quoteCompanies)) { ?>
				<p class="text-danger small mt-2 mb-0">No active companies in quote_company_details. Add a company first.</p>
<?php } ?>
			</div>
			<div class="modal-footer flex-wrap">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-outline-primary" id="slipPdfPreviewBtn">PDF Preview</button>
				<button type="button" class="btn btn-primary" id="slipPdfDownloadBtn">Download PDF</button>
				<button type="button" class="btn btn-success" id="slipSendBtn">Send (Email &amp; WhatsApp)</button>
			</div>
			<div id="slipSendStatus" class="px-3 pb-3 small text-muted" style="display:none;"></div>
		</div>
	</div>
</div>
<script>
$(function () {
	if ($('#employeeSelectSingle').length) {
		$('#employeeSelectSingle').select2({
			width: '100%',
			placeholder: 'Search employee by code or name',
			allowClear: true
		});
		$('#employeeSelectSingle').on('select2:open', function() {
			document.querySelector('.select2-search__field').focus();
		});
	}
	if ($('#employeeStatutorySelect').length) {
		$('#employeeStatutorySelect').select2({
			width: '100%',
			placeholder: 'Select employee',
			allowClear: true
		});
	}
	function syncStatutoryBoxes() {
		const statSelect = document.getElementById('employeeStatutorySelect');
		const epfBox = document.getElementById('applyEPFBox');
		const esiBox = document.getElementById('applyESIBox');
		if (!statSelect || !epfBox || !esiBox) return;
		const selectedOption = statSelect.options[statSelect.selectedIndex];
		if (!selectedOption || !selectedOption.value) {
			epfBox.checked = true;
			esiBox.checked = true;
			return;
		}
		epfBox.checked = (selectedOption.getAttribute('data-epf') || '1') === '1';
		esiBox.checked = (selectedOption.getAttribute('data-esi') || '1') === '1';
	}
	$('#employeeStatutorySelect').on('change', syncStatutoryBoxes);
	syncStatutoryBoxes();

	function syncPayrollRulesUi() {
		$('#fixedWeeklyOffRow').css('display', $('#includeFixedWeeklyOffs').is(':checked') ? '' : 'none');
		$('#minHoursRow').css('display', $('#includeMinHoursRule').is(':checked') ? 'flex' : 'none');

		const sel = document.getElementById('employeeSelectSingle');
		const hint = document.getElementById('employeeWeeklyOffHint');
		if (!sel || !hint) return;
		const opt = sel.options[sel.selectedIndex];
		if (!opt || !opt.value || !$('#includeProfileWeeklyOff').is(':checked')) {
			hint.style.display = 'none';
			return;
		}
		const wo = opt.getAttribute('data-weekly-off') || 'Sunday';
		hint.textContent = 'Profile weekly off for this employee: ' + wo;
		hint.style.display = 'block';
	}
	$('#includeFixedWeeklyOffs, #includeMinHoursRule, #includeProfileWeeklyOff').on('change', syncPayrollRulesUi);
	$('#employeeSelectSingle').on('change', syncPayrollRulesUi);
	syncPayrollRulesUi();

	function syncPayrollPreviewButton() {
		const empId = $('#employeeSelectSingle').val();
		const enabled = !!empId;
		$('#btnPayrollPreviewSummary').prop('disabled', !enabled);
		$('#btnPayrollPreviewSummaryMobile').prop('disabled', !enabled);
	}
	$('#employeeSelectSingle').on('change', syncPayrollPreviewButton);
	syncPayrollPreviewButton();
	$('#btnPayrollPreviewSummaryMobile').on('click', function () {
		$('#btnPayrollPreviewSummary').trigger('click');
	});

	function collectPayrollPreviewParams() {
		const params = new URLSearchParams();
		params.set('employee_id', $('#employeeSelectSingle').val() || '');
		params.set('year', $('#payrollYearInput').val() || '');
		params.set('month', $('#payrollMonthSelect').val() || '');
		const ruleFields = [
			'include_profile_weekly_off',
			'include_fixed_weekly_offs',
			'include_approved_attendance',
			'include_checkout_attendance',
			'include_min_hours_rule',
			'include_deduct_non_cl_sl_leave',
			'include_half_day_deduction',
			'include_prorate_joining_date'
		];
		ruleFields.forEach(function (name) {
			params.set(name, $('#formGenerateSingleSlip input[type=checkbox][name="' + name + '"]').is(':checked') ? '1' : '0');
		});
		params.set('fixed_weekly_offs', $('#fixedWeeklyOffsInput').val() || '4');
		params.set('min_work_hours', $('#minWorkHoursInput').val() || '9');
		return params;
	}

	function fmtNum(n) {
		const x = parseFloat(n);
		if (isNaN(x)) return '0';
		return (Math.round(x * 100) / 100).toString();
	}

	function renderLeaveBalanceCard(lb) {
		if (!lb) return '';
		const fy = lb.financial_year || {};
		const pol = lb.policy || {};
		const monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
		const calLabel = (monthNames[lb.calendar_month] || '') + ' ' + (lb.calendar_year || '');
		let html = '<div class="card payroll-leave-balance-card mb-3 border-info">';
		html += '<div class="card-header py-2 bg-info-subtle"><strong>Leave balance</strong> <span class="text-muted fw-normal">— as of ' + (lb.as_of || calLabel) + '</span></div>';
		html += '<div class="card-body py-2">';
		html += '<div class="row g-2 mb-2 small">';
		html += '<div class="col-md-6"><span class="text-muted">Financial year:</span> <strong>' + (fy.label || '—') + '</strong>';
		if (fy.start && fy.end) {
			html += ' <span class="text-muted">(' + fy.start + ' → ' + fy.end + ')</span>';
		}
		html += '</div>';
		html += '<div class="col-md-6"><span class="text-muted">Carry forward:</span> <strong>' + (pol.carry_forward_note || '—') + '</strong></div>';
		html += '<div class="col-12 text-muted">Monthly quota: CL ' + fmtNum(pol.cl_monthly_quota) + ' · SL ' + fmtNum(pol.sl_monthly_quota)
			+ ' · FY cap: CL ' + fmtNum(pol.cl_annual_quota) + ' / SL ' + fmtNum(pol.sl_annual_quota);
		if (pol.advance_leave_enabled) {
			html += ' · Advance up to ' + fmtNum(pol.advance_leave_max_days) + ' day(s)/type/year';
		}
		html += '</div></div>';
		html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0">';
		html += '<thead class="table-light"><tr><th>Type</th><th class="text-end">Month quota</th><th class="text-end">+ Carry</th><th class="text-end">Pool</th><th class="text-end">Used (month)</th><th class="text-end">Pending</th><th class="text-end">Available</th><th class="text-end">FY used / cap</th></tr></thead><tbody>';
		['CL', 'SL'].forEach(function (type) {
			const row = lb[type.toLowerCase()] || {};
			html += '<tr>';
			html += '<td class="balance-type-title">' + type + '</td>';
			html += '<td class="text-end">' + fmtNum(row.monthly_quota) + '</td>';
			html += '<td class="text-end">' + fmtNum(row.carried_in) + '</td>';
			html += '<td class="text-end">' + fmtNum(row.pool) + '</td>';
			html += '<td class="text-end">' + fmtNum(row.used_this_month) + '</td>';
			html += '<td class="text-end">' + fmtNum(row.pending) + '</td>';
			html += '<td class="text-end fw-semibold text-primary">' + fmtNum(row.available) + '</td>';
			html += '<td class="text-end">' + fmtNum(row.fy_used) + ' / ' + fmtNum(row.fy_quota) + ' <span class="text-muted">(' + fmtNum(row.fy_remaining) + ' left)</span></td>';
			html += '</tr>';
		});
		html += '</tbody></table></div>';
		const co = lb.comp_off || {};
		if (co.enabled) {
			html += '<div class="alert alert-success py-2 small mb-0 mt-2"><strong>Comp-off bank:</strong> ' + fmtNum(co.available) + ' day(s) available';
			html += ' <span class="text-muted">(valid ' + (co.validity_days || 90) + ' days per credit';
			if (co.requires_approval) {
				html += ' · HR approval required when employee requests';
			}
			html += ')</span></div>';
		}
		html += '<div class="small text-muted mt-2">CL/SL: month quota + carry − used − pending (FY cap). Comp-off: separate bank from working on weekly off/holiday.</div>';
		html += '</div></div>';
		return html;
	}

	function renderCompOffSection(data) {
		const co = data.comp_off;
		if (!co || !co.enabled) {
			return '<p class="text-muted small mb-3">Comp-off is disabled in leave policy.</p>';
		}
		const eligible = (data.days || []).filter(function (d) { return d.comp_off_eligible; });
		let html = '<div class="card mb-3 border-success payroll-leave-balance-card">';
		html += '<div class="card-header py-2 bg-success-subtle"><strong>Comp-off (worked on weekly off / holiday)</strong>';
		html += ' <span class="badge text-bg-success ms-1">' + fmtNum(co.available) + ' available</span></div>';
		html += '<div class="card-body py-2">';
		html += '<p class="small text-muted mb-2">Step 1: Grant comp-off when employee punched in on a weekly off or public holiday. Step 2: Set leave type to <strong>Comp-off</strong> when they take off — balance is consumed automatically.</p>';

		if (eligible.length) {
			html += '<div class="mb-2"><span class="small fw-medium">Eligible work days this month (no comp-off yet):</span> ';
			eligible.forEach(function (d) {
				html += '<button type="button" class="btn btn-sm btn-outline-success me-1 mb-1 payroll-compoff-grant-quick" data-work-date="' + d.date + '">' + d.date + '</button>';
			});
			html += '</div>';
		}

		html += '<div class="row g-2 align-items-end mb-3">';
		html += '<div class="col-md-3"><label class="form-label small mb-0">Work date</label><input type="date" class="form-control form-control-sm" id="payrollCompOffWorkDate"></div>';
		html += '<div class="col-md-2"><label class="form-label small mb-0">Credit days</label><select class="form-select form-select-sm" id="payrollCompOffCreditDays"><option value="1">1</option><option value="0.5">0.5</option></select></div>';
		html += '<div class="col-md-4"><label class="form-label small mb-0">Reason</label><input type="text" class="form-control form-control-sm" id="payrollCompOffReason" value="Worked on weekly off / holiday"></div>';
		html += '<div class="col-md-3"><button type="button" class="btn btn-sm btn-success w-100" id="btnPayrollGrantCompOff">Grant comp-off</button></div>';
		html += '</div>';
		html += '<div id="payrollCompOffMessage" class="small mb-2" style="display:none;"></div>';

		const credits = co.credits || [];
		html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead class="table-light"><tr><th>Work date</th><th>Credit</th><th>Status</th><th>Expires</th><th>Used for leave</th><th></th></tr></thead><tbody>';
		if (!credits.length) {
			html += '<tr><td colspan="6" class="text-muted text-center">No comp-off credits yet.</td></tr>';
		} else {
			credits.forEach(function (c) {
				const st = (c.status || '').toLowerCase();
				let badge = 'secondary';
				if (st === 'approved') badge = 'success';
				else if (st === 'pending') badge = 'warning';
				else if (st === 'used') badge = 'info';
				else if (st === 'expired' || st === 'rejected') badge = 'danger';
				html += '<tr>';
				html += '<td>' + c.work_date + (c.in_payroll_month ? '' : ' <span class="text-muted">(other month)</span>') + '</td>';
				html += '<td>' + fmtNum(c.credit_days) + '</td>';
				html += '<td><span class="badge text-bg-' + badge + '">' + (c.status || '—') + '</span></td>';
				html += '<td>' + (c.expires_at || '—') + '</td>';
				html += '<td>' + (c.used_leave_id > 0 ? '#' + c.used_leave_id : '—') + '</td>';
				html += '<td>';
				if (st === 'pending') {
					html += '<button type="button" class="btn btn-xs btn-success btn-sm payroll-compoff-approve" data-id="' + c.id + '">Approve</button> ';
					html += '<button type="button" class="btn btn-xs btn-outline-danger btn-sm payroll-compoff-reject" data-id="' + c.id + '">Reject</button>';
				} else {
					html += '—';
				}
				html += '</td></tr>';
			});
		}
		html += '</tbody></table></div>';
		html += '</div></div>';
		return html;
	}

	function renderPayrollPreview(data) {
		const s = data.summary || {};
		const emp = data.employee || {};
		const period = data.period || {};
		let html = '';
		html += '<div class="mb-3">';
		html += '<div class="fw-semibold">' + (emp.code ? emp.code + ' — ' : '') + (emp.name || '') + '</div>';
		html += '<div class="text-muted small">' + (period.month_label || '') + ' · Weekly off: ' + (emp.weekly_off || 'Sunday');
		if (emp.joining_date) {
			html += ' · Joined: ' + emp.joining_date;
		}
		html += ' · Counted through ' + (period.count_through || '') + '</div>';
		html += '<div class="text-muted small mt-1">Uses current payroll rule checkboxes on this form.</div>';
		html += '</div>';

		html += '<div class="row g-2 mb-3">';
		const cards = [
			['Present', s.present_days],
			['Paid leave (CL)', s.leave_cl],
			['Paid leave (SL)', s.leave_sl],
			['Other paid leave', s.leave_other],
			['Unpaid leave', s.leave_unpaid],
			['Holidays', s.holiday_days],
			['Weekly off', s.weekly_off_days],
			['Total paid days', s.paid_days]
		];
		cards.forEach(function (card) {
			html += '<div class="col-6 col-md-3"><div class="border rounded p-2 h-100"><div class="small text-muted">' + card[0] + '</div><div class="fw-semibold">' + fmtNum(card[1]) + '</div></div></div>';
		});
		html += '</div>';
		if (s.breakdown) {
			html += '<div class="alert alert-light border py-2 small mb-3">Paid-day breakdown: <strong>' + s.breakdown + '</strong></div>';
		}

		if (data.leave_balance) {
			html += renderLeaveBalanceCard(data.leave_balance);
		}
		html += renderCompOffSection(data);

		if ((data.leave_records || []).length) {
			html += '<h6 class="mb-2">Approved leave in this month</h6>';
			html += '<p class="text-muted small mb-2">If the employee did not choose CL, SL, Comp-off, or Unpaid Leave, HR can set the type here. Balance is checked and updated automatically.</p>';
			html += '<div class="table-responsive mb-3"><table class="table table-sm table-bordered mb-0" id="payrollLeaveRecordsTable"><thead><tr><th>Type</th><th>From</th><th>To</th><th>Duration</th><th>Days</th><th>Payroll</th><th></th></tr></thead><tbody>';
			data.leave_records.forEach(function (lv) {
				const rowClass = lv.needs_type ? 'table-warning' : '';
				let typeCell = lv.type || '—';
				if (lv.editable) {
					const cur = (lv.type === 'CL' || lv.type === 'SL' || lv.type === 'COMPOFF' || lv.type === 'UNPAID') ? lv.type : '';
					typeCell = '<select class="form-select form-select-sm payroll-leave-type-select" data-leave-id="' + lv.id + '" data-old-type="' + (lv.type || '') + '">';
					typeCell += '<option value=""' + (cur === '' ? ' selected' : '') + '>— Select —</option>';
					typeCell += '<option value="CL"' + (cur === 'CL' ? ' selected' : '') + '>CL</option>';
					typeCell += '<option value="SL"' + (cur === 'SL' ? ' selected' : '') + '>SL</option>';
					typeCell += '<option value="COMPOFF"' + (cur === 'COMPOFF' ? ' selected' : '') + '>Comp-off</option>';
					typeCell += '<option value="UNPAID"' + (cur === 'UNPAID' ? ' selected' : '') + '>Unpaid (LWP)</option>';
					typeCell += '</select>';
				}
				const daysLabel = fmtNum(lv.leave_days || lv.days_in_month);
				const payrollCell = lv.paid_for_payroll
					? '<span class="text-success">Paid</span>'
					: '<span class="text-danger">Unpaid</span>';
				let actionCell = '';
				if (lv.editable) {
					actionCell = '<button type="button" class="btn btn-sm btn-outline-primary payroll-leave-type-save" data-leave-id="' + lv.id + '" disabled>Apply</button>';
				}
				html += '<tr class="' + rowClass + '" data-leave-id="' + lv.id + '">';
				html += '<td>' + typeCell + '</td>';
				html += '<td>' + lv.from_date + '</td>';
				html += '<td>' + lv.to_date + '</td>';
				html += '<td>' + (lv.duration || '—') + '</td>';
				html += '<td>' + daysLabel + (lv.days_in_month !== lv.leave_days ? ' <span class="text-muted">(' + fmtNum(lv.days_in_month) + ' in month)</span>' : '') + '</td>';
				html += '<td class="payroll-leave-paid-cell">' + payrollCell + '</td>';
				html += '<td class="payroll-leave-action-cell">' + actionCell + '</td>';
				html += '</tr>';
			});
			html += '</tbody></table></div>';
			html += '<div id="payrollLeaveTypeMessage" class="small mb-3" style="display:none;"></div>';
		} else {
			html += '<p class="text-muted small mb-3">No approved leave records in this month.</p>';
		}

		html += '<h6 class="mb-2">Day-wise attendance &amp; leave</h6>';
		html += '<div class="payroll-legend mb-2"><strong>Work:</strong> <b>P</b> full day (7.5h+) · <b>HDO</b> half (4–7.5h) · <b>HD</b> half (2–4h) · <b>A</b> absent or no checkout</div>';
		html += '<div class="table-responsive" style="max-height:420px;overflow:auto;"><table class="table table-sm table-striped table-bordered mb-0"><thead class="table-light sticky-top"><tr><th>Date</th><th>Day</th><th>In</th><th>Out</th><th>Approval</th><th>Work</th><th>Leave</th><th>Status</th><th class="text-end">Credit</th><th>Note</th></tr></thead><tbody>';
		(data.days || []).forEach(function (day) {
			let rowClass = '';
			if (day.period_state === 'future') rowClass = 'table-secondary';
			else if (day.period_state === 'before_join') rowClass = 'table-secondary';
			else if (day.payroll_credit > 0) rowClass = 'table-success';
			else if (day.attendance && !day.attendance.qualifies) rowClass = 'table-warning';
			else if (day.leave && day.leave.paid_for_payroll === false) rowClass = 'table-warning';

			const att = day.attendance || {};
			const leave = day.leave || {};
			let leaveCell = '—';
			if (day.holiday) leaveCell = 'Holiday';
			else if (leave.type) leaveCell = leave.type + (leave.fraction < 1 ? ' (0.5)' : '');

			html += '<tr class="' + rowClass + '">';
			html += '<td>' + day.date + '</td>';
			html += '<td>' + day.weekday + '</td>';
			html += '<td>' + (att.in_time || '—') + '</td>';
			html += '<td>' + (att.out_time || '—') + '</td>';
			html += '<td>' + (att.approval_status || '—') + '</td>';
			html += '<td>' + (att.work_status || '—') + '</td>';
			html += '<td>' + leaveCell + '</td>';
			html += '<td>' + (day.status || '') + '</td>';
			html += '<td class="text-end">' + (day.payroll_credit > 0 ? fmtNum(day.payroll_credit) : '—') + '</td>';
			html += '<td class="small text-muted">' + (day.note || '') + '</td>';
			html += '</tr>';
		});
		html += '</tbody></table></div>';
		return html;
	}

	const payrollPreviewModalEl = document.getElementById('payrollPreviewModal');
	let payrollPreviewModal = null;
	let lastPayrollPreviewData = null;
	if (payrollPreviewModalEl && typeof bootstrap !== 'undefined') {
		payrollPreviewModal = new bootstrap.Modal(payrollPreviewModalEl);
	}

	function reloadPayrollPreview() {
		$('#btnPayrollPreviewSummary').trigger('click');
	}

	$(document).on('change', '.payroll-leave-type-select', function () {
		const $row = $(this).closest('tr');
		const oldType = String($(this).attr('data-old-type') || '');
		const newType = String($(this).val() || '');
		const $btn = $row.find('.payroll-leave-type-save');
		const changed = newType !== '' && newType !== oldType;
		$btn.prop('disabled', !changed);
		const days = parseFloat($row.find('td').eq(4).text()) || 1;
		const lb = lastPayrollPreviewData && lastPayrollPreviewData.leave_balance;
		if (changed && lb && newType) {
			if (newType === 'COMPOFF') {
				const coAvail = parseFloat((lb.comp_off && lb.comp_off.available) || (lastPayrollPreviewData.comp_off && lastPayrollPreviewData.comp_off.available)) || 0;
				if (days > coAvail) {
					$btn.attr('title', 'Comp-off available: ' + coAvail + ' (need ' + days + '). Grant comp-off for off-day work first.');
				} else {
					$btn.removeAttr('title');
				}
			} else {
				const rowBal = lb[newType.toLowerCase()] || {};
				const avail = parseFloat(rowBal.available) || 0;
				if (days > avail) {
					$btn.attr('title', newType + ' available: ' + avail + ' (need ' + days + '). Apply may use advance if policy allows.');
				} else {
					$btn.removeAttr('title');
				}
			}
		}
	});

	function postPayrollCompOffGrant(workDate) {
		const empId = $('#employeeSelectSingle').val();
		const $msg = $('#payrollCompOffMessage');
		if (!empId || !workDate) {
			alert('Select employee and work date.');
			return;
		}
		const creditDays = $('#payrollCompOffCreditDays').val() || '1';
		const reason = $('#payrollCompOffReason').val() || 'Worked on weekly off / holiday';
		$msg.hide().removeClass('text-danger text-success');
		$('#btnPayrollGrantCompOff').prop('disabled', true).text('Granting…');
		const formData = new FormData();
		formData.append('employee_id', empId);
		formData.append('work_date', workDate);
		formData.append('credit_days', creditDays);
		formData.append('reason', reason);
		fetch('action/grant-comp-off-action.php', { method: 'POST', body: formData, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				$('#btnPayrollGrantCompOff').prop('disabled', false).text('Grant comp-off');
				if (data.error) {
					$msg.addClass('text-danger').text(data.message || 'Could not grant comp-off.').show();
					return;
				}
				$msg.addClass('text-success').text(data.message || 'Comp-off granted.').show();
				reloadPayrollPreview();
			})
			.catch(function () {
				$('#btnPayrollGrantCompOff').prop('disabled', false).text('Grant comp-off');
				$msg.addClass('text-danger').text('Network error.').show();
			});
	}

	$(document).on('click', '#btnPayrollGrantCompOff', function () {
		postPayrollCompOffGrant($('#payrollCompOffWorkDate').val());
	});
	$(document).on('click', '.payroll-compoff-grant-quick', function () {
		const d = $(this).attr('data-work-date');
		$('#payrollCompOffWorkDate').val(d);
		postPayrollCompOffGrant(d);
	});
	$(document).on('click', '.payroll-compoff-approve', function () {
		const id = $(this).attr('data-id');
		const empId = $('#employeeSelectSingle').val();
		const formData = new FormData();
		formData.append('comp_off_id', id);
		formData.append('employee_id', empId);
		formData.append('approve', '1');
		fetch('action/approve-comp-off-payroll-action.php', { method: 'POST', body: formData, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.error) { alert(data.message || 'Failed'); return; }
				reloadPayrollPreview();
			});
	});
	$(document).on('click', '.payroll-compoff-reject', function () {
		const id = $(this).attr('data-id');
		const empId = $('#employeeSelectSingle').val();
		const reason = prompt('Rejection reason (optional):') || '';
		const formData = new FormData();
		formData.append('comp_off_id', id);
		formData.append('employee_id', empId);
		formData.append('approve', '0');
		formData.append('reason', reason);
		fetch('action/approve-comp-off-payroll-action.php', { method: 'POST', body: formData, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.error) { alert(data.message || 'Failed'); return; }
				reloadPayrollPreview();
			});
	});

	$(document).on('click', '.payroll-leave-type-save', function () {
		const leaveId = $(this).attr('data-leave-id');
		const $row = $('tr[data-leave-id="' + leaveId + '"]');
		const $select = $row.find('.payroll-leave-type-select');
		const newType = String($select.val() || '');
		const empId = $('#employeeSelectSingle').val();
		const $msg = $('#payrollLeaveTypeMessage');
		if (!leaveId || !empId || !newType) {
			alert('Please select CL or SL.');
			return;
		}
		const oldType = String($select.attr('data-old-type') || '');
		if (newType === oldType) {
			return;
		}
		if (!confirm('Change this leave to ' + (newType === 'COMPOFF' ? 'Comp-off' : newType) + '? Employee leave balance will be updated after validation.')) {
			return;
		}
		const $btn = $(this);
		$btn.prop('disabled', true).text('Saving…');
		$msg.hide().removeClass('text-danger text-success');
		const formData = new FormData();
		formData.append('leave_id', leaveId);
		formData.append('employee_id', empId);
		formData.append('new_type', newType);
		fetch('action/change-leave-type-action.php', { method: 'POST', body: formData, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (data.error) {
					$msg.addClass('text-danger').text(data.message || 'Could not update leave type.').show();
					$btn.prop('disabled', false).text('Apply');
					return;
				}
				$msg.addClass('text-success').text(data.message || 'Leave type updated.').show();
				reloadPayrollPreview();
			})
			.catch(function () {
				$msg.addClass('text-danger').text('Network error while updating leave type.').show();
				$btn.prop('disabled', false).text('Apply');
			});
	});

	$('#btnPayrollPreviewSummary').on('click', function () {
		const empId = $('#employeeSelectSingle').val();
		if (!empId) {
			alert('Please select an employee first.');
			return;
		}
		const $body = $('#payrollPreviewModalBody');
		$body.html('<div class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Loading attendance &amp; leave summary…</div>');
		if (payrollPreviewModal) {
			payrollPreviewModal.show();
		}
		const params = collectPayrollPreviewParams();
		fetch('action/get-payroll-preview-action.php?' + params.toString(), { credentials: 'same-origin' })
			.then(function (r) {
				return r.text().then(function (text) {
					try {
						return JSON.parse(text);
					} catch (e) {
						throw new Error(text && text.length < 500 ? text.trim() : 'Server returned invalid response (HTTP ' + r.status + ').');
					}
				});
			})
			.then(function (data) {
				if (data.error) {
					$body.html('<div class="alert alert-danger mb-0">' + (data.message || 'Could not load summary') + '</div>');
					return;
				}
				const emp = data.employee || {};
				$('#payrollPreviewModalTitle').text('Attendance & leave — ' + (emp.name || 'Employee'));
				lastPayrollPreviewData = data;
				$body.html(renderPayrollPreview(data));
			})
			.catch(function (err) {
				$body.html('<div class="alert alert-danger mb-0">' + (err && err.message ? err.message : 'Could not load summary. Please try again.') + '</div>');
			});
	});

	if ($('#employeeSelectSingle').val()) {
		$('#employeeSelectSingle').trigger('change');
	}
	$('form').on('submit', function () {
		if ($('#employeeSelectSingle').length) {
			$('#employeeSelectSingle').trigger('change');
		}
	});

	const slipSearch = document.getElementById('slipEmployeeSearch');
	const slipStatusFilter = document.getElementById('slipStatusFilter');
	const slipsTable = document.getElementById('slipsTable');

	function applySlipTableFilters() {
		if (!slipsTable) return;
		const q = (slipSearch?.value || '').toLowerCase().trim();
		const statusFilter = slipStatusFilter?.value || 'generated';
		const rows = slipsTable.querySelectorAll('tbody tr.slip-filter-row');
		let visibleCount = 0;
		rows.forEach(function (row) {
			const rowStatus = row.getAttribute('data-slip-status') || '';
			const employeeText = (row.children[1]?.textContent || '').toLowerCase();
			const codeText = (row.children[2]?.textContent || '').toLowerCase();
			const matchesSearch = q === '' || employeeText.indexOf(q) !== -1 || codeText.indexOf(q) !== -1;
			const matchesStatus = statusFilter === 'all'
				|| (statusFilter === 'generated' && rowStatus === 'generated')
				|| (statusFilter === 'pending' && rowStatus === 'pending');
			const show = matchesSearch && matchesStatus;
			row.style.display = show ? '' : 'none';
			if (show) visibleCount++;
		});
		const emptyRow = document.getElementById('slipTableEmptyRow');
		if (emptyRow) {
			emptyRow.style.display = visibleCount === 0 && rows.length > 0 ? '' : 'none';
		}
	}

	if (slipSearch) {
		slipSearch.addEventListener('input', applySlipTableFilters);
	}
	if (slipStatusFilter) {
		slipStatusFilter.addEventListener('change', applySlipTableFilters);
	}
	applySlipTableFilters();

	$(document).on('click', '.btn-select-for-generate', function () {
		const empId = $(this).attr('data-employee-id');
		if (!empId) return;
		$('#employeeSelectSingle').val(empId).trigger('change');
		$('html, body').animate({ scrollTop: $('#formGenerateSingleSlip').offset().top - 80 }, 300);
	});
<?php if ($latestSlipId > 0) { ?>
	const latestSlipRow = document.getElementById('slip-row-<?php echo $latestSlipId; ?>');
	if (latestSlipRow) {
		setTimeout(function () {
			latestSlipRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}, 300);
	}
	const latestSlipModalEl = document.getElementById('latestSlipModal');
	if (latestSlipModalEl && typeof bootstrap !== 'undefined') {
		const latestSlipModal = new bootstrap.Modal(latestSlipModalEl);
		latestSlipModal.show();
	}
<?php } ?>

	let slipPdfSlipId = 0;
	let slipPdfRev = '';
	let slipSendEmail = '';
	let slipSendPhone = '';
	const companySlipModalEl = document.getElementById('companySlipModal');
	let companySlipModal = null;
	if (companySlipModalEl && typeof bootstrap !== 'undefined') {
		companySlipModal = new bootstrap.Modal(companySlipModalEl);
	}

	function openCompanySlipModal(slipId, email, phone, slipRev) {
		slipPdfSlipId = parseInt(slipId, 10) || 0;
		slipPdfRev = slipRev || '';
		slipSendEmail = email || '';
		slipSendPhone = phone || '';
		if (!slipPdfSlipId) {
			alert('Salary slip not found.');
			return;
		}
		const statusEl = document.getElementById('slipSendStatus');
		if (statusEl) {
			statusEl.style.display = 'none';
			statusEl.textContent = '';
			statusEl.className = 'px-3 pb-3 small text-muted';
		}
		const sel = document.getElementById('slipCompanySelect');
		if (sel && sel.options.length > 2 && !sel.value) {
			sel.selectedIndex = 1;
		}
		if (companySlipModal) {
			companySlipModal.show();
		} else if (companySlipModalEl) {
			companySlipModalEl.classList.add('show');
			companySlipModalEl.style.display = 'block';
			companySlipModalEl.removeAttribute('aria-hidden');
			document.body.classList.add('modal-open');
		} else {
			alert('Company selection is unavailable. Please refresh the page.');
		}
	}

	function goPayslipPdf(action) {
		const sel = document.getElementById('slipCompanySelect');
		const companyId = sel ? sel.value : '';
		if (!companyId) {
			alert('Please select a company for the salary slip.');
			return;
		}
		if (!slipPdfSlipId) {
			alert('Salary slip not found.');
			return;
		}
		const url = 'view-payslip-pro.php?id=' + slipPdfSlipId
			+ '&company_id=' + encodeURIComponent(companyId)
			+ '&pdf=' + encodeURIComponent(action)
			+ '&v=' + encodeURIComponent(slipPdfRev || String(Date.now()));
		if (companySlipModal) {
			companySlipModal.hide();
		}
		if (action === 'download') {
			window.location.href = url;
		} else {
			window.open(url, '_blank');
		}
	}

	function sendPayslipToEmployee() {
		const sel = document.getElementById('slipCompanySelect');
		const companyId = sel ? sel.value : '';
		const statusEl = document.getElementById('slipSendStatus');
		if (!companyId) {
			alert('Please select a company for the salary slip.');
			return;
		}
		if (!slipPdfSlipId) {
			alert('Salary slip not found.');
			return;
		}
		if (!slipSendEmail && !slipSendPhone) {
			alert('Employee has no email or contact number. Update the employee profile first.');
			return;
		}
		const btn = document.getElementById('slipSendBtn');
		if (btn) {
			btn.disabled = true;
			btn.textContent = 'Sending…';
		}
		if (statusEl) {
			statusEl.style.display = 'block';
			statusEl.textContent = 'Sending salary slip link…';
		}
		const formData = new FormData();
		formData.append('slip_id', slipPdfSlipId);
		formData.append('company_id', companyId);
		fetch('action/send-payslip-action.php', { method: 'POST', body: formData, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (data) {
				if (btn) {
					btn.disabled = false;
					btn.textContent = 'Send (Email & WhatsApp)';
				}
				if (data.error) {
					if (statusEl) {
						statusEl.className = 'px-3 pb-3 small text-danger';
						statusEl.textContent = data.message || 'Send failed';
					} else {
						alert(data.message || 'Send failed');
					}
					return;
				}
				if (statusEl) {
					var isWaFail = (data.employee_phone && !data.whatsapp_sent);
					statusEl.className = 'px-3 pb-3 small ' + (isWaFail ? 'text-warning' : 'text-success');
					statusEl.textContent = data.message || 'Sent';
				}
			})
			.catch(function () {
				if (btn) {
					btn.disabled = false;
					btn.textContent = 'Send (Email & WhatsApp)';
				}
				if (statusEl) {
					statusEl.className = 'px-3 pb-3 small text-danger';
					statusEl.textContent = 'Network error while sending.';
				} else {
					alert('Network error while sending.');
				}
			});
	}

	$(document).on('click', '.btn-slip-pdf', function (e) {
		e.preventDefault();
		e.stopPropagation();
		const $btn = $(this);
		openCompanySlipModal($btn.attr('data-slip-id'), '', '', $btn.attr('data-slip-rev') || '');
	});
	$(document).on('click', '.btn-slip-send', function (e) {
		e.preventDefault();
		e.stopPropagation();
		const $btn = $(this);
		openCompanySlipModal($btn.attr('data-slip-id'), $btn.attr('data-email') || '', $btn.attr('data-phone') || '', $btn.attr('data-slip-rev') || '');
	});
	$('#slipPdfPreviewBtn').on('click', function () { goPayslipPdf('preview'); });
	$('#slipPdfDownloadBtn').on('click', function () { goPayslipPdf('download'); });
	$('#slipSendBtn').on('click', function () { sendPayslipToEmployee(); });

	const payrollRuleFields = [
		'include_profile_weekly_off',
		'include_fixed_weekly_offs',
		'include_approved_attendance',
		'include_checkout_attendance',
		'include_min_hours_rule',
		'include_deduct_non_cl_sl_leave',
		'include_half_day_deduction',
		'include_prorate_joining_date'
	];
	function syncPayrollRuleHiddens(disableCheckboxes) {
		const $form = $('#formGenerateSingleSlip');
		payrollRuleFields.forEach(function (name) {
			const $hidden = $form.find('input[type=hidden][name="' + name + '"]');
			const $cb = $form.find('input[type=checkbox][name="' + name + '"]');
			if ($hidden.length && $cb.length) {
				$hidden.val($cb.is(':checked') ? '1' : '0');
				if (disableCheckboxes) {
					$cb.prop('disabled', true);
				}
			}
		});
	}
	$('#formGenerateSingleSlip .payroll-rule-checkbox').on('change', function () {
		syncPayrollRuleHiddens(false);
	});
	syncPayrollRuleHiddens(false);

	$('#formGenerateSingleSlip').on('submit', function () {
		syncPayrollRuleHiddens(true);
		if (!$('#include_kpi').is(':checked')) {
			return true;
		}
		$('#kpiSlipLoading').removeClass('d-none');
		$('#btnGenerateSingleSlip').prop('disabled', true).text('Generating…');
	});
});
</script>
</body>
</html>
