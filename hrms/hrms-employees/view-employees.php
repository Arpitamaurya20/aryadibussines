<?php
@session_start();
require_once('../include/autoloader.inc.php');
$conf = new Conf();

$dbh = new Dbh();
$conn = $dbh->_connectodb();
$session = new Session($conn);
$session->SessionCheck_redirect();

$core = new Core();
$salary = new Salarypayroll($conn);

$y = isset($_GET['y']) ? intval($_GET['y']) : intval(date('Y'));
$m = isset($_GET['m']) ? intval($_GET['m']) : intval(date('n'));
if ($m < 1 || $m > 12) $m = intval(date('n'));

$employees = $salary->listEmployeesWithSlipSummary($y, $m);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>HRMS Employees</title>
	<?php include('../include/common-head.php'); ?>
</head>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-6496901533964255"
     crossorigin="anonymous"></script>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
	<?php include('../navigation/top-header.php'); ?>
	<?php include('../navigation/side-navigation.php'); ?>
	<main class="app-main">
		<div class="app-content-header">
			<div class="container-fluid d-flex justify-content-between align-items-center">
				<div>
					<h1 class="mb-1">HRMS Employees</h1>
					<p class="text-muted mb-0">Professional employee directory with payroll status.</p>
				</div>
				<form method="get" class="row g-2 align-items-center">
					<div class="col-auto">
						<select name="m" class="form-select form-select-sm">
							<?php for ($i = 1; $i <= 12; $i++) {
								$sel = ($i === $m) ? ' selected' : '';
								echo '<option value="' . $i . '"' . $sel . '>' . date('M', mktime(0,0,0,$i,1)) . '</option>';
							} ?>
						</select>
					</div>
					<div class="col-auto"><input type="number" name="y" value="<?php echo $y; ?>" class="form-control form-control-sm"></div>
					<div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Go</button></div>
				</form>
			</div>
		</div>
		<div class="app-content">
			<div class="container-fluid">
				<div class="card">
					<div class="card-header">
						<input type="text" id="empSearch" class="form-control form-control-sm" placeholder="Search employee by code, name, designation">
					</div>
					<div class="card-body p-0">
						<div class="table-responsive">
							<table class="table table-hover mb-0" id="empTable">
								<thead>
									<tr>
										<th>Employee</th>
										<th>Code</th>
										<th>Designation</th>
										<th>Contact</th>
										<th>EPF/ESI</th>
										<th class="text-end">Net (<?php echo htmlspecialchars(date('M Y', mktime(0,0,0,$m,1,$y)), ENT_QUOTES, 'UTF-8'); ?>)</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
								<?php foreach ($employees as $e) { ?>
									<tr>
										<td><?php echo htmlspecialchars($e['Name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($e['EmployeeNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($e['Designation'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo htmlspecialchars($e['ContactNumber'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
										<td>
											<span class="badge text-bg-<?php echo intval($e['ApplyEPF'] ?? 1) ? 'success' : 'secondary'; ?>">EPF</span>
											<span class="badge text-bg-<?php echo intval($e['ApplyESI'] ?? 1) ? 'success' : 'secondary'; ?>">ESI</span>
										</td>
										<td class="text-end"><?php echo number_format(floatval($e['NetSalary'] ?? 0), 2); ?></td>
										<td>
											<a class="btn btn-sm btn-outline-primary" href="view-employee-profile.php?id=<?php echo intval($e['ID']); ?>&m=<?php echo $m; ?>&y=<?php echo $y; ?>">Profile</a>
										</td>
									</tr>
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
<script>
document.getElementById('empSearch')?.addEventListener('input', function () {
	const q = this.value.toLowerCase().trim();
	document.querySelectorAll('#empTable tbody tr').forEach(function (tr) {
		const txt = tr.textContent.toLowerCase();
		tr.style.display = (!q || txt.indexOf(q) !== -1) ? '' : 'none';
	});
});
</script>
</body>
</html>
