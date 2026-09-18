<?php
@session_start();
require_once('../include/autoloader.inc.php');

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$download = !empty($_GET['download']);
$autoPrint = !empty($_GET['autoprint']);
$salary = new Salarypayroll($conn);
$data = $id ? $salary->getSlipWithLines($id) : null;

if ($data === null) {
	http_response_code(404);
	echo 'Slip not found';
	exit;
}

$slip = $data['slip'];
$emp = $data['employee'];
$lines = $data['lines'];

$earn = [];
$ded = [];
$empr = [];
foreach ($lines as $ln) {
	if ($ln['LineType'] === 'earning') {
		$earn[] = $ln;
	} elseif ($ln['LineType'] === 'deduction') {
		$ded[] = $ln;
	} else {
		$empr[] = $ln;
	}
}

$period = sprintf('%04d-%02d', intval($slip['PayrollYear']), intval($slip['PayrollMonth']));
$calc = [];
if (!empty($slip['CalculationJson'])) {
	$parsed = json_decode($slip['CalculationJson'], true);
	if (is_array($parsed)) {
		$calc = $parsed;
	}
}
$presentDays = isset($calc['present_days']) ? floatval($calc['present_days']) : 0;
if ($download) {
	$fileName = 'salary-slip-' . preg_replace('/[^A-Za-z0-9_-]/', '-', ($emp['EmployeeNumber'] ?? 'emp')) . '-' . $period . '.html';
	header('Content-Type: text/html; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $fileName . '"');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Payslip — <?php echo htmlspecialchars($emp['Name'], ENT_QUOTES, 'UTF-8'); ?></title>
	<style>
		body { font-family: "Times New Roman", serif; margin: 12px; color: #111; font-size: 13px; }
		.sheet { max-width: 920px; margin: 0 auto; border: 2px solid #000; padding: 8px; }
		.headline { font-size: 40px; font-weight: 700; letter-spacing: 1px; margin: 0 0 3px; text-align: center; }
		.subhead { text-align: center; font-size: 14px; margin: 0 0 8px; }
		table { width: 100%; border-collapse: collapse; }
		.block { border: 2px solid #000; margin-top: 8px; }
		.block td, .block th { border: 1px solid #000; padding: 4px 6px; vertical-align: top; }
		.label { font-weight: 700; }
		.right { text-align: right; }
		.center { text-align: center; }
		.section-title { font-weight: 700; background: #f5f5f5; }
		.no-print { margin-bottom: 10px; }
		@media print {
			body { margin: 0; }
			.sheet { border: 1px solid #000; }
			.no-print { display: none; }
		}
	</style>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body>
<div class="no-print" style="margin-bottom:12px;">
	<button onclick="window.print()">Print / Save PDF</button>
	<a href="view-payroll.php?y=<?php echo intval($slip['PayrollYear']); ?>&m=<?php echo intval($slip['PayrollMonth']); ?>">← Back</a>
</div>
<div class="sheet">
	<div class="headline">Salary Slip</div>
	<div class="subhead">Pay Slip For The Month Of: <?php echo htmlspecialchars(date('M-Y', strtotime($period . '-01')), ENT_QUOTES, 'UTF-8'); ?></div>

	<table class="block">
		<tr>
			<td><span class="label">Code</span> : <?php echo htmlspecialchars($emp['EmployeeNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><span class="label">Branch</span> : <?php echo htmlspecialchars($emp['Division'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><span class="label">Dept</span> : <?php echo htmlspecialchars($emp['Department'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
		</tr>
		<tr>
			<td><span class="label">Name</span> : <?php echo htmlspecialchars($emp['Name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><span class="label">Category</span> : <?php echo htmlspecialchars($emp['Vendor'] ? 'Vendor' : 'Staff', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><span class="label">Bank Name</span> : <?php echo htmlspecialchars($emp['BankAccountName'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
		</tr>
		<tr>
			<td><span class="label">D.O.J.</span> : <?php echo htmlspecialchars($emp['DateofJoining'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><span class="label">PAN No.</span> : <?php echo htmlspecialchars($emp['PAN'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><span class="label">A/C No.</span> : <?php echo htmlspecialchars($emp['BankAccountNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
		</tr>
		<tr>
			<td><span class="label">Desig</span> : <?php echo htmlspecialchars($emp['Designation'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><span class="label">D.O.B.</span> : <?php echo htmlspecialchars($emp['DateOfBirth'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
			<td><span class="label">ESIC No.</span> : <?php echo htmlspecialchars($emp['Esic_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
		</tr>
		<tr>
			<td><span class="label">Basic</span> : <?php echo number_format(floatval($emp['Basic'] ?? 0), 2); ?></td>
			<td><span class="label">No. Of Child</span> : </td>
			<td><span class="label">UAN No.</span> : <?php echo htmlspecialchars($emp['UANNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
		</tr>
	</table>

	<table class="block" style="margin-top:10px;">
		<tr class="section-title">
			<td style="width:28%">Salary</td>
			<td style="width:18%">Earned</td>
			<td style="width:34%">Deductions</td>
			<td style="width:20%">Month Days : <?php echo number_format(floatval($slip['TotalDays']), 0); ?></td>
		</tr>
		<tr>
			<td>
<?php foreach ($earn as $ln) { ?>
				<div><?php echo htmlspecialchars($ln['ComponentLabel'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php } ?>
			</td>
			<td class="right">
<?php foreach ($earn as $ln) { ?>
				<div><?php echo number_format(floatval($ln['Amount']), 2); ?></div>
<?php } ?>
			</td>
			<td>
<?php foreach ($ded as $ln) { ?>
				<div><?php echo htmlspecialchars($ln['ComponentLabel'], ENT_QUOTES, 'UTF-8'); ?> <span style="float:right"><?php echo number_format(floatval($ln['Amount']), 2); ?></span></div>
<?php } ?>
			</td>
			<td>
				<div>Present Days : <?php echo number_format($presentDays, 2); ?></div>
				<div>Paid Days : <?php echo number_format(floatval($slip['PaidDays']), 2); ?></div>
			</td>
		</tr>
	</table>

	<table class="block" style="margin-top:10px;">
		<tr>
			<td class="label">Total Earning : <?php echo number_format(floatval($slip['GrossSalary']), 2); ?></td>
			<td class="label">Total Deduction : <?php echo number_format(floatval($slip['TotalDeductions']), 2); ?></td>
			<td class="label">Net Payable : <?php echo number_format(floatval($slip['NetSalary']), 2); ?></td>
		</tr>
	</table>

<?php if (!empty($empr)) { ?>
	<table class="block" style="margin-top:10px;">
		<tr class="section-title"><td>Employer Statutory (Informational)</td></tr>
		<tr><td>
<?php foreach ($empr as $ln) { ?>
			<div><?php echo htmlspecialchars($ln['ComponentLabel'], ENT_QUOTES, 'UTF-8'); ?> <span style="float:right"><?php echo number_format(floatval($ln['Amount']), 2); ?></span></div>
<?php } ?>
		</td></tr>
	</table>
<?php } ?>
</div>
<?php if ($autoPrint) { ?>
<script>
window.addEventListener('load', function () {
	window.print();
});
</script>
<?php } ?>
</body>
</html>
