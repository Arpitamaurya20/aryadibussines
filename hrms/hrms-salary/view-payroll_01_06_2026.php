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

$slips = $salary->listSlipsForPeriod($y, $m);
$employees = $core->_getTableRecords($conn, 'employees', 'WHERE IFNULL(IsActive,1)=1 ORDER BY Name ASC');
$quoteCompanies = $salary->listQuoteCompanies();

$flashOk = isset($_GET['ok']) ? $_GET['ok'] : '';
$flashErr = isset($_GET['err']) ? $_GET['err'] : '';
$latestSlipId = isset($_GET['latest_slip_id']) ? intval($_GET['latest_slip_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>HRMS Payroll — Salary slips</title>
	<?php include('../include/common-head.php'); ?>
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<style>
		.select2-container .select2-selection--single {
			height: 38px !important;
			padding-top: 4px;
		}
		.select2-container .select2-selection--single .select2-selection__arrow {
			height: 36px !important;
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
					<div class="alert alert-success"><?php echo htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8'); ?></div>
				<?php } ?>
				<?php if ($flashErr !== '') { ?>
					<div class="alert alert-danger"><?php echo htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8'); ?></div>
				<?php } ?>

				<div class="card mb-4">
					<div class="card-header"><strong>Generate salary slip</strong></div>
					<div class="card-body">
						<form action="action/generate-payroll-action.php" method="post" class="row g-3 align-items-end">
							<input type="hidden" name="return_y" value="<?php echo $y; ?>">
							<input type="hidden" name="return_m" value="<?php echo $m; ?>">
							<div class="col-md-4">
								<label class="form-label">Generate individual employee slip</label>
								<select name="employee_id" id="employeeSelectSingle" class="form-select" required>
									<option value="">Select employee</option>
<?php foreach ($employees as $empOpt) { ?>
									<option value="<?php echo intval($empOpt['ID']); ?>">
										<?php echo htmlspecialchars(($empOpt['EmployeeNumber'] ?? '') . ' - ' . ($empOpt['Name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
									</option>
<?php } ?>
								</select>
							</div>
							<div class="col-auto">
								<label class="form-label">Month</label>
								<select name="month" class="form-select">
<?php for ($i = 1; $i <= 12; $i++) {
	$sel = ($i === $m) ? ' selected' : '';
	echo '<option value="' . $i . '"' . $sel . '>' . date('F', mktime(0, 0, 0, $i, 1)) . '</option>';
} ?>
								</select>
							</div>
							<div class="col-auto">
								<label class="form-label">Year</label>
								<input type="number" name="year" class="form-control" value="<?php echo $y; ?>" min="2000" max="2099">
							</div>
							<div class="col-auto form-check mt-4">
								<input type="checkbox" name="overwrite" id="overwrite_single" value="1" class="form-check-input" checked>
								<label class="form-check-label" for="overwrite_single">Overwrite existing slip</label>
							</div>
							<div class="col-auto form-check mt-4">
								<input type="checkbox" name="include_kpi" id="include_kpi" value="1" class="form-check-input">
								<label class="form-check-label" for="include_kpi">Include KPI performance (this month)</label>
							</div>
							<div class="col-12">
								<p class="text-muted small mb-0">When checked, uses saved data from <code>employee_kpi_monthly_snapshot</code> for the selected month: shows KPI summary on the slip and sets <strong>Net Payable</strong> to <code>PayableSalary</code>. Uncheck and regenerate with overwrite to remove KPI from the slip.</p>
							</div>
							<div class="col-auto">
								<button type="submit" class="btn btn-primary">Generate single slip</button>
							</div>
						</form>
					</div>
				</div>
				<div class="card mb-4">
					<div class="card-header"><strong>Employee statutory options (EPF / ESI)</strong></div>
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
							<div class="col-auto form-check mt-4">
								<input type="checkbox" name="apply_epf" id="applyEPFBox" value="1" class="form-check-input" checked>
								<label class="form-check-label" for="applyEPFBox">Apply EPF</label>
							</div>
							<div class="col-auto form-check mt-4">
								<input type="checkbox" name="apply_esi" id="applyESIBox" value="1" class="form-check-input" checked>
								<label class="form-check-label" for="applyESIBox">Apply ESI</label>
							</div>
							<div class="col-auto">
								<button type="submit" class="btn btn-warning">Save statutory settings</button>
							</div>
						</form>
					</div>
				</div>

				<div class="card">
					<div class="card-header d-flex justify-content-between align-items-center">
						<strong>Slips — <?php echo htmlspecialchars(date('F', mktime(0, 0, 0, $m, 1)) . ' ' . $y, ENT_QUOTES, 'UTF-8'); ?></strong>
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
						<div class="row g-2">
							<div class="col-md-4">
								<label class="form-label mb-1">Search employee in slips</label>
								<input type="text" id="slipEmployeeSearch" class="form-control form-control-sm" placeholder="Type employee name or code">
							</div>
						</div>
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-striped mb-0" id="slipsTable">
								<thead>
									<tr>
										<th>Employee</th>
										<th>Code</th>
										<th class="text-end">Gross</th>
										<th class="text-end">Deductions</th>
										<th class="text-end">Net</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
<?php if (empty($slips)) { ?>
									<tr><td colspan="6" class="text-center text-muted py-4">No slips for this month. Generate a slip for an employee above.</td></tr>
<?php } else {
	foreach ($slips as $row) {
		?>
									<tr>
										<td><?php echo htmlspecialchars($row['Name'], ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($row['EmployeeNumber'], ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="text-end"><?php echo number_format(floatval($row['GrossSalary']), 2); ?></td>
										<td class="text-end"><?php echo number_format(floatval($row['TotalDeductions']), 2); ?></td>
										<td class="text-end"><?php echo number_format(floatval($row['NetSalary']), 2); ?></td>
										<td class="text-nowrap">
											<button type="button" class="btn btn-sm btn-outline-primary btn-slip-pdf" data-slip-id="<?php echo intval($row['ID']); ?>" data-action="preview">PDF Preview</button>
											<button type="button" class="btn btn-sm btn-primary btn-slip-pdf" data-slip-id="<?php echo intval($row['ID']); ?>" data-action="download">Download PDF</button>
											<button type="button" class="btn btn-sm btn-success btn-slip-send" data-slip-id="<?php echo intval($row['ID']); ?>" data-email="<?php echo htmlspecialchars($row['Email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-phone="<?php echo htmlspecialchars($row['ContactNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo (trim($row['Email'] ?? '') === '' && trim($row['ContactNumber'] ?? '') === '') ? 'Add email/phone in employee profile' : 'Send slip via Email & WhatsApp'; ?>">Send</button>
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
				<button type="button" class="btn btn-outline-primary btn-slip-pdf" data-slip-id="<?php echo $latestSlipId; ?>" data-action="preview">PDF Preview</button>
				<button type="button" class="btn btn-primary btn-slip-pdf" data-slip-id="<?php echo $latestSlipId; ?>" data-action="download">Download PDF</button>
				<button type="button" class="btn btn-success btn-slip-send" data-slip-id="<?php echo $latestSlipId; ?>" data-email="" data-phone="">Send</button>
			</div>
		</div>
	</div>
</div>
<?php } ?>
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
	if ($('#employeeSelectSingle').val()) {
		$('#employeeSelectSingle').trigger('change');
	}
	$('form').on('submit', function () {
		if ($('#employeeSelectSingle').length) {
			$('#employeeSelectSingle').trigger('change');
		}
	});

	const slipSearch = document.getElementById('slipEmployeeSearch');
	const slipsTable = document.getElementById('slipsTable');
	if (slipSearch && slipsTable) {
		slipSearch.addEventListener('input', function () {
			const q = this.value.toLowerCase().trim();
			const rows = slipsTable.querySelectorAll('tbody tr');
			rows.forEach(function (row) {
				const employeeText = (row.children[0]?.textContent || '').toLowerCase();
				const codeText = (row.children[1]?.textContent || '').toLowerCase();
				const show = q === '' || employeeText.indexOf(q) !== -1 || codeText.indexOf(q) !== -1;
				row.style.display = show ? '' : 'none';
			});
		});
	}
<?php if ($latestSlipId > 0) { ?>
	const latestSlipModalEl = document.getElementById('latestSlipModal');
	if (latestSlipModalEl && typeof bootstrap !== 'undefined') {
		const latestSlipModal = new bootstrap.Modal(latestSlipModalEl);
		latestSlipModal.show();
	}
<?php } ?>

	let slipPdfSlipId = 0;
	let slipSendEmail = '';
	let slipSendPhone = '';
	const companySlipModalEl = document.getElementById('companySlipModal');
	let companySlipModal = null;
	if (companySlipModalEl && typeof bootstrap !== 'undefined') {
		companySlipModal = new bootstrap.Modal(companySlipModalEl);
	}

	function openCompanySlipModal(slipId, email, phone) {
		slipPdfSlipId = parseInt(slipId, 10) || 0;
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
		const url = 'view-payslip-pro.php?id=' + slipPdfSlipId + '&company_id=' + encodeURIComponent(companyId) + '&pdf=' + encodeURIComponent(action);
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
		openCompanySlipModal($btn.attr('data-slip-id'), '', '');
	});
	$(document).on('click', '.btn-slip-send', function (e) {
		e.preventDefault();
		e.stopPropagation();
		const $btn = $(this);
		openCompanySlipModal($btn.attr('data-slip-id'), $btn.attr('data-email') || '', $btn.attr('data-phone') || '');
	});
	$('#slipPdfPreviewBtn').on('click', function () { goPayslipPdf('preview'); });
	$('#slipPdfDownloadBtn').on('click', function () { goPayslipPdf('download'); });
	$('#slipSendBtn').on('click', function () { sendPayslipToEmployee(); });
});
</script>
</body>
</html>
