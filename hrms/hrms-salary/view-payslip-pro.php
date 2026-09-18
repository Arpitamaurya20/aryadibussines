<?php
@session_start();
require_once('../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$salary = new Salarypayroll($conn);

$shareToken = isset($_GET['token']) ? trim($_GET['token']) : '';
$isPublicShare = false;
if ($shareToken !== '') {
	$verifiedShare = $salary->verifyPayslipShareToken($shareToken);
	if (empty($verifiedShare['valid'])) {
		http_response_code(403);
		echo htmlspecialchars($verifiedShare['message'] ?? 'Invalid or expired link', ENT_QUOTES, 'UTF-8');
		exit;
	}
	$id = intval($verifiedShare['slip_id']);
	$companyId = intval($verifiedShare['company_id']);
	$pdfMode = 'download';
	$isPublicShare = true;
} else {
	$session = new Session($conn);
	$session->SessionCheck_redirect();
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$companyId = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
	$pdfMode = isset($_GET['pdf']) ? strtolower(trim($_GET['pdf'])) : '';
}

$autoPrint = !empty($_GET['autoprint']) && !$isPublicShare;
$data = $id ? $salary->getSlipWithLines($id) : null;
if ($data === null) {
	http_response_code(404);
	echo 'Slip not found';
	exit;
}

if (!$isPublicShare && ($pdfMode === 'preview' || $pdfMode === 'download') && $companyId <= 0) {
	$quoteCompanies = $salary->listQuoteCompanies();
	?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Select company — salary slip</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 520px;">
	<div class="card shadow-sm">
		<div class="card-header"><strong>Select company for salary slip</strong></div>
		<div class="card-body">
			<form method="get" action="">
				<input type="hidden" name="id" value="<?php echo $id; ?>">
				<input type="hidden" name="pdf" value="<?php echo htmlspecialchars($pdfMode, ENT_QUOTES, 'UTF-8'); ?>">
				<label class="form-label" for="company_id">Company</label>
				<select name="company_id" id="company_id" class="form-select mb-3" required>
					<option value="">— Choose company —</option>
<?php foreach ($quoteCompanies as $qc) { ?>
					<option value="<?php echo intval($qc['ID']); ?>"><?php echo htmlspecialchars($qc['CompanyName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
<?php } ?>
				</select>
<?php if (empty($quoteCompanies)) { ?>
				<p class="text-danger small">No active companies in quote_company_details.</p>
<?php } else { ?>
				<button type="submit" class="btn btn-primary w-100"><?php echo $pdfMode === 'download' ? 'Download PDF' : 'Open PDF Preview'; ?></button>
<?php } ?>
			</form>
			<p class="mt-3 mb-0"><a href="view-payroll.php?y=<?php echo intval($data['slip']['PayrollYear']); ?>&m=<?php echo intval($data['slip']['PayrollMonth']); ?>">← Back to payroll</a></p>
		</div>
	</div>
</div>
</body>
</html>
	<?php
	exit;
}

$company = null;
if ($companyId > 0) {
	$company = $salary->getQuoteCompanyById($companyId);
}
if ($company === null && ($pdfMode === 'preview' || $pdfMode === 'download')) {
	http_response_code(400);
	echo 'Invalid or inactive company selected.';
	exit;
}
if ($company === null) {
	$allCompanies = $salary->listQuoteCompanies();
	if (!empty($allCompanies)) {
		$company = $allCompanies[0];
	} else {
		$company = [
			'ID' => 0,
			'CompanyName' => 'Techxpert Group',
			'CompanyAddress' => '451-452, First Floor, Leela Ram Market, Masjid Moth, South Extension Part-II, New Delhi - 110049, India',
		];
	}
}

$slip = $data['slip'];
$emp = $data['employee'];
$lines = $data['lines'];
$earn = [];
$ded = [];
foreach ($lines as $ln) {
	if ($ln['LineType'] === 'earning') $earn[] = $ln;
	if ($ln['LineType'] === 'deduction') $ded[] = $ln;
}

$prevTs = strtotime(date('Y-m-01', mktime(0, 0, 0, intval($slip['PayrollMonth']), 1, intval($slip['PayrollYear']))) . ' -1 month');
$prevY = intval(date('Y', $prevTs));
$prevM = intval(date('n', $prevTs));
$prevSlip = $salary->listSlipsForPeriod($prevY, $prevM);
$prevPaidDays = 0.0;
foreach ($prevSlip as $ps) {
	if (intval($ps['EmployeeID']) === intval($slip['EmployeeID'])) {
		$prevPaidDays = floatval($ps['PaidDays']);
		break;
	}
}

$cl = 0.0; $sl = 0.0; $pl = 0.0;
$mStart = sprintf('%04d-%02d-01', intval($slip['PayrollYear']), intval($slip['PayrollMonth']));
$mEnd = date('Y-m-t', strtotime($mStart));
$leaveSql = "SELECT TypeOfLeave, Duration FROM employee_leave WHERE EmployeeID=" . intval($slip['EmployeeID']) . " AND IFNULL(IsActive,1)=1 AND Status IN ('Approved','approved') AND FromDate<='$mEnd' AND ToDate>='$mStart'";
$leaveRes = mysqli_query($conn, $leaveSql);
if ($leaveRes) {
	while ($lv = mysqli_fetch_assoc($leaveRes)) {
		$type = strtolower(trim($lv['TypeOfLeave'] ?? ''));
		$dur = floatval($lv['Duration'] ?? 1);
		if ($type === 'cl') $cl += $dur;
		if ($type === 'sl') $sl += $dur;
		if ($type === 'pl') $pl += $dur;
	}
}

$calcJson = [];
if (!empty($slip['CalculationJson'])) {
	$parsedCalc = json_decode($slip['CalculationJson'], true);
	if (is_array($parsedCalc)) {
		$calcJson = $parsedCalc;
	}
}
$attendanceSummary = [
	'total_days' => floatval($slip['TotalDays'] ?? 0),
	'present_days' => floatval($calcJson['present_days'] ?? 0),
	'paid_days' => floatval($slip['PaidDays'] ?? 0),
	'leave_days' => floatval($calcJson['leave_days'] ?? 0),
	'holiday_days' => floatval($calcJson['holiday_days'] ?? 0),
	'weekly_off_days' => floatval($calcJson['weekly_off_days'] ?? 0),
	'absent_days' => 0.0,
	'cl' => $cl,
	'sl' => $sl,
	'pl' => $pl,
];
if ($attendanceSummary['present_days'] <= 0) {
	$storedPayrollOptions = [];
	if (!empty($calcJson['payroll_options']) && is_array($calcJson['payroll_options'])) {
		$storedPayrollOptions = $calcJson['payroll_options'];
	}
	$liveAttendance = $salary->getPayslipAttendanceSummary(
		intval($slip['EmployeeID']),
		intval($slip['PayrollYear']),
		intval($slip['PayrollMonth']),
		$storedPayrollOptions
	);
	if (is_array($liveAttendance)) {
		$attendanceSummary = array_merge($attendanceSummary, $liveAttendance);
		$attendanceSummary['cl'] = $cl;
		$attendanceSummary['sl'] = $sl;
		$attendanceSummary['pl'] = $pl;
	}
}
if ($attendanceSummary['total_days'] <= 0) {
	$attendanceSummary['total_days'] = floatval(date('t', strtotime($mStart)));
}
if ($attendanceSummary['absent_days'] <= 0 && $attendanceSummary['total_days'] > 0) {
	$attendanceSummary['absent_days'] = max(
		0,
		$attendanceSummary['total_days']
			- $attendanceSummary['present_days']
			- $attendanceSummary['leave_days']
			- $attendanceSummary['holiday_days']
			- $attendanceSummary['weekly_off_days']
	);
}

/**
 * Resolve quote company header image for payslip HTML/PDF.
 * Files live under admin/media/pdf-assets/ (e.g. quote-header-....png).
 */
function resolveQuoteHeaderImageSrc($headerImage, $forPdf = false)
{
	$headerImage = trim((string) $headerImage);
	if ($headerImage === '') {
		return null;
	}

	$quoteAssetsDir = 'admin/media/pdf-assets';
	$projectRoot = dirname(__DIR__, 2);

	if (preg_match('#^https?://#i', $headerImage)) {
		if ($forPdf) {
			$path = parse_url($headerImage, PHP_URL_PATH);
			if (is_string($path) && $path !== '') {
				if (preg_match('#/Projects/techxpert/(.+)$#i', $path, $m)) {
					$abs = realpath($projectRoot . '/' . str_replace('\\', '/', $m[1]));
					if ($abs !== false) {
						return str_replace('\\', '/', $abs);
					}
				}
				$abs = realpath($projectRoot . '/' . ltrim(str_replace('\\', '/', $path), '/'));
				if ($abs !== false) {
					return str_replace('\\', '/', $abs);
				}
			}
		}
		return $headerImage;
	}

	$headerImage = str_replace('\\', '/', $headerImage);
	$headerImage = ltrim($headerImage, '/');

	if (strpos($headerImage, '/') === false) {
		$relativePath = $quoteAssetsDir . '/' . $headerImage;
	} elseif (stripos($headerImage, 'admin/media/pdf-assets/') === 0) {
		$relativePath = $headerImage;
	} elseif (stripos($headerImage, 'media/pdf-assets/') === 0) {
		$relativePath = 'admin/' . $headerImage;
	} elseif (preg_match('#^Projects/techxpert/(.+)$#i', $headerImage, $m)) {
		$relativePath = $m[1];
	} else {
		$relativePath = $quoteAssetsDir . '/' . basename($headerImage);
	}

	$absPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
	if ($forPdf) {
		$real = realpath($absPath);
		return $real !== false ? str_replace('\\', '/', $real) : null;
	}

	return '../../' . $relativePath;
}

function renderClassicSlipHtml($emp, $slip, $earn, $ded, $forPdf = false, $prevPaidDays = 0.0, $cl = 0.0, $sl = 0.0, $pl = 0.0, $company = [], $attendanceSummary = [], $isPublicShare = false)
{
	$companyId = intval($company['ID'] ?? 0);
	$companyName = htmlspecialchars($company['CompanyName'] ?? 'Techxpert Group', ENT_QUOTES, 'UTF-8');
	$companyAddress = htmlspecialchars($company['CompanyAddress'] ?? '', ENT_QUOTES, 'UTF-8');
	$companyGst = trim($company['GstNumber'] ?? '');
	$companyPan = trim($company['PanNumber'] ?? '');
	$headerImage = trim($company['HeaderImage'] ?? '');
	$headerImgHtml = '';
	$imgSrc = resolveQuoteHeaderImageSrc($headerImage, $forPdf);
	if ($imgSrc !== null && $imgSrc !== '') {
		$headerImgHtml = '<div style="margin-bottom:6px;text-align:center;"><img src="' . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . '" alt="" style="max-height:72px;max-width:100%;"></div>';
	}
	$companyQuery = $companyId > 0 ? '&company_id=' . $companyId : '';
	$calcJson = [];
	if (!empty($slip['CalculationJson'])) {
		$parsedSlipCalc = json_decode($slip['CalculationJson'], true);
		if (is_array($parsedSlipCalc)) {
			$calcJson = $parsedSlipCalc;
		}
	}
	$kpiApplied = !empty($calcJson['kpi_applied']);
	$kpiData = is_array($calcJson['kpi'] ?? null) ? $calcJson['kpi'] : [];
	$kpiDeductionDisplay = floatval($calcJson['kpi_deduction'] ?? 0);
	$kpiNetPayable = floatval($calcJson['kpi_net_payable'] ?? $slip['NetSalary']);
	$netPayableDisplay = $kpiApplied ? $kpiNetPayable : floatval($slip['NetSalary']);
	ob_start();
	$formatByEightyRule = function ($amount) {
		$amount = floatval($amount);
		$whole = floor($amount);
		$fraction = $amount - $whole;
		if ($fraction >= 0.80) {
			$whole += 1;
		}
		return number_format($whole, 2);
	};
	$monthTitle = date('M-Y', mktime(0, 0, 0, intval($slip['PayrollMonth']), 1, intval($slip['PayrollYear'])));
	$nationalMap = [
		'Basic' => floatval($emp['Basic'] ?? 0),
		'H.R.A.' => floatval($emp['HRA'] ?? 0),
		'Convey.' => floatval($emp['ConvenienceAllowance'] ?? 0),
		'Other All.' => floatval($emp['Bonus'] ?? 0) + floatval($emp['Others'] ?? 0),
		'Medical' => 0.0,
		'Incentive' => 0.0,
	];
	$earnMap = [
		'Basic' => 0.0,
		'H.R.A.' => 0.0,
		'Convey.' => 0.0,
		'Other All.' => 0.0,
		'Medical' => 0.0,
		'Incentive' => 0.0,
	];
	$dedMap = [
		'P.F.' => 0.0,
		'ESI' => 0.0,
		'Income Tex' => 0.0,
		'LWF' => 0.0,
		'Loan' => 0.0,
		'ADV' => 0.0,
		'Other' => 0.0,
		'V. P. F.' => 0.0,
	];
	foreach ($earn as $e) {
		$code = strtoupper(trim($e['ComponentCode'] ?? ''));
		$label = strtolower(trim($e['ComponentLabel'] ?? ''));
		$amt = floatval($e['Amount'] ?? 0);
		if ($code === 'BASIC' || $label === 'basic') $earnMap['Basic'] += $amt;
		elseif ($code === 'HRA' || strpos($label, 'hra') !== false) $earnMap['H.R.A.'] += $amt;
		elseif ($code === 'CONVENIENCEALLOWANCE' || strpos($label, 'conven') !== false) $earnMap['Convey.'] += $amt;
		elseif ($code === 'HEALTHINSURANCE' || strpos($label, 'medical') !== false || strpos($label, 'health') !== false) $earnMap['Medical'] += $amt;
		elseif ($code === 'BONUS' || $code === 'OTHERS' || strpos($label, 'other + bonus') !== false) $earnMap['Other All.'] += $amt;
		elseif ($code === 'INCENTIVE' || strpos($label, 'incentive') !== false) $earnMap['Incentive'] += $amt;
		else $earnMap['Other All.'] += $earnMap['Other All.'];
	}
	foreach ($ded as $d) {
		$code = strtoupper(trim($d['ComponentCode'] ?? ''));
		$label = strtolower(trim($d['ComponentLabel'] ?? ''));
		$amt = floatval($d['Amount'] ?? 0);
		if ($code === 'EPF_EE' || strpos($label, 'pf') !== false) $dedMap['P.F.'] += $amt;
		elseif ($code === 'ESI_EE' || strpos($label, 'esi') !== false) $dedMap['ESI'] += $amt;
		elseif ($code === 'INCOME_TAX' || $code === 'TDS' || strpos($label, 'income tax') !== false || strpos($label, 'tds') !== false) $dedMap['Income Tex'] += $amt;
		elseif ($code === 'LWF' || strpos($label, 'lwf') !== false) $dedMap['LWF'] += $amt;
		elseif ($code === 'PT' || $code === 'PTEX' || strpos($label, 'professional') !== false || strpos($label, 'ptex') !== false) $dedMap['Other'] += $amt;
		elseif ($code === 'LOAN' || strpos($label, 'loan') !== false) $dedMap['Loan'] += $amt;
		elseif ($code === 'ADV' || $code === 'ADVANCE' || strpos($label, 'adv') !== false) $dedMap['ADV'] += $amt;
		elseif ($code === 'VPF' || strpos($label, 'vpf') !== false) $dedMap['V. P. F.'] += $amt;
		elseif ($code === 'KPI_PERF' || strpos($label, 'kpi performance') !== false) $dedMap['Other'] += $amt;
		else $dedMap['Other'] += $amt;
	}
	$totalDays = floatval($slip['TotalDays'] ?? 0);
	$paidDays = floatval($slip['PaidDays'] ?? 0);
	$roundingValue = round(floatval($slip['NetSalary']) - (floatval($slip['GrossSalary']) - floatval($slip['TotalDeductions'])), 2);
	?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Salary Slip - <?php echo htmlspecialchars($emp['Name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
	<style>
		body { font-family: 'Times New Roman', serif; color: #111; font-size: 13px; margin: 10px; }
		.sheet { max-width: 980px; margin: 0 auto; border: 2px solid #000; padding: 8px; }
		.box { border: 2px solid #000; padding: 8px; margin-bottom: 8px; }
		.company-name { font-size: 18px; font-weight: 700; }
		.small { font-size: 12px; }
		table { width: 100%; border-collapse: collapse; }
		.meta td, .salary td, .salary th, .totals td, .attendance td, .attendance th { border: 1px solid #000; padding: 3px 6px; vertical-align: top; }
		.salary th, .attendance th { background: #f2f2f2; text-align: left; }
		.attendance .section-head { font-weight: 700; background: #f2f2f2; }
		.attendance .right { text-align: right; }
		.right { text-align: right; }
		.note { margin-top: 8px; border-top: 1px dashed #000; padding-top: 6px; font-size: 12px; }
		.actions { margin-bottom: 10px; }
		@media print {
			.actions { display: none; }
			body { margin: 0; }
		}
	</style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body>
<?php if (!$forPdf && !$isPublicShare) { ?>
<div class="actions">
	<button onclick="window.print()">Print / Save PDF</button>
	<a href="view-payslip-pro.php?id=<?php echo intval($slip['ID']); ?><?php echo $companyQuery; ?>&pdf=preview" target="_blank">PDF Preview</a>
	<a href="view-payslip-pro.php?id=<?php echo intval($slip['ID']); ?><?php echo $companyQuery; ?>&pdf=download">Download PDF</a>
</div>
<?php } ?>

<div class="sheet">
	<div class="box">
		
		<div class="company-name"><?php echo $companyName; ?></div>
		<?php if ($companyAddress !== '') { ?>
		<div class="small"><?php echo $companyAddress; ?></div>
		<?php } ?>
		<?php if ($companyGst !== '' || $companyPan !== '') { ?>
		<div class="small">
			<?php if ($companyGst !== '') { ?>GST: <?php echo htmlspecialchars($companyGst, ENT_QUOTES, 'UTF-8'); ?><?php } ?>
			<?php if ($companyGst !== '' && $companyPan !== '') { ?> &nbsp;|&nbsp; <?php } ?>
			<?php if ($companyPan !== '') { ?>PAN: <?php echo htmlspecialchars($companyPan, ENT_QUOTES, 'UTF-8'); ?><?php } ?>
		</div>
		<?php } ?>
		<div class="small"><strong>Pay Slip For The Month Of:</strong> <?php echo htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></div>
	</div>

	<table class="meta">
		<tr>
			<td><strong>Code</strong> : <?php echo htmlspecialchars($emp['EmployeeNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><strong>Branch</strong> : <?php echo htmlspecialchars($emp['Division'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><strong>Dept</strong> : <?php echo htmlspecialchars($emp['Department'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
		</tr>
		<tr>
			<td><strong>Name</strong> : <?php echo htmlspecialchars($emp['Name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><strong>PAN No.</strong> : <?php echo htmlspecialchars($emp['PAN'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><strong>Bank Name</strong> : <?php echo htmlspecialchars($emp['BankAccountName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
		</tr>
		<tr>
			<td><strong>D.O.J.</strong> : <?php echo htmlspecialchars($emp['DateofJoining'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><strong>D.O.B.</strong> : <?php echo htmlspecialchars($emp['DateofBirth'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><strong>A/C No.</strong> : <?php echo htmlspecialchars($emp['BankAccountNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
		</tr>
		<tr>
			<td><strong>Desig.</strong> : <?php echo htmlspecialchars($emp['Designation'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><strong>UAN No.</strong> : <?php echo htmlspecialchars($emp['UANNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><strong>ESIC No.</strong> : <?php echo htmlspecialchars($emp['Esic_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
		</tr>
	</table>

<?php
	$attTotalDays = floatval($attendanceSummary['total_days'] ?? 0);
	$attPresent = floatval($attendanceSummary['present_days'] ?? 0);
	$attPaid = floatval($attendanceSummary['paid_days'] ?? 0);
	$attWeeklyOff = floatval($attendanceSummary['weekly_off_days'] ?? 0);
	$attHoliday = floatval($attendanceSummary['holiday_days'] ?? 0);
	$attLeave = floatval($attendanceSummary['leave_days'] ?? 0);
	$attAbsent = floatval($attendanceSummary['absent_days'] ?? 0);
	$attCl = floatval($attendanceSummary['cl'] ?? 0);
	$attSl = floatval($attendanceSummary['sl'] ?? 0);
	$attPl = floatval($attendanceSummary['pl'] ?? 0);
?>
	<table class="attendance" style="margin-top: 8px;">
		<tr>
			<th colspan="4" class="section-head">Attendance Summary — <?php echo htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?></th>
		</tr>
		<tr>
			<td style="width:28%;"><strong>Total Days in Month</strong></td>
			<td class="right" style="width:12%;"><?php echo number_format($attTotalDays, 0); ?></td>
			<td style="width:30%;"><strong>Present Days</strong></td>
			<td class="right" style="width:30%;"><strong><?php echo number_format($attPresent, 0); ?></strong></td>
		</tr>
		<tr>
			<td><strong>Weekly Off</strong></td>
			<td class="right"><?php echo number_format($attWeeklyOff, 0); ?></td>
			<td><strong>Paid Days (Salary)</strong></td>
			<td class="right"><strong><?php echo number_format($attPaid, 0); ?></strong></td>
		</tr>
		<tr>
			<td><strong>Public Holidays</strong></td>
			<td class="right"><?php echo number_format($attHoliday, 0); ?></td>
			<td><strong>Prev. Month Paid Days</strong></td>
			<td class="right"><?php echo number_format($prevPaidDays, 0); ?></td>
		</tr>
		<tr>
			<td><strong>Approved Leave (Total)</strong></td>
			<td class="right"><?php echo number_format($attLeave, 0); ?></td>
			<td><strong>Absent Days</strong></td>
			<td class="right"><?php echo number_format($attAbsent, 0); ?></td>
		</tr>
		<tr>
			<td><strong>CL</strong></td>
			<td class="right"><?php echo number_format($attCl, 2); ?></td>
			<td><strong>SL</strong></td>
			<td class="right"><?php echo number_format($attSl, 2); ?></td>
		</tr>
		<tr>
			<td><strong>PL</strong></td>
			<td class="right"><?php echo number_format($attPl, 2); ?></td>
			<td><strong>Rounding Value</strong></td>
			<td class="right"><?php echo number_format($roundingValue, 2); ?></td>
		</tr>
	</table>

<?php if ($kpiApplied && !empty($kpiData)) {
	$kpiIsExec = !empty($kpiData['is_executive']);
	$kpiPeriod = htmlspecialchars(trim(($kpiData['date_from'] ?? '') . ' → ' . ($kpiData['date_to'] ?? '')), ENT_QUOTES, 'UTF-8');
?>
	<table class="attendance" style="margin-top: 8px;">
		<tr>
			<th colspan="4" class="section-head">KPI Performance — <?php echo htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8'); ?><?php if ($kpiPeriod !== '→') { ?> <span class="small">(<?php echo $kpiPeriod; ?>)</span><?php } ?></th>
		</tr>
<?php if (!$kpiIsExec) { ?>
		<tr>
			<td><strong>Assignment</strong></td>
			<td class="right"><?php echo number_format(floatval($kpiData['assign_pct'] ?? 0), 1); ?>%</td>
			<td><strong>Quotation</strong></td>
			<td class="right"><?php echo number_format(floatval($kpiData['quote_pct'] ?? 0), 1); ?>%</td>
		</tr>
		<tr>
			<td><strong>Closed</strong></td>
			<td class="right"><?php echo number_format(floatval($kpiData['closed_pct'] ?? 0), 1); ?>%</td>
			<td><strong>Attendance</strong></td>
			<td class="right"><?php echo number_format(floatval($kpiData['attendance_pct'] ?? 0), 1); ?>%</td>
		</tr>
<?php } else { ?>
		<tr>
			<td><strong>Attendance</strong></td>
			<td class="right"><?php echo number_format(floatval($kpiData['attendance_pct'] ?? 0), 1); ?>%</td>
			<td><strong>Salary %</strong></td>
			<td class="right"><?php echo number_format(floatval($kpiData['salary_pct'] ?? 0), 1); ?>%</td>
		</tr>
<?php } ?>
		<tr>
			<td><strong>Overall KPI</strong></td>
			<td class="right"><strong><?php echo number_format(floatval($kpiData['overall_pct'] ?? 0), 1); ?>%</strong></td>
			<td><strong>Full salary (in-hand)</strong></td>
			<td class="right"><?php echo $formatByEightyRule(floatval($kpiData['full_salary'] ?? 0)); ?></td>
		</tr>
		<tr>
			<td><strong>KPI deduction</strong></td>
			<td class="right"><?php echo $formatByEightyRule($kpiDeductionDisplay); ?></td>
			<td><strong>Payable salary (KPI)</strong></td>
			<td class="right"><strong><?php echo $formatByEightyRule(floatval($kpiData['payable_salary'] ?? 0)); ?></strong></td>
		</tr>
	</table>
<?php } ?>

	<table class="salary" style="margin-top: 8px;">
		<tr>
			<th style="width:22%;">Salary</th>
			<th style="width:11%; white-space: nowrap;">National</th>
			<th style="width:12%;">Earned</th>
			<th style="width:26%;">Deductions</th>
			<th style="width:30%;">Month Days : <?php echo number_format($totalDays, 0); ?></th>
		</tr>
		<?php
		$leftHeads = ['Basic','H.R.A.','Convey.','Other All.','Medical','Incentive'];
		$rightHeads = ['P.F.','ESI','Income Tex','LWF','Loan','ADV','Other','V. P. F.'];
		$rows = max(count($leftHeads), count($rightHeads));
		for ($i = 0; $i < $rows; $i++) {
			$lh = $leftHeads[$i] ?? '';
			$rh = $rightHeads[$i] ?? '';
			$monthInfo = '';
			if ($i === 1) $monthInfo = 'Present Days : ' . number_format($attPresent, 0);
			if ($i === 2) $monthInfo = 'Paid Days : ' . number_format($attPaid, 0);
			?>
		<tr>
			<td><?php echo htmlspecialchars($lh, ENT_QUOTES, 'UTF-8'); ?></td>
			<td class="right"><?php echo $lh ? $formatByEightyRule(floatval($nationalMap[$lh] ?? 0)) : ''; ?></td>
			<td class="right"><?php echo $lh ? $formatByEightyRule(floatval($earnMap[$lh] ?? 0)) : ''; ?></td>
			<td><?php echo htmlspecialchars($rh, ENT_QUOTES, 'UTF-8'); ?></td>
			<td>
				<?php if ($rh !== '') { ?>
					<span style="float:left;"><?php echo $formatByEightyRule(floatval($dedMap[$rh] ?? 0)); ?></span>
				<?php } ?>
				<span style="float:right;"><?php echo htmlspecialchars($monthInfo, ENT_QUOTES, 'UTF-8'); ?></span>
			</td>
		</tr>
		<?php } ?>
	</table>

	<table class="totals" style="margin-top: 8px;">
		<tr>
			<td><strong>Total Earning :</strong> <?php echo $formatByEightyRule(floatval($slip['GrossSalary'])); ?></td>
			<td><strong>Total Deduction :</strong> <?php echo $formatByEightyRule(floatval($slip['TotalDeductions'])); ?></td>
			<td><strong>Net Payable<?php echo $kpiApplied ? ' (after KPI)' : ''; ?> :</strong> <?php echo $formatByEightyRule($netPayableDisplay); ?></td>
		</tr>
	</table>

	<div class="note">
		<strong>Note:</strong> This is system generated salary slip and does not require physical signature.
<?php if ($kpiApplied) { ?>
		KPI performance for this month is applied; net payable uses saved <em>PayableSalary</em> from the monthly KPI snapshot.
<?php } ?>
		In case of any discrepancy, please contact HR/Accounts within 3 working days.
	</div>
</div>
</body>
</html>
	<?php
	return ob_get_clean();
}

if ($pdfMode === 'preview' || $pdfMode === 'download') {
	$html = renderClassicSlipHtml($emp, $slip, $earn, $ded, true, $prevPaidDays, $cl, $sl, $pl, $company, $attendanceSummary, $isPublicShare);
	try {
		$mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
		$mpdf->WriteHTML($html);
		$fileBase = 'salary-slip-' . preg_replace('/[^A-Za-z0-9_-]/', '-', ($emp['EmployeeNumber'] ?? 'emp')) . '-' . intval($slip['PayrollYear']) . '-' . sprintf('%02d', intval($slip['PayrollMonth'])) . '.pdf';
		$dest = ($pdfMode === 'download') ? \Mpdf\Output\Destination::DOWNLOAD : \Mpdf\Output\Destination::INLINE;
		$mpdf->Output($fileBase, $dest);
		exit;
	} catch (\Throwable $e) {
		http_response_code(500);
		echo 'PDF generation failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
		exit;
	}
}

if ($isPublicShare) {
	header('Location: download-payslip.php?token=' . rawurlencode($shareToken));
	exit;
}
echo renderClassicSlipHtml($emp, $slip, $earn, $ded, false, $prevPaidDays, $cl, $sl, $pl, $company, $attendanceSummary, $isPublicShare);
if ($autoPrint) {
	echo "<script>window.addEventListener('load',function(){window.print();});</script>";
}
